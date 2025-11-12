<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Settings - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">⚙️ Order Settings</h1>
                        <p class="text-gray-600 mt-1">Configure discount, tax, and service charge options for customer orders</p>
                    </div>
                    <a href="{{ url('/') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        ← Back to Home
                    </a>
                </div>
            </div>

            <!-- Success Message -->
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            <!-- Settings Form -->
            <form action="{{ route('order-settings.update') }}" method="POST" class="bg-white rounded-lg shadow-md p-6">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <!-- Discount Setting -->
                    <div class="border-b pb-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="text-2xl mr-3">🎁</span>
                                    <h3 class="text-xl font-semibold text-gray-800">Discount System</h3>
                                </div>
                                <p class="text-gray-600 ml-11">
                                    Enable discount selection at checkout. Customers can choose from active discounts.
                                </p>
                            </div>
                            <div class="ml-6">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="enable_discount" value="1" class="sr-only peer" 
                                           {{ $settings['enable_discount'] == '1' ? 'checked' : '' }}>
                                    <input type="hidden" name="enable_discount" value="0">
                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700 peer-checked:text-blue-600">
                                        {{ $settings['enable_discount'] == '1' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-4 ml-11 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <strong>💡 Note:</strong> Only active discounts (not expired) will be shown to customers at checkout.
                            </p>
                        </div>
                    </div>

                    <!-- Tax Setting -->
                    <div class="border-b pb-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="text-2xl mr-3">🧾</span>
                                    <h3 class="text-xl font-semibold text-gray-800">Tax (PPN)</h3>
                                </div>
                                <p class="text-gray-600 ml-11">
                                    Apply tax percentage to all orders. Tax will be calculated automatically based on subtotal.
                                </p>
                            </div>
                            <div class="ml-6">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="enable_tax" value="1" class="sr-only peer" 
                                           {{ $settings['enable_tax'] == '1' ? 'checked' : '' }}>
                                    <input type="hidden" name="enable_tax" value="0">
                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700 peer-checked:text-green-600">
                                        {{ $settings['enable_tax'] == '1' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-4 ml-11 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-800">
                                <strong>💡 Note:</strong> Tax percentage is configured in Settings. Default: {{ tax_percentage() }}%
                            </p>
                        </div>
                    </div>

                    <!-- Service Charge Setting -->
                    <div class="pb-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="text-2xl mr-3">💼</span>
                                    <h3 class="text-xl font-semibold text-gray-800">Service Charge</h3>
                                </div>
                                <p class="text-gray-600 ml-11">
                                    Add service charge to all orders. Will be applied after tax calculation.
                                </p>
                            </div>
                            <div class="ml-6">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="enable_service_charge" value="1" class="sr-only peer" 
                                           {{ $settings['enable_service_charge'] == '1' ? 'checked' : '' }}>
                                    <input type="hidden" name="enable_service_charge" value="0">
                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700 peer-checked:text-purple-600">
                                        {{ $settings['enable_service_charge'] == '1' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-4 ml-11 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                            <p class="text-sm text-purple-800">
                                <strong>💡 Note:</strong> Service charge is managed in Tax settings (type: Service). Configure percentage in admin panel.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="mt-8 pt-6 border-t flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <strong>⚠️ Important:</strong> Changes will apply immediately to all new orders.
                    </div>
                    <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        💾 Save Settings
                    </button>
                </div>
            </form>

            <!-- Info Box -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <h4 class="font-semibold text-yellow-800 mb-2">📌 Quick Guide:</h4>
                <ul class="text-sm text-yellow-700 space-y-2">
                    <li>• <strong>Discount:</strong> Manage discounts in Admin Panel → Discounts</li>
                    <li>• <strong>Tax:</strong> Configure tax percentage in Admin Panel → Settings</li>
                    <li>• <strong>Service Charge:</strong> Manage in Admin Panel → Taxes (type: Service)</li>
                    <li>• <strong>Order Flow:</strong> Subtotal → Discount → Tax → Service → Total</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Toggle checkbox behavior
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const label = this.parentElement.querySelector('span');
                if (this.checked) {
                    this.value = '1';
                    label.textContent = 'Enabled';
                } else {
                    this.value = '0';
                    label.textContent = 'Disabled';
                }
            });
        });
    </script>
</body>
</html>
