<!DOCTYPE html>
<html>
<head>
    <title>{{ $tenant->business_name }} - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">POS SAAS - Super Admin</h1>
            <div>
                <a href="{{ route('superadmin.dashboard') }}" class="mr-4 hover:underline">Dashboard</a>
                <a href="{{ route('superadmin.tenants.index') }}" class="mr-4 hover:underline">Tenants</a>
                <form method="POST" action="{{ route('superadmin.logout') }}" class="inline">
                    @csrf
                    <button class="bg-red-500 px-4 py-2 rounded hover:bg-red-600">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('show_credentials'))
            <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-lg font-bold text-yellow-800 mb-2">⚠️ LOGIN CREDENTIALS - COPY NOW!</h3>
                        <p class="text-sm text-yellow-700 mb-4">Send these credentials to the client. This password will only be shown once!</p>
                        
                        <div class="bg-white border border-yellow-300 rounded p-4 font-mono text-sm">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-gray-600 text-xs mb-1">SUBDOMAIN:</div>
                                    <div class="font-bold text-lg">{{ $tenant->subdomain }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-600 text-xs mb-1">EMAIL:</div>
                                    <div class="font-bold">{{ $tenant->email }}</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-yellow-200">
                                <div class="text-gray-600 text-xs mb-1">PASSWORD:</div>
                                <div class="font-bold text-xl text-red-600 bg-red-50 p-3 rounded select-all">
                                    {{ session('password') }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex gap-2">
                            <button onclick="copyCredentials()" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                                📋 Copy All
                            </button>
                            <button onclick="copyPassword()" 
                                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                                🔑 Copy Password Only
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="mb-6">
            <a href="{{ route('superadmin.tenants.index') }}" class="text-blue-600 hover:underline">← Back to Tenants</a>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">{{ $tenant->business_name }}</h2>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <div class="text-sm text-gray-600">Subdomain</div>
                    <div class="font-mono">{{ $tenant->subdomain }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Status</div>
                    <div class="font-bold">{{ $tenant->status_label }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Email</div>
                    <div>{{ $tenant->email }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Phone</div>
                    <div>{{ $tenant->phone }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Days Until Expiry</div>
                    <div class="font-bold">{{ $tenant->getDaysUntilExpiry() }} days</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Subscription Plan</div>
                    <div>{{ $tenant->subscription_plan ?? 'None' }}</div>
                </div>
            </div>

            <div class="border-t pt-4">
                <h3 class="font-bold mb-2">Configuration Status</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm text-gray-600">Midtrans:</span>
                        <span class="ml-2">{{ $tenant->hasMidtransConfigured() ? '✅' : '❌' }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">N8N:</span>
                        <span class="ml-2">{{ $tenant->hasN8NConfigured() ? '✅' : '❌' }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Firebase:</span>
                        <span class="ml-2">{{ $tenant->hasFirebaseConfigured() ? '✅' : '❌' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Login Credentials -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-lg">🔐 Admin Login Credentials</h3>
                <form method="POST" action="{{ route('superadmin.tenants.reset-password', $tenant) }}" 
                      onsubmit="return confirm('Reset password for {{ $tenant->email }}?')" class="inline">
                    @csrf
                    <button type="submit" 
                            class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 text-sm">
                        🔄 Reset Password
                    </button>
                </form>
            </div>
            
            @if(session('show_password'))
                <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h4 class="text-sm font-bold text-yellow-800 mb-2">⚠️ NEW PASSWORD - COPY NOW!</h4>
                            <p class="text-xs text-yellow-700 mb-3">This password will only be shown once. Send to tenant admin immediately.</p>
                            
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
                            
                            <div class="flex gap-2">
                                <button onclick="copyNewPassword()" 
                                        class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-xs">
                                    📋 Copy Password
                                </button>
                                <button onclick="copyLoginMessage()" 
                                        class="bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700 text-xs">
                                    💬 Copy Login Message
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="border rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="text-xs text-gray-600 mb-1">Login URL:</div>
                        <div class="font-mono text-sm bg-gray-50 p-2 rounded break-all">
                            {{ url('/admin/login') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-600 mb-1">Email:</div>
                        <div class="font-mono text-sm bg-gray-50 p-2 rounded break-all">
                            {{ $tenant->email }}
                        </div>
                    </div>
                </div>
                
                <div class="text-xs text-gray-500 bg-gray-50 p-3 rounded">
                    <strong>Note:</strong> Password was auto-generated during tenant creation or last reset. 
                    Use "Reset Password" button above to generate a new password if needed.
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="font-bold mb-4">Data Statistics</h3>
            <div class="grid grid-cols-5 gap-4">
                <div><span class="text-gray-600">Products:</span> <b>{{ $stats['products'] }}</b></div>
                <div><span class="text-gray-600">Categories:</span> <b>{{ $stats['categories'] }}</b></div>
                <div><span class="text-gray-600">Orders:</span> <b>{{ $stats['orders'] }}</b></div>
                <div><span class="text-gray-600">Tables:</span> <b>{{ $stats['tables'] }}</b></div>
                <div><span class="text-gray-600">Users:</span> <b>{{ $stats['users'] }}</b></div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-bold mb-4">Actions</h3>
            <div class="flex gap-2">
                <button onclick="document.getElementById('extendTrialModal').classList.remove('hidden')"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Extend Trial
                </button>
                <button onclick="document.getElementById('activateModal').classList.remove('hidden')"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Activate Subscription
                </button>
                @if($tenant->status != 'suspended')
                <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant) }}" class="inline">
                    @csrf
                    <button class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Suspend</button>
                </form>
                @else
                <form method="POST" action="{{ route('superadmin.tenants.reactivate', $tenant) }}" class="inline">
                    @csrf
                    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Reactivate</button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Extend Trial Modal -->
    <div id="extendTrialModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
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
    <div id="activateModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
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
                    <p class="text-xs text-gray-500 mt-1">Auto-filled from plan, can be customized</p>
                </div>
                <div class="mb-4 p-3 bg-blue-50 rounded border border-blue-200">
                    <div class="text-sm">
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Selected Plan:</span>
                            <strong id="selectedPlan">-</strong>
                        </div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Price:</span>
                            <strong id="selectedPrice">-</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Duration:</span>
                            <strong id="selectedDuration">-</strong>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        Activate
                    </button>
                    <button type="button" onclick="document.getElementById('activateModal').classList.add('hidden')"
                            class="flex-1 bg-gray-300 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateDuration() {
            const select = document.getElementById('planSelect');
            const option = select.options[select.selectedIndex];
            const duration = option.getAttribute('data-duration');
            const price = option.getAttribute('data-price');
            const planName = option.text.split(' - ')[0];
            
            if (duration) {
                document.getElementById('durationInput').value = duration;
                document.getElementById('selectedPlan').textContent = planName;
                document.getElementById('selectedPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
                document.getElementById('selectedDuration').textContent = duration + ' days';
            } else {
                document.getElementById('durationInput').value = 30;
                document.getElementById('selectedPlan').textContent = '-';
                document.getElementById('selectedPrice').textContent = '-';
                document.getElementById('selectedDuration').textContent = '-';
            }
        }
        
        function copyPassword() {
            const password = "{{ session('password') }}";
            navigator.clipboard.writeText(password).then(() => {
                alert('✅ Password copied to clipboard!\n\n' + password);
            });
        }
        
        function copyCredentials() {
            const text = `
=================================
SELAMAT! Akun POS Anda Sudah Aktif
=================================

Restaurant: {{ $tenant->business_name }}
Status: Trial ({{ $tenant->getDaysUntilExpiry() }} hari)

LOGIN CREDENTIALS:
------------------
Subdomain: {{ $tenant->subdomain }}
Email: {{ $tenant->email }}
Password: {{ session('password') }}

CARA LOGIN DI FLUTTER APP:
--------------------------
1. Buka aplikasi POS
2. Masukkan Subdomain: {{ $tenant->subdomain }}
3. Masukkan Email: {{ $tenant->email }}
4. Masukkan Password: {{ session('password') }}

PENTING:
- Silakan ganti password setelah login pertama
- Setup payment gateway (Midtrans) di menu Settings

SUPPORT:
Email: admin@possaas.com
            `.trim();
            
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ All credentials copied to clipboard!\n\nReady to send via WhatsApp or Email');
            });
        }
        
        // Copy new password (after reset)
        function copyNewPassword() {
            const password = "{{ session('new_password') }}";
            navigator.clipboard.writeText(password).then(() => {
                alert('✅ Password copied to clipboard!\n\n' + password);
            });
        }
        
        // Copy login message (after reset)
        function copyLoginMessage() {
            const text = `
🔐 PASSWORD RESET - POS Admin

Your password has been reset. Please use the new credentials below to login:

Login URL: {{ url('/admin/login') }}
Email: {{ $tenant->email }}
New Password: {{ session('new_password') }}

PENTING:
- Silakan login dan ganti password segera
- Password ini hanya ditampilkan sekali untuk keamanan

Need help? Contact support.
            `.trim();
            
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Login message copied to clipboard!\n\nReady to send via WhatsApp or Email');
            });
        }
    </script>
</body>
</html>
