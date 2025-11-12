<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
<div class="mt-6 flex justify-end gap-3">
    <x-filament::button 
        type="submit" 
        size="lg"
        color="primary"
        class="px-10 text-center justify-center">
        Save Settings
    </x-filament::button>
</div>2
    </form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Current Active Settings
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Discount Status -->
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-center text-3xl mb-2">
                    @if(is_discount_enabled())
                        ✅
                    @else
                        ❌
                    @endif
                </div>
                <div class="text-center text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Available Discounts</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @php $selectedDiscounts = get_selected_discounts(); @endphp
                    @if($selectedDiscounts->isNotEmpty())
                        <ul class="space-y-1">
                            @foreach($selectedDiscounts as $discount)
                                <li class="flex items-center">
                                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                    <strong>{{ $discount->name }}</strong>
                                    <span class="ml-auto">
                                        {{ $discount->type === 'percentage' ? $discount->value . '%' : 'Rp' . number_format($discount->value) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-2 pt-2 border-t border-gray-300 dark:border-gray-600 text-center">
                            <strong>{{ $selectedDiscounts->count() }}</strong> discount(s) available
                        </div>
                    @else
                        <div class="text-center">No discounts</div>
                    @endif
                </div>
            </div>

            <!-- Tax Status -->
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-center text-3xl mb-2">
                    @if(is_tax_enabled())
                        ✅
                    @else
                        ❌
                    @endif
                </div>
                <div class="text-center text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tax Rates</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @php $selectedTaxes = get_selected_taxes(); @endphp
                    @if($selectedTaxes->isNotEmpty())
                        <ul class="space-y-1">
                            @foreach($selectedTaxes as $tax)
                                <li class="flex items-center">
                                    <span class="inline-block w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                    <strong>{{ $tax->name }}</strong>
                                    <span class="ml-auto">{{ $tax->value }}%</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-2 pt-2 border-t border-gray-300 dark:border-gray-600 text-center">
                            <strong>Total: {{ tax_percentage() }}%</strong>
                        </div>
                    @else
                        <div class="text-center">No tax</div>
                    @endif
                </div>
            </div>

            <!-- Service Charge Status -->
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-center text-3xl mb-2">
                    @if(is_service_charge_enabled())
                        ✅
                    @else
                        ❌
                    @endif
                </div>
                <div class="text-center text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service Charges</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @php $selectedServices = get_selected_services(); @endphp
                    @if($selectedServices->isNotEmpty())
                        <ul class="space-y-1">
                            @foreach($selectedServices as $service)
                                <li class="flex items-center">
                                    <span class="inline-block w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                                    <strong>{{ $service->name }}</strong>
                                    <span class="ml-auto">{{ $service->value }}%</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-2 pt-2 border-t border-gray-300 dark:border-gray-600 text-center">
                            <strong>Total: {{ get_active_service_charge() }}%</strong>
                        </div>
                    @else
                        <div class="text-center">No service charge</div>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
