import $ from 'jquery';
import * as joint from 'jointjs';
import * as _ from 'lodash';
import * as backbone from 'backbone';
import dagre from 'dagre';

// Make dependencies available globally for JointJS
window.$ = window.jQuery = $;
window._ = _;
window.Backbone = backbone;

export function initWorkflowGraph(containerId, workflowData) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Container ${containerId} not found`);
        return;
    }

    // Clear any existing content
    container.innerHTML = '';

    // Create the JointJS graph and paper
    const graph = new joint.dia.Graph();
    
    // Get container dimensions
    const containerWidth = container.offsetWidth || container.clientWidth || 1200;
    const containerHeight = Math.max(600, container.offsetHeight || 600);
    
    const paper = new joint.dia.Paper({
        el: container,
        model: graph,
        width: containerWidth,
        height: containerHeight,
        gridSize: 10,
        drawGrid: true,
        background: {
            color: '#f8fafc'
        },
        interactive: {
            linkMove: false
        },
        defaultLink: () => new joint.shapes.standard.Link({
            attrs: {
                line: {
                    stroke: '#3b82f6',
                    strokeWidth: 2,
                    targetMarker: {
                        type: 'path',
                        d: 'M 10 -5 0 0 10 5 Z',
                        fill: '#3b82f6'
                    }
                }
            }
        })
    });
    
    // Handle window resize to adjust paper size
    const resizeObserver = new ResizeObserver(() => {
        const newWidth = container.offsetWidth || container.clientWidth || 1200;
        const newHeight = Math.max(600, container.offsetHeight || 600);
        paper.setDimensions(newWidth, newHeight);
    });
    resizeObserver.observe(container);

    // Parse workflow JSON
    try {
        const workflow = typeof workflowData === 'string' ? JSON.parse(workflowData) : workflowData;
        
        if (!workflow) {
            throw new Error('No workflow data provided');
        }

        // Build the graph from workflow structure
        buildWorkflowGraph(graph, workflow);

        // Apply automatic layout using Dagre
        layoutGraph(graph);

        // Center the paper content
        paper.fitToContent({
            padding: 50,
            allowNewOrigin: 'any'
        });

        // Add zoom and pan controls
        addZoomAndPanControls(container, paper);

    } catch (error) {
        console.error('Error rendering workflow graph:', error);
        container.innerHTML = `
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 font-semibold">Error rendering workflow graph</p>
                <p class="text-red-600 text-sm mt-2">${error.message}</p>
            </div>
        `;
    }
}

function layoutGraph(graph) {
    // Create a new directed graph for Dagre
    const g = new dagre.graphlib.Graph();
    g.setGraph({
        rankdir: 'LR', // Left to Right (horizontal layout)
        nodesep: 80,
        edgesep: 80,
        ranksep: 150, // Increased spacing for horizontal layout
        marginx: 50,
        marginy: 50
    });
    g.setDefaultEdgeLabel(() => ({}));

    // Add nodes to Dagre graph
    const cells = graph.getCells();
    cells.forEach(cell => {
        if (cell.isElement()) {
            const size = cell.size();
            g.setNode(cell.id, {
                width: size.width,
                height: size.height
            });
        }
    });

    // Add edges to Dagre graph
    cells.forEach(cell => {
        if (cell.isLink()) {
            const source = cell.getSourceElement();
            const target = cell.getTargetElement();
            if (source && target) {
                g.setEdge(source.id, target.id);
            }
        }
    });

    // Run the layout algorithm
    dagre.layout(g);

    // Apply the calculated positions to JointJS elements
    g.nodes().forEach(nodeId => {
        const node = g.node(nodeId);
        const cell = graph.getCell(nodeId);
        if (cell) {
            cell.position(node.x - node.width / 2, node.y - node.height / 2);
        }
    });
}

function buildWorkflowGraph(graph, workflow) {
    const elements = {};
    
    // Create start node
    const startNode = createNode('start', 'Start', 'start', '#10b981');
    graph.addCell(startNode);
    elements['start'] = startNode;
    elements['workflow-start'] = startNode; // Zuora uses workflow as source

    // Process workflow tasks
    if (workflow.tasks && Array.isArray(workflow.tasks)) {
        workflow.tasks.forEach((task) => {
            const nodeId = String(task.id);
            const actionType = task.action_type || task.type || 'task';
            const node = createNode(
                nodeId,
                task.name || `Task ${task.id}`,
                actionType,
                getNodeColor(actionType)
            );
            
            // Use initial position from CSS if available
            if (task.css) {
                const top = parseFloat(task.css.top) || 0;
                const left = parseFloat(task.css.left) || 0;
                node.position(left, top);
            } else {
                // Default positioning will be handled by layout algorithm
                node.position(0, 0);
            }
            
            graph.addCell(node);
            elements[nodeId] = node;
        });
    } else if (workflow.nodes && Array.isArray(workflow.nodes)) {
        workflow.nodes.forEach((node, index) => {
            const nodeId = String(node.id || `node-${index}`);
            const graphNode = createNode(
                nodeId,
                node.name || node.label || `Node ${index + 1}`,
                node.type || 'node',
                getNodeColor(node.type)
            );
            
            graphNode.position(0, 0);
            graph.addCell(graphNode);
            elements[nodeId] = graphNode;
        });
    }

    // Create end node
    const endNode = createNode('end', 'End', 'end', '#ef4444');
    graph.addCell(endNode);
    elements['end'] = endNode;

    // Create links based on linkages (Zuora format)
    if (workflow.linkages && Array.isArray(workflow.linkages)) {
        workflow.linkages.forEach(linkage => {
            let sourceId, targetId;
            
            // Handle Zuora linkage format
            if (linkage.source_workflow_id !== null && linkage.source_task_id === null) {
                // Start linkage
                sourceId = 'start';
            } else if (linkage.source_task_id !== null) {
                sourceId = String(linkage.source_task_id);
            }
            
            if (linkage.target_task_id !== null) {
                targetId = String(linkage.target_task_id);
            }
            
            if (sourceId && targetId && elements[sourceId] && elements[targetId]) {
                const label = linkage.linkage_type || '';
                const link = createLink(elements[sourceId], elements[targetId], label);
                graph.addCell(link);
            }
        });
        
        // Find terminal tasks (tasks with no outgoing links) and connect to end
        const tasksWithOutgoing = new Set();
        workflow.linkages.forEach(linkage => {
            if (linkage.source_task_id !== null) {
                tasksWithOutgoing.add(String(linkage.source_task_id));
            }
        });
        
        workflow.tasks?.forEach(task => {
            const taskId = String(task.id);
            if (!tasksWithOutgoing.has(taskId) && elements[taskId]) {
                const link = createLink(elements[taskId], elements['end'], 'Complete');
                graph.addCell(link);
            }
        });
    } else if (workflow.connections && Array.isArray(workflow.connections)) {
        // Generic connections format
        workflow.connections.forEach(conn => {
            const sourceId = String(conn.source);
            const targetId = String(conn.target);
            if (elements[sourceId] && elements[targetId]) {
                const link = createLink(elements[sourceId], elements[targetId], conn.label);
                graph.addCell(link);
            }
        });
    } else if (workflow.edges && Array.isArray(workflow.edges)) {
        // Edges format
        workflow.edges.forEach(edge => {
            const sourceId = String(edge.from);
            const targetId = String(edge.to);
            if (elements[sourceId] && elements[targetId]) {
                const link = createLink(elements[sourceId], elements[targetId], edge.label);
                graph.addCell(link);
            }
        });
    } else {
        // Fallback: create sequential connections
        const nodeIds = Object.keys(elements).filter(id => id !== 'start' && id !== 'end');
        if (nodeIds.length > 0) {
            const link1 = createLink(elements['start'], elements[nodeIds[0]]);
            graph.addCell(link1);
            
            for (let i = 0; i < nodeIds.length - 1; i++) {
                const link = createLink(elements[nodeIds[i]], elements[nodeIds[i + 1]]);
                graph.addCell(link);
            }
            
            const linkEnd = createLink(elements[nodeIds[nodeIds.length - 1]], elements['end']);
            graph.addCell(linkEnd);
        }
    }
}

function createNode(id, label, type, color) {
    const node = new joint.shapes.standard.Rectangle({
        id: id,
        size: { width: 180, height: 60 },
        attrs: {
            body: {
                fill: color,
                stroke: darkenColor(color, 20),
                strokeWidth: 2,
                rx: 8,
                ry: 8
            },
            label: {
                text: truncateText(label, 20),
                fill: '#ffffff',
                fontSize: 14,
                fontWeight: 'bold',
                fontFamily: 'Inter, system-ui, sans-serif'
            }
        }
    });

    // Add tooltip
    node.attr('body/title', label);

    return node;
}

function createLink(source, target, label = '') {
    const link = new joint.shapes.standard.Link({
        source: { id: source.id },
        target: { id: target.id },
        attrs: {
            line: {
                stroke: '#64748b',
                strokeWidth: 2,
                targetMarker: {
                    type: 'path',
                    d: 'M 10 -5 0 0 10 5 Z',
                    fill: '#64748b'
                }
            }
        },
        labels: label ? [{
            attrs: {
                text: {
                    text: label,
                    fill: '#475569',
                    fontSize: 12
                },
                rect: {
                    fill: '#ffffff',
                    stroke: '#cbd5e1',
                    strokeWidth: 1,
                    rx: 4,
                    ry: 4
                }
            }
        }] : []
    });

    return link;
}

function getNodeColor(type) {
    const colors = {
        'start': '#10b981',
        'end': '#ef4444',
        // Zuora action types
        'export': '#3b82f6',
        'iterate': '#f59e0b',
        'cancel': '#ef4444',
        'create': '#10b981',
        'update': '#8b5cf6',
        'delete': '#dc2626',
        'query': '#06b6d4',
        'callout': '#ec4899',
        'email': '#f59e0b',
        'wait': '#6b7280',
        'approval': '#fbbf24',
        // Generic types
        'task': '#3b82f6',
        'decision': '#f59e0b',
        'action': '#8b5cf6',
        'notification': '#ec4899',
        'default': '#3b82f6'
    };

    return colors[type?.toLowerCase()] || colors['default'];
}

function darkenColor(color, percent) {
    const num = parseInt(color.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = (num >> 16) - amt;
    const G = (num >> 8 & 0x00FF) - amt;
    const B = (num & 0x0000FF) - amt;
    
    return '#' + (
        0x1000000 + 
        (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
        (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
        (B < 255 ? (B < 1 ? 0 : B) : 255)
    ).toString(16).slice(1);
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength - 3) + '...';
}

function addZoomAndPanControls(container, paper) {
    const controls = document.createElement('div');
    controls.className = 'absolute top-4 right-4 flex flex-col gap-2 z-10';
    controls.innerHTML = `
        <button id="zoom-in-btn" class="bg-white border border-gray-300 rounded-lg p-2 hover:bg-gray-50 shadow-sm transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
        </button>
        <button id="zoom-out-btn" class="bg-white border border-gray-300 rounded-lg p-2 hover:bg-gray-50 shadow-sm transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
            </svg>
        </button>
        <button id="zoom-reset-btn" class="bg-white border border-gray-300 rounded-lg p-2 hover:bg-gray-50 shadow-sm transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    `;

    container.style.position = 'relative';
    container.appendChild(controls);

    let scale = 1;
    const scaleStep = 0.1;
    const minScale = 0.2;
    const maxScale = 3;

    // Zoom in
    document.getElementById('zoom-in-btn')?.addEventListener('click', () => {
        scale = Math.min(scale + scaleStep, maxScale);
        paper.scale(scale, scale);
    });

    // Zoom out
    document.getElementById('zoom-out-btn')?.addEventListener('click', () => {
        scale = Math.max(scale - scaleStep, minScale);
        paper.scale(scale, scale);
    });

    // Reset zoom
    document.getElementById('zoom-reset-btn')?.addEventListener('click', () => {
        scale = 1;
        paper.scale(scale, scale);
        paper.fitToContent({
            padding: 50,
            allowNewOrigin: 'any'
        });
    });

    // Enable mouse wheel zoom
    container.addEventListener('wheel', (event) => {
        event.preventDefault();
        const delta = event.deltaY > 0 ? -scaleStep : scaleStep;
        scale = Math.max(minScale, Math.min(maxScale, scale + delta));
        paper.scale(scale, scale);
    });

    // Enable panning with mouse drag
    let isPanning = false;
    let startPoint = { x: 0, y: 0 };
    let paperOrigin = { x: 0, y: 0 };

    container.addEventListener('mousedown', (event) => {
        if (event.target === container || event.target.closest('.joint-paper')) {
            isPanning = true;
            startPoint = { x: event.clientX, y: event.clientY };
            const paperEl = paper.el;
            const transform = window.getComputedStyle(paperEl).transform;
            if (transform !== 'none') {
                const matrix = new DOMMatrix(transform);
                paperOrigin = { x: matrix.e, y: matrix.f };
            }
            container.style.cursor = 'grabbing';
        }
    });

    document.addEventListener('mousemove', (event) => {
        if (isPanning) {
            const dx = event.clientX - startPoint.x;
            const dy = event.clientY - startPoint.y;
            paper.translate(paperOrigin.x + dx, paperOrigin.y + dy);
        }
    });

    document.addEventListener('mouseup', () => {
        if (isPanning) {
            isPanning = false;
            container.style.cursor = 'default';
        }
    });
}
