<div class="rounded-lg overflow-hidden">
    <div x-data="{
        jsonData: @js($data),
        init() {
            // Initialize CodeMirror or similar if needed
        }
    }">
        <pre class="!bg-gray-900 !text-gray-100 p-4 rounded-lg overflow-x-auto text-xs font-mono"
             style="max-height: 600px;"><code>{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
    </div>
</div>
