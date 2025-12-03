<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ $tenant->business_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $tenant->business_name }}</h1>
                    <p class="text-sm text-gray-600">{{ $tenant->subdomain }} • {{ $tenant->status_label }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('tenantadmin.settings') }}" 
                       class="text-gray-600 hover:text-gray-800 flex items-center gap-1 font-medium">
                       <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                       Settings
                    </a>
                    <form method="POST" action="{{ route('tenantadmin.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 shadow-sm animate-fade-in-down">
                {{ session('success') }}
            </div>
        @endif

        <!-- Subscription Status Alert -->
        @if($tenant->status === 'trial')
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6 shadow-sm">
                <div class="flex items-center">
                    <span class="mr-2 text-xl">⏰</span>
                    <div>
                        <strong>Trial Period:</strong> 
                        {{ $tenant->getDaysUntilExpiry() }} days remaining
                        @if($tenant->trial_ends_at)
                            (expires on {{ $tenant->trial_ends_at->format('d M Y') }})
                        @endif
                    </div>
                </div>
            </div>
        @elseif($tenant->status === 'active')
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 shadow-sm">
                <div class="flex items-center">
                    <span class="mr-2 text-xl">✅</span>
                    <div>
                        <strong>Active Subscription:</strong> 
                        {{ ucfirst($tenant->subscription_plan) }} Plan
                        ({{ $tenant->getDaysUntilExpiry() }} days remaining)
                    </div>
                </div>
            </div>
        @endif
        
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

        <!-- HYBRID SECTION: Quick Actions & Resource Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Quick Actions
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('tenantadmin.settings') }}" class="flex flex-col items-center justify-center p-3 bg-gray-50 hover:bg-blue-50 rounded-lg transition border border-gray-200 hover:border-blue-200 group">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700">Settings</span>
                    </a>
                    <a href="#" class="flex flex-col items-center justify-center p-3 bg-gray-50 hover:bg-green-50 rounded-lg transition border border-gray-200 hover:border-green-200 group">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-green-700">Add Product</span>
                    </a>
                    <a href="#" class="flex flex-col items-center justify-center p-3 bg-gray-50 hover:bg-purple-50 rounded-lg transition border border-gray-200 hover:border-purple-200 group">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-purple-700">Reports</span>
                    </a>
                    <a href="#" class="flex flex-col items-center justify-center p-3 bg-gray-50 hover:bg-orange-50 rounded-lg transition border border-gray-200 hover:border-orange-200 group">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-orange-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-orange-700">Staff</span>
                    </a>
                </div>
            </div>

            <!-- Resource Overview -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Resources
                </h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Total Products</span>
                        <span class="font-bold text-gray-900">{{ $stats['total_products'] }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Categories</span>
                        <span class="font-bold text-gray-900">{{ $stats['total_categories'] }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Tables</span>
                        <span class="font-bold text-gray-900">{{ $stats['total_tables'] }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Staff Members</span>
                        <span class="font-bold text-gray-900">{{ $stats['total_staff'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Today's Performance (Real-time) -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Today's Performance
                </h3>
                
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <div class="text-blue-200 text-sm mb-1">Sales Today</div>
                        <div class="text-3xl font-bold">Rp {{ number_format($dashboardData['today_sales']['total_sales'], 0, ',', '.') }}</div>
                        <div class="text-xs mt-2 {{ $dashboardData['today_sales']['change_percentage'] >= 0 ? 'text-green-300' : 'text-red-300' }} flex items-center">
                            <span>{{ $dashboardData['today_sales']['change_percentage'] >= 0 ? '↑' : '↓' }} {{ abs($dashboardData['today_sales']['change_percentage']) }}%</span>
                            <span class="ml-1 text-blue-200 opacity-75">vs yesterday</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-blue-200 text-sm mb-1">Orders Today</div>
                        <div class="text-3xl font-bold">{{ $dashboardData['today_sales']['total_orders'] }}</div>
                        <div class="text-xs mt-2 text-blue-200 opacity-75">
                            Avg: Rp {{ number_format($dashboardData['today_sales']['avg_order'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Breakdown Grid -->
                <div class="grid grid-cols-2 gap-4 border-t border-blue-500/30 pt-4">
                    <!-- Dine In -->
                    <div class="bg-white/10 rounded-lg p-3">
                        <div class="text-blue-200 text-xs mb-1 flex justify-between">
                            <span>🍽️ Dine In</span>
                            <span>{{ $dashboardData['today_sales']['dine_in_count'] }}</span>
                        </div>
                        <div class="font-bold text-sm">Rp {{ number_format($dashboardData['today_sales']['dine_in_sales'], 0, ',', '.') }}</div>
                        <div class="text-[10px] {{ $dashboardData['today_sales']['dine_in_change'] >= 0 ? 'text-green-300' : 'text-red-300' }}">
                            {{ $dashboardData['today_sales']['dine_in_change'] >= 0 ? '↑' : '↓' }} {{ abs($dashboardData['today_sales']['dine_in_change']) }}%
                        </div>
                    </div>
                    <!-- Takeaway -->
                    <div class="bg-white/10 rounded-lg p-3">
                        <div class="text-blue-200 text-xs mb-1 flex justify-between">
                            <span>🛍️ Takeaway</span>
                            <span>{{ $dashboardData['today_sales']['takeaway_count'] }}</span>
                        </div>
                        <div class="font-bold text-sm">Rp {{ number_format($dashboardData['today_sales']['takeaway_sales'], 0, ',', '.') }}</div>
                        <div class="text-[10px] {{ $dashboardData['today_sales']['takeaway_change'] >= 0 ? 'text-green-300' : 'text-red-300' }}">
                            {{ $dashboardData['today_sales']['takeaway_change'] >= 0 ? '↑' : '↓' }} {{ abs($dashboardData['today_sales']['takeaway_change']) }}%
                        </div>
                    </div>
                    <!-- Cash -->
                    <div class="bg-white/10 rounded-lg p-3">
                        <div class="text-blue-200 text-xs mb-1 flex justify-between">
                            <span>💵 Cash</span>
                            <span>{{ $dashboardData['today_sales']['cash_count'] }}</span>
                        </div>
                        <div class="font-bold text-sm">Rp {{ number_format($dashboardData['today_sales']['cash_sales'], 0, ',', '.') }}</div>
                        <div class="text-[10px] {{ $dashboardData['today_sales']['cash_change'] >= 0 ? 'text-green-300' : 'text-red-300' }}">
                            {{ $dashboardData['today_sales']['cash_change'] >= 0 ? '↑' : '↓' }} {{ abs($dashboardData['today_sales']['cash_change']) }}%
                        </div>
                    </div>
                    <!-- QRIS -->
                    <div class="bg-white/10 rounded-lg p-3">
                        <div class="text-blue-200 text-xs mb-1 flex justify-between">
                            <span>📱 QRIS</span>
                            <span>{{ $dashboardData['today_sales']['qris_count'] }}</span>
                        </div>
                        <div class="font-bold text-sm">Rp {{ number_format($dashboardData['today_sales']['qris_sales'], 0, ',', '.') }}</div>
                        <div class="text-[10px] {{ $dashboardData['today_sales']['qris_change'] >= 0 ? 'text-green-300' : 'text-red-300' }}">
                            {{ $dashboardData['today_sales']['qris_change'] >= 0 ? '↑' : '↓' }} {{ abs($dashboardData['today_sales']['qris_change']) }}%
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-blue-500/30">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-blue-200">Last updated</span>
                        <span class="font-mono">{{ now()->format('H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RICH ANALYTICS SECTION -->
        <div class="border-t border-gray-200 pt-8 mt-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Detailed Analytics</h3>
                
                <!-- Date Filter Form -->
                <form method="GET" action="{{ route('tenantadmin.dashboard') }}" class="flex items-center gap-2 bg-white p-1 rounded-lg border border-gray-200 shadow-sm">
                    <input type="date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}" 
                           class="border-none text-sm focus:ring-0 text-gray-600 bg-transparent">
                    <span class="text-gray-400">-</span>
                    <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}" 
                           class="border-none text-sm focus:ring-0 text-gray-600 bg-transparent">
                    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1.5 rounded-md text-sm font-medium transition">
                        Filter
                    </button>
                    @if(request('start_date') || request('end_date'))
                        <a href="{{ route('tenantadmin.dashboard') }}" class="text-red-500 hover:text-red-700 px-2 text-sm" title="Clear Filter">×</a>
                    @endif
                </form>
            </div>

            <!-- Sales Summary Cards (Filtered) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Sales -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Sales (Period)</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">
                                Rp {{ number_format($dashboardData['sales_summary']['total_sales'], 0, ',', '.') }}
                            </h3>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center text-sm">
                        @if($dashboardData['sales_summary']['change_percentage'] >= 0)
                            <span class="text-green-500 flex items-center font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                +{{ $dashboardData['sales_summary']['change_percentage'] }}%
                            </span>
                        @else
                            <span class="text-red-500 flex items-center font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                                {{ $dashboardData['sales_summary']['change_percentage'] }}%
                            </span>
                        @endif
                        <span class="text-gray-400 ml-2">vs previous period</span>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Orders</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">
                                {{ number_format($dashboardData['sales_summary']['total_orders']) }}
                            </h3>
                        </div>
                        <div class="p-2 bg-purple-50 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="text-gray-500">Avg Value: </span>
                        <span class="text-gray-800 font-medium ml-1">Rp {{ number_format($dashboardData['sales_summary']['avg_order'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Inventory Value -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Inventory Value</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">
                                Rp {{ number_format($dashboardData['inventory_stats']['total_value'], 0, ',', '.') }}
                            </h3>
                        </div>
                        <div class="p-2 bg-orange-50 rounded-lg">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="text-gray-500">{{ $dashboardData['inventory_stats']['total_items'] }} items tracked</span>
                    </div>
                </div>

                <!-- Critical Alerts -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Critical Alerts</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">
                                {{ count($dashboardData['critical_alerts']) }}
                            </h3>
                        </div>
                        <div class="p-2 bg-red-50 rounded-lg">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        @forelse(array_slice($dashboardData['critical_alerts'], 0, 2) as $alert)
                            <span class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded truncate">
                                {{ $alert['message'] }}
                            </span>
                        @empty
                            <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded">All systems normal</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Sales Trend -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Sales Trend</h3>
                    <div class="h-80">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>

                <!-- Payment Methods & Order Types -->
                <div class="space-y-6">
                    <!-- Payment Methods -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Payment Methods</h3>
                        <div class="h-48 relative">
                            <canvas id="paymentMethodChart"></canvas>
                        </div>
                        <div class="mt-4 space-y-2">
                            @foreach($dashboardData['sales_by_payment'] as $payment)
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">{{ $payment['method'] }}</span>
                                    <span class="font-medium">{{ number_format($payment['total'], 0, ',', '.') }} ({{ $payment['count'] }})</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Order Types -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Order Types</h3>
                        <div class="h-48 relative">
                            <canvas id="orderTypeChart"></canvas>
                        </div>
                        <div class="mt-4 space-y-2">
                            @foreach($dashboardData['sales_by_type'] as $type)
                                <div class="flex justify-between items-center text-sm">
                                    <span class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full" style="background-color: {{ $type['color'] }}"></span>
                                        <span class="text-gray-600">{{ $type['type'] }}</span>
                                    </span>
                                    <span class="font-medium">{{ number_format($type['total'], 0, ',', '.') }} ({{ $type['count'] }})</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Top Products & Recent Orders -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Top Products -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Top Products</h3>
                    <div class="space-y-4">
                        @forelse($dashboardData['top_products'] as $index => $product)
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full text-sm font-bold text-gray-600">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $product['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $product['quantity'] }} sold</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium text-gray-900">Rp {{ number_format($product['revenue'], 0, ',', '.') }}</div>
                                    <div class="text-xs {{ $product['change'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                        {{ $product['change'] >= 0 ? '+' : '' }}{{ $product['change'] }}%
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">No sales data yet</div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Recent Orders</h3>
                        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs text-gray-500 border-b">
                                    <th class="pb-3 font-medium">Time</th>
                                    <th class="pb-3 font-medium">Order #</th>
                                    <th class="pb-3 font-medium">Table/Customer</th>
                                    <th class="pb-3 font-medium">Items</th>
                                    <th class="pb-3 font-medium">Total</th>
                                    <th class="pb-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @forelse($dashboardData['recent_orders'] as $order)
                                    <tr class="border-b last:border-0 hover:bg-gray-50 transition">
                                        <td class="py-3 text-gray-500">{{ $order['time'] }}</td>
                                        <td class="py-3 font-medium text-gray-900">#{{ $order['order_number'] }}</td>
                                        <td class="py-3 text-gray-600">{{ $order['table'] }}</td>
                                        <td class="py-3 text-gray-600">{{ $order['items_count'] }} items</td>
                                        <td class="py-3 font-medium">Rp {{ number_format($order['grand_total'], 0, ',', '.') }}</td>
                                        <td class="py-3">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                                {{ $order['status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                                  ($order['status'] === 'cooking' ? 'bg-blue-100 text-blue-800' : 
                                                  ($order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                                {{ ucfirst($order['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-500">No recent orders</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Scripts -->
    <script>
        // Data from Controller
        const salesTrend = @json($dashboardData['sales_trend']);
        const paymentData = @json($dashboardData['sales_by_payment']);
        const orderTypeData = @json($dashboardData['sales_by_type']);

        // Sales Trend Chart
        new Chart(document.getElementById('salesTrendChart'), {
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
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: salesTrend.orders,
                        borderColor: '#9333EA',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { type: 'linear', display: true, position: 'left', grid: { display: false } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { display: false } }
                }
            }
        });

        // Payment Method Chart
        new Chart(document.getElementById('paymentMethodChart'), {
            type: 'doughnut',
            data: {
                labels: paymentData.map(d => d.method),
                datasets: [{
                    data: paymentData.map(d => d.total),
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        // Order Type Chart
        new Chart(document.getElementById('orderTypeChart'), {
            type: 'pie',
            data: {
                labels: orderTypeData.map(d => d.type),
                datasets: [{
                    data: orderTypeData.map(d => d.total),
                    backgroundColor: orderTypeData.map(d => d.color)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>
