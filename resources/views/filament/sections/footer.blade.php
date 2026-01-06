<footer
    class="fixed bottom-0 left-0 z-20 w-full p-4 border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 dark:border-gray-600">
    <div class="flex flex-col lg:flex-row items-center justify-between gap-9">
        {{-- Copyright notice --}}
        <div class="text-sm">
            © {{ date('Y') }}
            <x-filament::link href="https://github.com/FrancoStino/zuora-workflows" target="_blank">
                Zuora Workflow Manager
            </x-filament::link>
            - All Rights Reserved.
        </div>

        {{-- Social buttons --}}
        <div class="flex gap-2">
            <x-filament::button
                tag="a"
                href="https://github.com/FrancoStino/zuora-workflows"
                target="_blank"
                rel="noopener noreferrer"
                color="gray"
                outlined
                size="sm"
            >
                <x-filament::badge
                    size="sm"
                    color="gold"
                >
                    @livewire('stars-git-hub')
                </x-filament::badge>
                <x-filament::icon-button
                    icon="heroicon-o-star"
                    color="gold"
                >
                </x-filament::icon-button>
                Star on GitHub
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="https://github.com/FrancoStino/zuora-workflows/issues"
                target="_blank"
                rel="noopener noreferrer"
                color="gray"
                outlined
                size="sm"
            >
                <x-filament::icon-button
                    icon="heroicon-o-exclamation-circle"
                    color="danger"
                >
                </x-filament::icon-button>
                Report Issue
            </x-filament::button>
        </div>
    </div>
</footer>
