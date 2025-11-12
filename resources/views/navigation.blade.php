<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation - Self Order System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">🍽️ Self Order System</h1>
                <p class="text-gray-600">Quick navigation to all features</p>
            </div>

            <!-- Navigation Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Order Settings -->
                <a href="{{ route('order-settings.index') }}" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="text-4xl mb-3">⚙️</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Order Settings</h3>
                    <p class="text-gray-600 text-sm">Configure discount, tax, and service charge</p>
                </a>

                <!-- Test Order -->
                <a href="{{ route('order.menu', 1) }}" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="text-4xl mb-3">🛒</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Test Order</h3>
                    <p class="text-gray-600 text-sm">Try ordering from Table 1</p>
                </a>

                <!-- Table Management -->
                <a href="{{ route('table-management.index') }}" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="text-4xl mb-3">🏢</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Table Management</h3>
                    <p class="text-gray-600 text-sm">Manage restaurant tables</p>
                </a>

                <!-- Table Categories -->
                <a href="{{ route('table-categories.index') }}" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="text-4xl mb-3">📂</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Table Categories</h3>
                    <p class="text-gray-600 text-sm">Manage table categories</p>
                </a>

                <!-- Admin Panel -->
                <a href="/admin" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="text-4xl mb-3">📊</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Admin Panel</h3>
                    <p class="text-gray-600 text-sm">Filament admin dashboard</p>
                </a>

                <!-- Documentation -->
                <a href="#" onclick="alert('Check these files:\\n- ORDER_DISCOUNT_TAX_GUIDE.md\\n- QUICK_START_DISCOUNT_TAX.md\\n- RESERVATION_SYNC_GUIDE.md')" class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="text-4xl mb-3">📖</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Documentation</h3>
                    <p class="text-gray-600 text-sm">View guides and documentation</p>
                </a>
            </div>

            <!-- Quick Test -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">🧪 Quick Test Flow:</h3>
                <ol class="list-decimal list-inside space-y-2 text-blue-800">
                    <li>Go to <strong>Order Settings</strong> → Enable Discount, Tax, Service</li>
                    <li>Go to <strong>Admin Panel</strong> → Create a test discount</li>
                    <li>Go to <strong>Test Order</strong> → Add items → Checkout</li>
                    <li>See discount dropdown and calculations work! ✨</li>
                </ol>
            </div>

            <!-- Current Settings Status -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Current Settings Status:</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-2xl mb-2">
                            @if(is_discount_enabled())
                                ✅
                            @else
                                ❌
                            @endif
                        </div>
                        <div class="text-sm font-medium text-gray-700">Discount</div>
                        <div class="text-xs text-gray-500">
                            {{ is_discount_enabled() ? 'Enabled' : 'Disabled' }}
                        </div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-2xl mb-2">
                            @if(is_tax_enabled())
                                ✅
                            @else
                                ❌
                            @endif
                        </div>
                        <div class="text-sm font-medium text-gray-700">Tax</div>
                        <div class="text-xs text-gray-500">
                            {{ is_tax_enabled() ? tax_percentage() . '%' : 'Disabled' }}
                        </div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-2xl mb-2">
                            @if(is_service_charge_enabled())
                                ✅
                            @else
                                ❌
                            @endif
                        </div>
                        <div class="text-sm font-medium text-gray-700">Service</div>
                        <div class="text-xs text-gray-500">
                            {{ is_service_charge_enabled() ? get_active_service_charge() . '%' : 'Disabled' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
