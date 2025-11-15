<!DOCTYPE html>
<html>
<head>
    <title>Subscription Plans - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">POS SAAS - Super Admin</h1>
            <div>
                <a href="{{ route('superadmin.dashboard') }}" class="mr-4 hover:underline">Dashboard</a>
                <a href="{{ route('superadmin.tenants.index') }}" class="mr-4 hover:underline">Tenants</a>
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
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Subscription Plans</h2>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                + Create Plan
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($plans as $plan)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden {{ $plan->is_popular ? 'border-2 border-blue-500' : '' }}">
                @if($plan->is_popular)
                    <div class="bg-blue-500 text-white text-center py-1 text-sm font-bold">⭐ POPULAR</div>
                @endif
                
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-2">{{ $plan->name }}</h3>
                    <div class="text-3xl font-bold text-blue-600 mb-4">
                        Rp {{ number_format($plan->price, 0, ',', '.') }}
                    </div>
                    <div class="text-gray-600 text-sm mb-4">{{ $plan->duration_label }}</div>
                    
                    @if($plan->description)
                    <p class="text-sm text-gray-600 mb-4">{{ $plan->description }}</p>
                    @endif
                    
                    <div class="border-t pt-4 text-sm space-y-2">
                        <div>
                            <span class="text-gray-600">Products:</span>
                            <strong>{{ $plan->max_products ? number_format($plan->max_products) : 'Unlimited' }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-600">Orders/day:</span>
                            <strong>{{ $plan->max_orders_per_day ? number_format($plan->max_orders_per_day) : 'Unlimited' }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-600">Users:</span>
                            <strong>{{ $plan->max_users ? number_format($plan->max_users) : 'Unlimited' }}</strong>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t">
                        <div class="text-xs text-gray-500 mb-2">
                            Status: 
                            <span class="font-bold {{ $plan->is_active ? 'text-green-600' : 'text-red-600' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="editPlan({{ $plan->id }}, {{ json_encode($plan) }})"
                                    class="flex-1 bg-yellow-500 text-white px-3 py-2 rounded text-sm hover:bg-yellow-600">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('superadmin.plans.destroy', $plan) }}" 
                                  onsubmit="return confirm('Delete this plan?')" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button class="w-full bg-red-500 text-white px-3 py-2 rounded text-sm hover:bg-red-600">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Create Plan Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold mb-4">Create New Plan</h3>
            <form method="POST" action="{{ route('superadmin.plans.store') }}">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Plan Name *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Slug *</label>
                        <input type="text" name="slug" required class="w-full px-3 py-2 border rounded-lg" placeholder="bronze">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Price (Rp) *</label>
                        <input type="number" name="price" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Duration (days) *</label>
                        <input type="number" name="duration_days" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Duration Label *</label>
                        <input type="text" name="duration_label" required class="w-full px-3 py-2 border rounded-lg" placeholder="1 Bulan">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Display Order</label>
                        <input type="number" name="display_order" value="0" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Max Products (0 = unlimited)</label>
                        <input type="number" name="max_products" value="0" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Max Orders/Day (0 = unlimited)</label>
                        <input type="number" name="max_orders_per_day" value="0" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Max Users (0 = unlimited)</label>
                        <input type="number" name="max_users" value="0" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" checked class="mr-2">
                            <span class="text-sm font-bold">Active</span>
                        </label>
                        <label class="flex items-center mt-2">
                            <input type="checkbox" name="is_popular" value="1" class="mr-2">
                            <span class="text-sm font-bold">Popular</span>
                        </label>
                    </div>
                </div>
                <div class="col-span-2 mt-4">
                    <label class="block text-sm font-bold mb-2">Description</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                <div class="flex gap-2 mt-6">
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

    <!-- Edit Plan Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold mb-4">Edit Plan</h3>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Plan Name *</label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Slug</label>
                        <input type="text" id="edit_slug" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Price (Rp) *</label>
                        <input type="number" name="price" id="edit_price" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Duration (days) *</label>
                        <input type="number" name="duration_days" id="edit_duration_days" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Duration Label *</label>
                        <input type="text" name="duration_label" id="edit_duration_label" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Display Order</label>
                        <input type="number" name="display_order" id="edit_display_order" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Max Products</label>
                        <input type="number" name="max_products" id="edit_max_products" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Max Orders/Day</label>
                        <input type="number" name="max_orders_per_day" id="edit_max_orders_per_day" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Max Users</label>
                        <input type="number" name="max_users" id="edit_max_users" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="mr-2">
                            <span class="text-sm font-bold">Active</span>
                        </label>
                        <label class="flex items-center mt-2">
                            <input type="checkbox" name="is_popular" id="edit_is_popular" value="1" class="mr-2">
                            <span class="text-sm font-bold">Popular</span>
                        </label>
                    </div>
                </div>
                <div class="col-span-2 mt-4">
                    <label class="block text-sm font-bold mb-2">Description</label>
                    <textarea name="description" id="edit_description" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                <div class="flex gap-2 mt-6">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Update
                    </button>
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                            class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editPlan(id, plan) {
            document.getElementById('editForm').action = `/superadmin/plans/${id}`;
            document.getElementById('edit_name').value = plan.name;
            document.getElementById('edit_slug').value = plan.slug;
            document.getElementById('edit_price').value = plan.price;
            document.getElementById('edit_duration_days').value = plan.duration_days;
            document.getElementById('edit_duration_label').value = plan.duration_label;
            document.getElementById('edit_display_order').value = plan.display_order;
            document.getElementById('edit_max_products').value = plan.max_products || 0;
            document.getElementById('edit_max_orders_per_day').value = plan.max_orders_per_day || 0;
            document.getElementById('edit_max_users').value = plan.max_users || 0;
            document.getElementById('edit_is_active').checked = plan.is_active;
            document.getElementById('edit_is_popular').checked = plan.is_popular;
            document.getElementById('edit_description').value = plan.description || '';
            
            document.getElementById('editModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
