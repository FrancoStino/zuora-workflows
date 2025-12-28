# Documentation Components

This directory contains custom MDX components used throughout the Zuora Workflow Manager documentation. These components follow the Mintlify design patterns and provide a consistent, modern look across all documentation pages.

## Component Library

### Layout Components

#### `TechnologiesBadge`
Displays technology version badges.

```jsx
<TechnologiesBadge badges={['Laravel 12', 'Filament 4.2', 'PHP 8.4']} />
```

#### `FeaturesList`
Renders a responsive grid of feature cards with icons.

```jsx
<FeaturesList
  features={[
    {
      icon: '🔄',
      title: 'Automatic Synchronization',
      description: 'Configurable scheduled sync'
    }
  ]}
/>
```

#### `TechStack`
Three-column layout showing backend, frontend, and library stacks.

```jsx
<TechStack
  backend={[{ name: 'Laravel', version: '12.x', url: 'https://laravel.com' }]}
  frontend={[{ name: 'Filament', version: '4.2', url: 'https://filamentphp.com' }]}
  libraries={[{ name: 'filament-shield', description: 'RBAC' }]}
/>
```

#### `ArchitectureDiagram`
Visual representation of system architecture layers.

```jsx
<ArchitectureDiagram />
```

#### `Steps`
Sequential step-by-step guide with optional commands.

```jsx
<Steps steps={[
  { title: 'Step 1', command: 'git clone ...' },
  { title: 'Step 2', command: 'composer install' }
]} />
```

#### `FolderTree`
Interactive directory tree with descriptions.

```jsx
<FolderTree
  name="project/"
  items={[
    { name: 'app/', type: 'folder' },
    { name: 'config/', type: 'folder' }
  ]}
/>
```

#### `NextSteps`
Standardized next steps navigation.

```jsx
<NextSteps
  links={[
    { title: 'Getting Started', href: './getting-started', description: 'Installation guide' }
  ]}
/>
```

#### `LinksGrid`
Grid of external resource links with icons.

```jsx
<LinksGrid
  links={[
    { title: 'Laravel', url: 'https://laravel.com', icon: '📚' }
  ]}
/>
```

### UI Components

#### `Alert`
Colored alert boxes for important information.

```jsx
<Alert type="warning">
  <strong>Warning:</strong> This is important
</Alert>
```

**Types:** `warning`, `info`, `success`, `error`

#### `CodeBlock`
Syntax-highlighted code blocks with tabs.

```jsx
<CodeBlock
  title="Installation"
  tabs={[
    { name: 'Bash', code: 'npm install', language: 'bash' },
    { name: 'Yarn', code: 'yarn install', language: 'bash' }
  ]}
/>
```

#### `Code` (Inline)
Inline code styling.

```jsx
Use <Code inline>--verbose</Code> flag
```

#### `DataTable`
Responsive data tables.

```jsx
<DataTable
  columns={['Column 1', 'Column 2']}
  rows={[
    ['Value 1', 'Value 2'],
    ['Value 3', 'Value 4']
  ]}
/>
```

#### `InfoCards`
Grid of informational cards.

```jsx
<InfoCards
  cards={[
    { title: 'Card 1', description: 'Description' }
  ]}
/>
```

#### `Checklist`
Interactive checklist items.

```jsx
<Checklist
  items={[
    'Item 1',
    'Item 2',
    'Item 3'
  ]}
/>
```

### Technical Components

#### `ServiceCard`
Detailed service documentation with methods.

```jsx
<ServiceCard
  title="ZuoraService"
  description="Handles Zuora API interactions"
  location="app/Services/ZuoraService.php"
  responsibilities={['OAuth', 'API calls']}
  methods={[
    { name: 'getAccessToken', signature: '...', description: '...' }
  ]}
/>
```

#### `ModelCard`
Eloquent model documentation.

```jsx
<ModelCard
  title="Customer"
  description="Customer model"
  location="app/Models/Customer.php"
  properties={[{ name: 'id', type: 'Primary Key' }]}
  relationships={[{ type: 'hasMany', relation: 'workflows' }]}
/>
```

#### `ERDiagram`
Entity relationship diagram visualization.

```jsx
<ERDiagram />
```

#### `FlowDiagram`
Process flow diagram with Mermaid.

```jsx
<FlowDiagram type="workflow-sync" title="Sync Flow" />
```

#### `APIEndpoint`
REST API endpoint documentation.

```jsx
<APIEndpoint
  title="Get User"
  method="GET"
  path="/api/user"
  auth={true}
  response={{ ... }}
  statusCodes={[{ code: 200, description: 'Success' }]}
/>
```

#### `CodeExample`
Multi-step code examples.

```jsx
<CodeExample
  title="Setup Process"
  steps={[
    { title: 'Step 1', code: '...' },
    { title: 'Step 2', code: '...' }
  ]}
/>
```

### Specialized Components

#### `RequirementsCard`
Requirements list with checkboxes.

```jsx
<RequirementsCard
  title="Server Requirements"
  requirements={[
    { name: 'PHP', version: '8.4+', required: true }
  ]}
/>
```

#### `PrerequisitesCheck`
Interactive prerequisite checklist.

```jsx
<PrerequisitesCheck
  items={[
    { name: 'Lando', url: 'https://lando.dev' }
  ]}
/>
```

#### `InstallationSteps`
Step-by-step installation guide.

```jsx
<InstallationSteps
  steps={[
    { title: 'Clone', commands: ['git clone ...'] }
  ]}
/>
```

#### `QuickCommands`
Grouped command reference.

```jsx
<QuickCommands
  groups={[
    {
      title: 'Basic',
      commands: [{ command: 'lando start', description: 'Start' }]
    }
  ]}
/>
```

