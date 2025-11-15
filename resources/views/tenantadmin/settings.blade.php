<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - {{ $tenant->business_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $tenant->business_name }}</h1>
                    <p class="text-sm text-gray-600">Settings & Configuration</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('tenantadmin.dashboard') }}" 
                       class="text-gray-600 hover:text-gray-800">← Back to Dashboard</a>
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

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Settings Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Midtrans Configuration -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold">💳 Midtrans</h2>
                    @if($tenant->midtrans_merchant_id)
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">✅ Configured</span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded">⚠️ Not Set</span>
                    @endif
                </div>

                <p class="text-sm text-gray-600 mb-4">Configure payment gateway for accepting online payments</p>

                <form method="POST" action="{{ route('tenantadmin.settings.midtrans') }}" class="space-y-3">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-bold mb-1">Merchant ID</label>
                        <input type="text" name="merchant_id" 
                               value="{{ $tenant->midtrans_merchant_id }}"
                               class="w-full px-3 py-2 border rounded text-sm"
                               placeholder="M123456">
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-1">Server Key</label>
                        <input type="password" name="server_key" 
                               class="w-full px-3 py-2 border rounded text-sm"
                               placeholder="SB-Mid-server-...">
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-1">Client Key</label>
                        <input type="password" name="client_key" 
                               class="w-full px-3 py-2 border rounded text-sm"
                               placeholder="SB-Mid-client-...">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_production" value="1" 
                               {{ $tenant->midtrans_is_production ? 'checked' : '' }}
                               class="mr-2">
                        <label class="text-sm">Production Mode</label>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" 
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                            Save
                        </button>
                        @if($tenant->midtrans_merchant_id)
                            <a href="{{ route('tenantadmin.settings.delete', 'midtrans') }}" 
                               onclick="return confirm('Delete Midtrans config?')"
                               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">
                                Delete
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- N8N Webhook Configuration -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold">🔗 N8N Webhook</h2>
                    @if($tenant->n8n_webhook_url)
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">✅ Configured</span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded">⚠️ Not Set</span>
                    @endif
                </div>

                <p class="text-sm text-gray-600 mb-4">Configure webhook URL for automation workflows</p>

                <form method="POST" action="{{ route('tenantadmin.settings.n8n') }}" class="space-y-3">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-bold mb-1">Webhook URL</label>
                        <input type="url" name="webhook_url" 
                               class="w-full px-3 py-2 border rounded text-sm"
                               placeholder="https://n8n.yourdomain.com/webhook/...">
                        @if($tenant->n8n_webhook_url)
                            <p class="text-xs text-green-600 mt-1">✅ Currently configured</p>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" 
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                            Save
                        </button>
                        @if($tenant->n8n_webhook_url)
                            <a href="{{ route('tenantadmin.settings.delete', 'n8n') }}" 
                               onclick="return confirm('Delete N8N config?')"
                               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">
                                Delete
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Firebase Configuration -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold">🔔 Firebase FCM</h2>
                    @if($tenant->firebase_credentials)
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">✅ Configured</span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded">⚠️ Not Set</span>
                    @endif
                </div>

                <p class="text-sm text-gray-600 mb-4">Configure Firebase for push notifications</p>

                <form method="POST" action="{{ route('tenantadmin.settings.firebase') }}" class="space-y-3">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-bold mb-1">Service Account JSON</label>
                        <textarea name="credentials" rows="6"
                                  class="w-full px-3 py-2 border rounded text-xs font-mono"
                                  placeholder='{"type": "service_account", "project_id": "..."}'></textarea>
                        @if($tenant->firebase_credentials)
                            <p class="text-xs text-green-600 mt-1">✅ Currently configured</p>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" 
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                            Save
                        </button>
                        @if($tenant->firebase_credentials)
                            <a href="{{ route('tenantadmin.settings.delete', 'firebase') }}" 
                               onclick="return confirm('Delete Firebase config?')"
                               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">
                                Delete
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Business Information -->
        <div class="mt-6 bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold mb-4">🏢 Business Information</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="text-gray-600">Business Name</div>
                    <div class="font-semibold">{{ $tenant->business_name }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Subdomain</div>
                    <div class="font-semibold">{{ $tenant->subdomain }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Email</div>
                    <div class="font-semibold">{{ $tenant->email }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Phone</div>
                    <div class="font-semibold">{{ $tenant->phone ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Status</div>
                    <div class="font-semibold">{{ $tenant->status_label }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Subscription</div>
                    <div class="font-semibold">{{ $tenant->subscription_plan ? ucfirst($tenant->subscription_plan) : 'Trial' }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
