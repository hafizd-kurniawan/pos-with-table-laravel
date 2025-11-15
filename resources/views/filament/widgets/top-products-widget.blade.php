<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <x-slot name="heading">
            🏆 Top Products (Last 7 Days)
        </x-slot>

        @php
            $products = $this->getProducts();
        @endphp

        @if(count($products) > 0)
            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-base divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                #
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                Product
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                Qty Sold
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                Revenue
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                Trend
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                        @foreach($products as $index => $product)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <td class="px-6 py-5 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 font-bold text-base">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="font-semibold text-base text-gray-900 dark:text-gray-100">
                                        {{ $product['name'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                        {{ number_format($product['quantity'], 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-right">
                                    <span class="font-semibold text-base text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($product['revenue'], 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-center">
                                    @php
                                        $change = $product['change'];
                                        $icon = $change > 0 ? '↗️' : ($change < 0 ? '↘️' : '→');
                                        $colorClass = $change > 0 
                                            ? 'bg-success-100 dark:bg-success-900 text-success-800 dark:text-success-200' 
                                            : ($change < 0 
                                                ? 'bg-danger-100 dark:bg-danger-900 text-danger-800 dark:text-danger-200' 
                                                : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200');
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $colorClass }}">
                                        {{ $icon }} {{ number_format(abs($change), 0, ',', '.') }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">
                    No sales data yet
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Start making sales to see your top products here
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
