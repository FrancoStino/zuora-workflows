<x-dynamic-component
        :component="$getEntryWrapperView()"
        :entry="$entry"
    >
    <div {{ $getExtraAttributeBag() }}>
        @php
            $state = $getState();
            
            // Handle different data types
            if (is_array($state)) {
                $output = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } elseif (is_string($state)) {
                // Try to decode as JSON first to format it nicely
                $decoded = json_decode($state, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $output = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } else {
                    $output = $state;
                }
            } elseif (is_null($state)) {
                $output = 'No data available';
            } else {
                $output = (string) $state;
            }
        @endphp
        
        <pre class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto text-sm font-mono">{{ $output }}</pre>
    </div>
</x-dynamic-component>
