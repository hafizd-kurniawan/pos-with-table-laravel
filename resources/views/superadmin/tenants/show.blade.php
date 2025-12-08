<!DOCTYPE html>
<html>
<head>
    <title>{{ $tenant->business_name }} - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-bold">POS SAAS - Super Admin</h1>
                <span class="bg-blue-700 px-3 py-1 rounded-full text-xs font-mono">{{ $tenant->subdomain }}</span>
            </div>
            <div>
                <a href="{{ route('superadmin.dashboard') }}" class="mr-4 hover:text-blue-200">Dashboard</a>
                <a href="{{ route('superadmin.tenants.index') }}" class="mr-4 hover:text-blue-200 font-bold">Tenants</a>
                <form method="POST" action="{{ route('superadmin.logout') }}" class="inline">
                    @csrf
                    <button class="bg-red-500 px-4 py-2 rounded hover:bg-red-600 transition-colors text-sm font-semibold">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 shadow-sm animate-fade-in-down">
                {{ session('success') }}
            </div>
        @endif
        
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('superadmin.tenants.index') }}" class="text-blue-600 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Tenants
            </a>
            <div class="flex gap-2">
                <button onclick="document.getElementById('extendTrialModal').classList.remove('hidden')"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 shadow-sm text-sm font-semibold">
                    Extend Trial
                </button>
                <button onclick="document.getElementById('activateModal').classList.remove('hidden')"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 shadow-sm text-sm font-semibold">
                    Activate Subscription
                </button>
                @if($tenant->status != 'suspended')
                <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant) }}" class="inline" onsubmit="return confirm('Are you sure you want to suspend this tenant?');">
                    @csrf
                    <button class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 shadow-sm text-sm font-semibold">Suspend</button>
                </form>
                @else
                <form method="POST" action="{{ route('superadmin.tenants.reactivate', $tenant) }}" class="inline">
                    @csrf
                    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 shadow-sm text-sm font-semibold">Reactivate</button>
                </form>
                @endif
            </div>
        </div>

        <!-- Header & Status -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">{{ $tenant->business_name }}</h2>
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> {{ $tenant->email }}</span>
                        <span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> {{ $tenant->phone }}</span>
                        <span class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">{{ $tenant->uuid }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500 mb-1">Status</div>
                    <div class="text-lg font-bold">{{ $tenant->status_label }}</div>
                    @if($tenant->status === 'active' || $tenant->status === 'trial')
                        <div class="text-xs {{ $tenant->getDaysUntilExpiry() <= 7 ? 'text-red-600 font-bold' : 'text-green-600' }}">
                            Expires in {{ $tenant->getDaysUntilExpiry() }} days
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Config Badges -->
            <div class="mt-6 flex gap-3 border-t pt-4">
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $tenant->hasMidtransConfigured() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                    {{ $tenant->hasMidtransConfigured() ? '✅ Midtrans Active' : '⚪ Midtrans Not Configured' }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $tenant->hasN8NConfigured() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500' }}">
                    {{ $tenant->hasN8NConfigured() ? '✅ N8N Active' : '⚪ N8N Not Configured' }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $tenant->hasFirebaseConfigured() ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-500' }}">
                    {{ $tenant->hasFirebaseConfigured() ? '✅ Firebase Active' : '⚪ Firebase Not Configured' }}
                </span>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-700">📊 Analytics Period: <span class="text-blue-600">{{ $dashboardData['sales_summary']['period_label'] }}</span></h3>
            <form method="GET" action="{{ route('superadmin.tenants.show', $tenant) }}" class="flex gap-2 items-center">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">From:</span>
                    <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" 
                           class="border rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">To:</span>
                    <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" 
                           class="border rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">Filter</button>
                <a href="{{ route('superadmin.tenants.export-pdf', ['tenant' => $tenant->id, 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                   target="_blank"
                   class="bg-red-600 text-white px-4 py-1 rounded text-sm hover:bg-red-700 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export PDF
                </a>
                <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="text-gray-500 text-sm hover:underline ml-2">Reset (Today)</a>
            </form>
        </div>

        <!-- DASHBOARD WIDGETS -->
        
        <!-- Row 1: Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Sales -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Sales</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format($dashboardData['sales_summary']['total_sales'], 0, ',', '.') }}</h3>
                    </div>
                    <div class="p-2 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-center text-sm">
                    @php $change = $dashboardData['sales_summary']['change_percentage']; @endphp
                    <span class="{{ $change >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium flex items-center">
                        {{ $change >= 0 ? '↗' : '↘' }} {{ abs($change) }}%
                    </span>
                    <span class="text-gray-400 ml-2">vs previous period</span>
                </div>
            </div>

            <!-- Orders -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($dashboardData['sales_summary']['total_orders']) }}</h3>
                    </div>
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                </div>
                <div class="text-sm text-gray-500">Transactions in period</div>
            </div>

            <!-- Avg Order Value -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Avg Order Value</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format($dashboardData['sales_summary']['avg_order'], 0, ',', '.') }}</h3>
                    </div>
                    <div class="p-2 bg-yellow-50 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="text-sm text-gray-500">Per transaction</div>
            </div>

            <!-- Inventory Value -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Inventory Value</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format($dashboardData['inventory_stats']['total_value'], 0, ',', '.') }}</h3>
                    </div>
                    <div class="p-2 bg-purple-50 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="text-sm text-gray-500">{{ $dashboardData['inventory_stats']['total_items'] }} items tracked</div>
            </div>
        </div>

        <!-- Row 2: Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Sales Trend Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Sales Trend</h3>
                <div class="h-64">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Payment Methods Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Payment Methods</h3>
                <div class="h-48 mb-4">
                    <canvas id="paymentChart"></canvas>
                </div>
                <div class="space-y-2">
                    @foreach($dashboardData['sales_by_payment'] as $payment)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ $payment['method'] }}</span>
                        <span class="font-bold">Rp {{ number_format($payment['total'], 0, ',', '.') }} ({{ $payment['count'] }})</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Row 3: Order Types & Top Products -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Order Types Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Order Types</h3>
                <div class="h-48 mb-4">
                    <canvas id="orderTypeChart"></canvas>
                </div>
                <div class="space-y-2">
                    @foreach($dashboardData['sales_by_type'] as $type)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ $type['type'] }}</span>
                        <span class="font-bold">Rp {{ number_format($type['total'], 0, ',', '.') }} ({{ $type['count'] }})</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Top Products</h3>
                <div class="space-y-4">
                    @foreach($dashboardData['top_products'] as $index => $product)
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm">
                            {{ $index + 1 }}
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-900">{{ $product['name'] }}</span>
                                <span class="text-sm text-gray-500">{{ $product['quantity'] }} sold</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($product['quantity'] / max(array_column($dashboardData['top_products'], 'quantity'))) * 100 }}%"></div>
                            </div>
                        </div>
                        <div class="ml-4 text-sm font-medium text-gray-600">
                            Rp {{ number_format($product['revenue'], 0, ',', '.') }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Row 4: Recent Activity & Alerts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Recent Orders -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Orders</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table/Customer</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($dashboardData['recent_orders'] as $order)
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $order['time'] }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $order['order_number'] }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $order['table'] }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $order['items_count'] }} items</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">Rp {{ number_format($order['grand_total'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $order['status'] === 'completed' || $order['status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                           ($order['status'] === 'cooking' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($order['status']) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Critical Alerts -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Critical Alerts</h3>
                    <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded-full">{{ count($dashboardData['critical_alerts']) }}</span>
                </div>
                
                @if(empty($dashboardData['critical_alerts']))
                    <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                        <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p>All good! No critical alerts.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($dashboardData['critical_alerts'] as $alert)
                        <div class="flex items-center p-3 bg-red-50 rounded-lg border border-red-100">
                            <div class="flex-shrink-0 mr-3">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $alert['name'] }}</p>
                                <p class="text-xs text-red-600">Stock: {{ $alert['current_stock'] }} {{ $alert['unit'] }} (Min: {{ $alert['min_stock'] }})</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Admin Credentials Section (Collapsible) -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100">
            <details>
                <summary class="cursor-pointer font-bold text-lg text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    Admin Credentials & Reset
                </summary>
                <div class="mt-4 pt-4 border-t">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-700">Reset Password</h3>
                        <form method="POST" action="{{ route('superadmin.tenants.reset-password', $tenant) }}" 
                              onsubmit="return confirm('Reset password for {{ $tenant->email }}?')" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 text-sm font-semibold">
                                🔄 Generate New Password
                            </button>
                        </form>
                    </div>

                    @if(session('show_password'))
                        <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-4 mb-4 animate-pulse">
                            <div class="flex items-start">
                                <div class="ml-3 flex-1">
                                    <h4 class="text-sm font-bold text-yellow-800 mb-2">⚠️ NEW PASSWORD GENERATED</h4>
                                    <div class="bg-white border border-yellow-300 rounded p-3 font-mono text-sm mb-3">
                                        <div class="mb-2">
                                            <div class="text-gray-600 text-xs">EMAIL:</div>
                                            <div class="font-bold select-all">{{ $tenant->email }}</div>
                                        </div>
                                        <div class="pt-2 border-t border-yellow-200">
                                            <div class="text-gray-600 text-xs">NEW PASSWORD:</div>
                                            <div class="font-bold text-xl text-red-600 select-all">{{ session('new_password') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </details>
        </div>

    </div>

    <!-- Modals (Extend Trial & Activate) -->
    <!-- Extend Trial Modal -->
    <div id="extendTrialModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold mb-4">Extend Trial</h3>
            <form method="POST" action="{{ route('superadmin.tenants.extend-trial', $tenant) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Extend by (days)</label>
                    <input type="number" name="days" value="7" required min="1"
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg">Extend</button>
                    <button type="button" onclick="document.getElementById('extendTrialModal').classList.add('hidden')"
                            class="flex-1 bg-gray-300 px-4 py-2 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Activate Subscription Modal -->
    <div id="activateModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold mb-4">Activate Subscription</h3>
            <form method="POST" action="{{ route('superadmin.tenants.activate-subscription', $tenant) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Plan *</label>
                    <select name="plan_slug" id="planSelect" class="w-full px-3 py-2 border rounded-lg" required onchange="updateDuration()">
                        <option value="">-- Select Plan --</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->slug }}" 
                                data-duration="{{ $plan->duration_days }}"
                                data-price="{{ $plan->price }}">
                            {{ $plan->name }} - Rp {{ number_format($plan->price, 0, ',', '.') }} ({{ $plan->duration_label }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Duration (days) *</label>
                    <input type="number" name="duration_days" id="durationInput" value="30" required min="1"
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg">Activate</button>
                    <button type="button" onclick="document.getElementById('activateModal').classList.add('hidden')"
                            class="flex-1 bg-gray-300 px-4 py-2 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Chart.js Initialization
        
        // 1. Sales Trend Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesTrend = @json($dashboardData['sales_trend']);
        
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesTrend.labels,
                datasets: [
                    {
                        label: 'Sales (Rp)',
                        data: salesTrend.sales,
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Orders',
                        data: salesTrend.orders,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Sales (Rp)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Orders' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });

        // 2. Payment Methods Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentData = @json($dashboardData['sales_by_payment']);
        
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: paymentData.map(item => item.method),
                datasets: [{
                    data: paymentData.map(item => item.total),
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // 3. Order Types Chart
        const orderTypeCtx = document.getElementById('orderTypeChart').getContext('2d');
        const orderTypeData = @json($dashboardData['sales_by_type']);
        
        new Chart(orderTypeCtx, {
            type: 'pie',
            data: {
                labels: orderTypeData.map(item => item.type),
                datasets: [{
                    data: orderTypeData.map(item => item.total),
                    backgroundColor: orderTypeData.map(item => item.color),
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        function updateDuration() {
            const select = document.getElementById('planSelect');
            const option = select.options[select.selectedIndex];
            const duration = option.getAttribute('data-duration');
            if (duration) {
                document.getElementById('durationInput').value = duration;
            }
        }
    </script>
</body>
</html>
