<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <x-slot name="heading">
            ✅ Pending Actions
        </x-slot>

        <div class="space-y-6 -mx-2">
            @php
                $actions = $this->getActions();
                $hasActions = count($actions['urgent']) > 0 || count($actions['important']) > 0 || count($actions['info']) > 0;
            @endphp

            @if($hasActions)
                <!-- URGENT -->
                @if(count($actions['urgent']) > 0)
                    <div>
                        <h4 class="text-base font-bold text-red-600 dark:text-red-400 mb-4 flex items-center">
                            <span class="mr-2 text-lg">🔴</span> URGENT ({{ count($actions['urgent']) }})
                        </h4>
                        <div class="space-y-3">
                            @foreach($actions['urgent'] as $action)
                                <div class="p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-lg">
                                    <div class="flex items-start space-x-4">
                                        <input type="checkbox" class="mt-1 rounded border-gray-300">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $action['title'] }}
                                            </p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $action['description'] }}
                                            </p>
                                            <a href="{{ $action['url'] }}" 
                                               class="inline-flex items-center mt-2 text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-700">
                                                Take Action →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- IMPORTANT -->
                @if(count($actions['important']) > 0)
                    <div>
                        <h4 class="text-base font-bold text-yellow-600 dark:text-yellow-400 mb-4 flex items-center">
                            <span class="mr-2 text-lg">🟡</span> IMPORTANT ({{ count($actions['important']) }})
                        </h4>
                        <div class="space-y-3">
                            @foreach($actions['important'] as $action)
                                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <div class="flex items-start space-x-4">
                                        <input type="checkbox" class="mt-1 rounded border-gray-300">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $action['title'] }}
                                            </p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $action['description'] }}
                                            </p>
                                            <a href="{{ $action['url'] }}" 
                                               class="inline-flex items-center mt-2 text-xs font-medium text-yellow-600 dark:text-yellow-400 hover:text-yellow-700">
                                                Take Action →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- INFO -->
                @if(count($actions['info']) > 0)
                    <div>
                        <h4 class="text-base font-bold text-blue-600 dark:text-blue-400 mb-4 flex items-center">
                            <span class="mr-2 text-lg">ℹ️</span> INFO ({{ count($actions['info']) }})
                        </h4>
                        <div class="space-y-3">
                            @foreach($actions['info'] as $action)
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg">
                                    <div class="flex items-start space-x-4">
                                        <input type="checkbox" class="mt-1 rounded border-gray-300">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $action['title'] }}
                                            </p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $action['description'] }}
                                            </p>
                                            <a href="{{ $action['url'] }}" 
                                               class="inline-flex items-center mt-2 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700">
                                                View →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <div class="text-4xl mb-3">✨</div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">All caught up!</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No pending actions at the moment</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
