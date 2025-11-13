<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ $tenant->business_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $tenant->business_name }}</h1>
                    <p class="text-sm text-gray-600">{{ $tenant->subdomain }} • {{ $tenant->status_label }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('tenantadmin.settings') }}" 
                       class="text-gray-600 hover:text-gray-800">⚙️ Settings</a>
                    <form method="POST" action="{{ route('tenantadmin.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Subscription Status Alert -->
        @if($tenant->status === 'trial')
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <span class="mr-2">⏰</span>
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
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <span class="mr-2">✅</span>
                    <div>
                        <strong>Active Subscription:</strong> 
                        {{ ucfirst($tenant->subscription_plan) }} Plan
                        ({{ $tenant->getDaysUntilExpiry() }} days remaining)
                    </div>
                </div>
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-500 text-sm font-semibold">Products</div>
                <div class="text-3xl font-bold text-blue-600">{{ $stats['total_products'] }}</div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-500 text-sm font-semibold">Categories</div>
                <div class="text-3xl font-bold text-green-600">{{ $stats['total_categories'] }}</div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-500 text-sm font-semibold">Tables</div>
                <div class="text-3xl font-bold text-purple-600">{{ $stats['total_tables'] }}</div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-500 text-sm font-semibold">Total Orders</div>
                <div class="text-3xl font-bold text-orange-600">{{ $stats['total_orders'] }}</div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-500 text-sm font-semibold">Staff</div>
                <div class="text-3xl font-bold text-indigo-600">{{ $stats['total_staff'] }}</div>
            </div>
        </div>

        <!-- Today's Revenue -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-sm opacity-90">Today's Revenue</div>
                    <div class="text-3xl font-bold">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</div>
                    <div class="text-sm opacity-90 mt-1">{{ $todayOrders->count() }} orders</div>
                </div>
                <div class="text-5xl opacity-20">💰</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Recent Orders -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-bold mb-4">Recent Orders</h2>
                @if($recentOrders->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentOrders as $order)
                            <div class="border-b pb-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-semibold">Order #{{ $order->id }}</div>
                                        <div class="text-sm text-gray-600">
                                            {{ $order->table ? $order->table->name : 'Takeaway' }}
                                            • {{ $order->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-blue-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
                                        <span class="text-xs px-2 py-1 rounded 
                                            @if($order->status === 'completed') bg-green-100 text-green-800
                                            @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">No orders yet</p>
                @endif
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-bold mb-4">Low Stock Alert</h2>
                @if($lowStockProducts->count() > 0)
                    <div class="space-y-3">
                        @foreach($lowStockProducts as $product)
                            <div class="border-b pb-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold">{{ $product->name }}</div>
                                        <div class="text-sm text-gray-600">{{ $product->category->name ?? '-' }}</div>
                                    </div>
                                    <div>
                                        <span class="px-3 py-1 rounded text-sm font-bold
                                            @if($product->stock < 5) bg-red-100 text-red-800
                                            @else bg-yellow-100 text-yellow-800
                                            @endif">
                                            {{ $product->stock }} left
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">✅ All products in stock</p>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('tenantadmin.settings') }}" 
                   class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg text-center transition">
                    <div class="text-2xl mb-2">⚙️</div>
                    <div class="font-semibold">Settings</div>
                </a>
                <a href="#" 
                   class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg text-center transition">
                    <div class="text-2xl mb-2">📦</div>
                    <div class="font-semibold">Products</div>
                </a>
                <a href="#" 
                   class="bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg text-center transition">
                    <div class="text-2xl mb-2">📊</div>
                    <div class="font-semibold">Reports</div>
                </a>
                <a href="#" 
                   class="bg-orange-500 hover:bg-orange-600 text-white p-4 rounded-lg text-center transition">
                    <div class="text-2xl mb-2">👥</div>
                    <div class="font-semibold">Staff</div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
