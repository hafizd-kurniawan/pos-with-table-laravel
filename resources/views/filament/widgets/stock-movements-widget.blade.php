<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <x-slot name="heading">
            📦 Stock Movements (Today)
        </x-slot>

        <div class="space-y-4 -mx-2">
            @forelse($this->getMovements() as $movement)
                <div class="flex items-start space-x-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        @if($movement['type'] === 'in')
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-400 text-base">🔵</span>
                            </div>
                        @elseif($movement['type'] === 'out')
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                                <span class="text-red-600 dark:text-red-400 text-base">🔴</span>
                            </div>
                        @elseif($movement['type'] === 'adjustment')
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <span class="text-green-600 dark:text-green-400 text-base">🟢</span>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                                <span class="text-yellow-600 dark:text-yellow-400 text-base">🟡</span>
                            </div>
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $movement['time'] }}
                            </p>
                            <span class="text-sm {{ $movement['type'] === 'in' ? 'text-green-600' : 'text-red-600' }} font-bold">
                                {{ $movement['type'] === 'in' ? '+' : '-' }}{{ number_format($movement['quantity'], 0, ',', '.') }} {{ $movement['unit'] }}
                            </span>
                        </div>
                        <p class="text-base text-gray-900 dark:text-gray-100 font-semibold">
                            {{ $movement['ingredient'] }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $movement['reference'] }}
                        </p>
                        @if($movement['notes'])
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 italic">
                                {{ $movement['notes'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">No stock movements today</p>
                </div>
            @endforelse

            @if(count($this->getMovements()) > 0)
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('filament.admin.resources.ingredients.index') }}" 
                       class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                        View All Movements →
                    </a>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
