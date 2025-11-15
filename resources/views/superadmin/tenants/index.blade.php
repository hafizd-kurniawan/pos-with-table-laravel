<!DOCTYPE html>
<html>
<head>
    <title>Manage Tenants - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">POS SAAS - Super Admin</h1>
            <div>
                <a href="{{ route('superadmin.dashboard') }}" class="mr-4 hover:underline">Dashboard</a>
                <span class="mr-4">{{ auth()->user()->name }}</span>
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

        {{-- QUICK STATS ALERTS --}}
        @php
            $suspendedCount = $tenants->where('status', 'suspended')->count();
            $expiredCount = $tenants->where('status', 'expired')->count();
            $expiringTrialCount = $tenants->where('status', 'trial')->filter(function($t) {
                return $t->getDaysUntilExpiry() <= 3 && $t->getDaysUntilExpiry() > 0;
            })->count();
        @endphp

        @if($suspendedCount > 0 || $expiredCount > 0 || $expiringTrialCount > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @if($suspendedCount > 0)
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    🚫 <strong class="text-2xl">{{ $suspendedCount }}</strong> Suspended
                                </p>
                                <p class="text-xs text-red-600">Cannot login</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($expiredCount > 0)
                    <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-orange-800">
                                    ⏰ <strong class="text-2xl">{{ $expiredCount }}</strong> Expired
                                </p>
                                <p class="text-xs text-orange-600">Cannot login</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($expiringTrialCount > 0)
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">
                                    ⚠️ <strong class="text-2xl">{{ $expiringTrialCount }}</strong> Expiring Soon
                                </p>
                                <p class="text-xs text-yellow-600">≤3 days left</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">All Tenants</h2>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                + Create Tenant
            </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subdomain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Business Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($tenants as $tenant)
                    <tr class="@if($tenant->status === 'suspended') bg-red-50 @elseif($tenant->status === 'expired') bg-orange-50 @endif">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm">{{ $tenant->subdomain }}</span>
                        </td>
                        <td class="px-6 py-4">
                            {{ $tenant->business_name }}
                            @if($tenant->status === 'suspended' || $tenant->status === 'expired')
                                <span class="ml-2 text-xs font-bold text-red-600">🚫 BLOCKED</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $tenant->email }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($tenant->status == 'active') bg-green-100 text-green-800
                                @elseif($tenant->status == 'trial') bg-blue-100 text-blue-800
                                @elseif($tenant->status == 'expired') bg-red-100 text-red-800
                                @elseif($tenant->status == 'suspended') bg-gray-800 text-white
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $tenant->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($tenant->status === 'suspended' || $tenant->status === 'expired')
                                <span class="font-bold text-red-600">N/A</span>
                            @else
                                <span class="@if($tenant->getDaysUntilExpiry() <= 3) font-bold text-red-600 @endif">
                                    {{ $tenant->getDaysUntilExpiry() }} days
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('superadmin.tenants.show', $tenant) }}" 
                               class="text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tenants->links() }}
        </div>
    </div>

    <!-- Create Tenant Modal -->
    <div id="createModal" class="@if($errors->any()) @else hidden @endif fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold mb-4">Create New Tenant</h3>
            
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="text-sm list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form method="POST" action="{{ route('superadmin.tenants.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Subdomain *</label>
                    <input type="text" name="subdomain" value="{{ old('subdomain') }}" required
                           class="w-full px-3 py-2 border rounded-lg @error('subdomain') border-red-500 @enderror" 
                           placeholder="resto-a">
                    @error('subdomain')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Business Name *</label>
                    <input type="text" name="business_name" value="{{ old('business_name') }}" required
                           class="w-full px-3 py-2 border rounded-lg @error('business_name') border-red-500 @enderror">
                    @error('business_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 border rounded-lg @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Phone *</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required
                           class="w-full px-3 py-2 border rounded-lg @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Address</label>
                    <textarea name="address" rows="2"
                           class="w-full px-3 py-2 border rounded-lg">{{ old('address') }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Trial Days *</label>
                    <input type="number" name="trial_days" value="30" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Create
                    </button>
                    <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                            class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