#### `TroubleshootingCard`
Problem-solution cards.

```jsx
<TroubleshootingCard
  problems={[
    {
      title: 'Issue',
      solution: 'Fix it',
      commands: ['fix']
    }
  ]}
/>
```

#### `DeploymentDiagram`
Deployment architecture visualization.

```jsx
<DeploymentDiagram />
```

#### `PostDeployment`
Post-deployment checklist and steps.

```jsx
<PostDeployment />
```

#### `MonitoringSection`
Monitoring and logging configuration.

```jsx
<MonitoringSection />
```

#### `BackupStrategy`
Backup configuration examples.

```jsx
<BackupStrategy />
```

## Design System

### Colors

- **Primary**: `#0d9488` (teal)
- **Success**: `#10b981` (green)
- **Warning**: `#f59e0b` (amber)
- **Error**: `#ef4444` (red)
- **Info**: `#3b82f6` (blue)
- **Purple**: `#8b5cf6`

### Typography

- **Headings**: Inter, semibold
- **Body**: Inter, regular
- **Code**: JetBrains Mono, monospace
- **Tables**: Inter, with appropriate weights

### Spacing

- `space-xs`: 0.25rem (4px)
- `space-sm`: 0.5rem (8px)
- `space-md`: 1rem (16px)
- `space-lg`: 1.5rem (24px)
- `space-xl`: 2rem (32px)
- `space-2xl`: 3rem (48px)

## Usage Guidelines

### 1. Import Components

Always import components from the `@/components` directory:

```jsx
import { Alert } from '@/components/alert'
import { DataTable } from '@/components/data-table'
```

### 2. Component Composition

Combine components for complex layouts:

```jsx
<Alert type="warning">
  <strong>Important:</strong> Read the configuration

  <DataTable columns={['Key', 'Value']} rows={[...]} />
</Alert>
```

### 3. Code Blocks

Use `CodeBlock` for multi-line code, `Code` for inline:

```jsx
<CodeBlock code="const x = 1" language="javascript" />
Use the <Code inline>--verbose</Code> flag
```

### 4. Links

Use `NextSteps` for internal navigation, `LinksGrid` for external:

```jsx
<NextSteps links={[{ title: 'Next', href: './next', description: 'Continue' }]} />
<LinksGrid links={[{ title: 'Docs', url: 'https://example.com', icon: '📚' }]} />
```

## Component Reference

All components are located in the `@/components` directory:

```
components/
├── alert.tsx
├── api-endpoint.tsx
├── app-settings.tsx
├── architecture-diagram.tsx
├── backup-strategy.tsx
├── best-practices.tsx
├── code-block.tsx
├── code-example.tsx
├── config.tsx            # Base component exports
├── data-table.tsx
├── deployment-diagram.tsx
├── deployment-methods.tsx
├── diagram.tsx           # Mermaid diagram wrapper
├── env-config-section.tsx
├── er-diagram.tsx
├── exception-handling.tsx
├── features-list.tsx
├── feature-section.tsx
├── filament-config.tsx
├── flow-diagram.tsx
├── folder-tree.tsx
├── info-cards.tsx
├── index.ts
├── installation-steps.tsx
├── job-processing.tsx
├── links-grid.tsx
├── maintenance-config.tsx
├── monitoring-section.tsx
├── model-card.tsx
├── next-steps.tsx
├── pagination-info.tsx
├── performance-config.tsx
├── post-deployment.tsx
├── prerequisites-check.tsx
├── quick-commands.tsx
├── rate-limiting.tsx
├── requirements-card.tsx
├── security-config.tsx
├── server-config.tsx
├── service-api.tsx
├── service-card.tsx
├── steps.tsx
├── system-architecture.tsx
├── tech-stack.tsx
├── testing-section.tsx
├── troubleshooting-card.tsx
├── workflow-graph.tsx
└── workflow-visualization.tsx
```

## Migration from Old Components

### Old Pattern

```mdx
<div className="warning">
  <p><strong>Warning:</strong> This is deprecated</p>
</div>
```

### New Pattern

```mdx
<Alert type="warning">
  <strong>Warning:</strong> This is deprecated
</Alert>
```

### Benefits

- **Type Safety**: TypeScript prop validation
- **Consistency**: Unified design system
- **Maintainability**: Centralized component updates
- **Accessibility**: Built-in a11y features
- **Performance**: Optimized rendering

## Best Practices

1. **Use Components Over HTML**: Always use components instead of raw HTML
2. **Consistent Naming**: Follow existing naming conventions
3. **Proper Prop Types**: Define clear interfaces for props
4. **Responsive Design**: Test on mobile and desktop
5. **Accessibility**: Include ARIA labels where needed
6. **Code Examples**: Use `CodeBlock` with proper language highlighting
7. **Internal Links**: Use `NextSteps` for navigation flow
8. **External Links**: Use `LinksGrid` for external resources

## Contributing

When adding new components:

1. Create the component file in `@/components/`
2. Export from `@/components/index.ts`
3. Add documentation to this README
4. Follow existing patterns and styling
5. Ensure TypeScript types are properly defined
6. Test across different screen sizes

## Mintlify Integration

These components are designed to work seamlessly with Mintlify's documentation system:

- **Auto-generated navigation**: Based on file structure
- **Search integration**: Component content is searchable
- **SEO optimization**: Proper meta tags
- **Theme support**: Dark/light mode compatible
- **Performance**: Optimized for fast loading

## Support

For questions or issues with documentation components:

1. Check this README for component documentation
2. Review existing usage in `.mdx` files
3. Refer to Mintlify documentation: https://mintlify.com/docs

---

**Last Updated**: December 2025
**Version**: 1.0.0
