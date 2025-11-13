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
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm">{{ $tenant->subdomain }}</span>
                        </td>
                        <td class="px-6 py-4">{{ $tenant->business_name }}</td>
                        <td class="px-6 py-4">{{ $tenant->email }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($tenant->status == 'active') bg-green-100 text-green-800
                                @elseif($tenant->status == 'trial') bg-blue-100 text-blue-800
                                @elseif($tenant->status == 'expired') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $tenant->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            {{ $tenant->getDaysUntilExpiry() }} days
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
