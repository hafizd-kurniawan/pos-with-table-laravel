<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button 
            type="submit" 
            class="mt-6"
            icon="heroicon-o-check"
        >
            Save Settings
        </x-filament::button>
    </form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Quick Guide
        </x-slot>

        <x-slot name="description">
            How these settings affect your system
        </x-slot>

        <div class="space-y-3 text-sm">
            <div class="flex items-start gap-3">
                <div class="text-primary-500 font-semibold">Discount:</div>
                <div class="flex-1 text-gray-600 dark:text-gray-400">
                    Manage individual discounts in <strong>Discounts</strong> menu. Only active and non-expired discounts will appear.
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-primary-500 font-semibold">Tax:</div>
                <div class="flex-1 text-gray-600 dark:text-gray-400">
                    Configure tax rates in <strong>Taxes</strong> menu (type: Pajak). Multiple tax rates can be available for selection.
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-primary-500 font-semibold">Service Charge:</div>
                <div class="flex-1 text-gray-600 dark:text-gray-400">
                    Manage service rates in <strong>Taxes</strong> menu (type: Layanan). Service charge is applied after discount and tax.
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-warning-50 dark:bg-warning-950 rounded-lg border border-warning-200 dark:border-warning-800">
                <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="flex-1">
                    <div class="font-semibold text-warning-800 dark:text-warning-200">Important</div>
                    <div class="text-warning-700 dark:text-warning-300 mt-1">
                        Changes apply immediately to POS and Self-Order systems. Existing orders are not affected.
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
