<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">POS SAAS - Super Admin</h1>
            <div>
                <span class="mr-4">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('superadmin.logout') }}" class="inline">
                    @csrf
                    <button class="bg-red-500 px-4 py-2 rounded hover:bg-red-600">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-6">Dashboard Overview</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-gray-600 text-sm">Total Tenants</div>
                <div class="text-3xl font-bold">{{ $stats['total_tenants'] }}</div>
            </div>
            <div class="bg-green-100 p-6 rounded-lg shadow">
                <div class="text-gray-600 text-sm">Active</div>
                <div class="text-3xl font-bold text-green-600">{{ $stats['active_tenants'] }}</div>
            </div>
            <div class="bg-blue-100 p-6 rounded-lg shadow">
                <div class="text-gray-600 text-sm">Trial</div>
                <div class="text-3xl font-bold text-blue-600">{{ $stats['trial_tenants'] }}</div>
            </div>
            <div class="bg-red-100 p-6 rounded-lg shadow">
                <div class="text-gray-600 text-sm">Expired</div>
                <div class="text-3xl font-bold text-red-600">{{ $stats['expired_tenants'] }}</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Quick Actions</h3>
            </div>
            <div class="flex gap-4">
                <a href="{{ route('superadmin.tenants.index') }}" 
                   class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    Manage Tenants →
                </a>
                <a href="{{ route('superadmin.plans.index') }}" 
                   class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                    Manage Plans →
                </a>
            </div>
        </div>
    </div>
</body>
</html>
