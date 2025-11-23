<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <div class="flex items-center justify-between mb-4">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🚨</span>
                    <span>Stock Alerts</span>
                </div>
            </x-slot>
            @php
                $stats = $this->getStats();
            @endphp
            @if($stats['total'] > 0)
                <x-filament::badge color="danger" size="lg">
                    {{ $stats['total'] }} alerts
                </x-filament::badge>
            @endif
        </div>

        @php
            $alerts = $this->getAlerts();
        @endphp

        @if(count($alerts) > 0)
            <div class="space-y-3">
                @foreach($alerts as $alert)
                    <div class="flex items-center gap-3 p-3 rounded-lg border-2 {{ $alert['alert_level'] === 'critical' ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20' }}">
                        <div class="flex-shrink-0">
                            @if($alert['alert_level'] === 'critical')
                                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-red-600 text-white">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-yellow-600 text-white">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="font-semibold text-sm {{ $alert['alert_level'] === 'critical' ? 'text-red-900 dark:text-red-100' : 'text-yellow-900 dark:text-yellow-100' }} truncate">
                                    {{ $alert['name'] }}
                                </h4>
                                @if($alert['alert_level'] === 'critical')
                                    <span class="flex-shrink-0 px-2 py-1 text-xs font-bold bg-red-600 text-white rounded-full">
                                        OUT OF STOCK
                                    </span>
                                @else
                                    <span class="flex-shrink-0 px-2 py-1 text-xs font-bold bg-yellow-600 text-white rounded-full">
                                        LOW
                                    </span>
                                @endif
                            </div>
                            
                            <div class="mt-1 flex items-center gap-3 text-xs">
                                <span class="font-mono {{ $alert['alert_level'] === 'critical' ? 'text-red-700 dark:text-red-300' : 'text-yellow-700 dark:text-yellow-300' }}">
                                    <strong>{{ number_format($alert['current_stock'], 0, ',', '.') }}</strong> {{ $alert['unit'] }}
                                </span>
                                <span class="{{ $alert['alert_level'] === 'critical' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                    Min: {{ number_format($alert['min_stock'], 0, ',', '.') }} {{ $alert['unit'] }}
                                </span>
                                @if($alert['percentage'] > 0)
                                    <span class="{{ $alert['alert_level'] === 'critical' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                        {{ $alert['percentage'] }}%
                                    </span>
                                @endif
                            </div>
                            
                            @if($alert['percentage'] > 0)
                                <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $alert['alert_level'] === 'critical' ? 'bg-red-600' : 'bg-yellow-600' }}" style="width: {{ min($alert['percentage'], 100) }}%"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if(count($alerts) >= 10)
                <div class="mt-4 text-center">
                    <a href="{{ route('filament.admin.resources.ingredients.index') }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                        View all alerts →
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 mb-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                    All Good!
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    No low stock alerts
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
