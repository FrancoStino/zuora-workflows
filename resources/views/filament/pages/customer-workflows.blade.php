<x-filament-panels::page>
    @if($error)
        <x-filament::section>
            <div class="flex items-center gap-3 text-danger-600 dark:text-danger-400">
                <x-filament::icon
                    icon="heroicon-o-exclamation-circle"
                    class="h-6 w-6"
                />
                <span class="font-medium">{{ $error }}</span>
            </div>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">
            Workflows List
        </x-slot>

        @if(count($workflows) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                State
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Created
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Updated
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($workflows as $workflow)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $workflow['id'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $workflow['name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $state = $workflow['state'] ?? $workflow['status'] ?? 'Unknown';
                                        $badgeColor = match($state) {
                                            'Active' => 'success',
                                            'Inactive' => 'gray',
                                            default => 'danger',
                                        };
                                    @endphp
                                    <x-filament::badge :color="$badgeColor">
                                        {{ $state }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $workflow['created_on'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $workflow['updated_on'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <x-filament::button
                                        wire:click="downloadWorkflow('{{ $workflow['id'] }}')"
                                        size="sm"
                                        icon="heroicon-o-arrow-down-tray"
                                    >
                                        Download
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <x-filament::icon
                    icon="heroicon-o-inbox"
                    class="mx-auto h-12 w-12 text-gray-400"
                />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    No workflows found for this customer.
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
