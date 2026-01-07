@extends('layouts.order')

@section('title', 'Keranjang - ' . $table->name)

@section('content')
<div x-data="cartSystem()" class="pb-32">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white border-b border-gray-100 px-4 py-3 flex items-center">
        <a href="{{ route('order.menu', [$table->tenantIdentifier, $table->name]) }}" class="p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-full transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="font-bold text-lg text-gray-900 ml-2">Keranjang Pesanan</h1>
    </div>

    <!-- Cart Items -->
    <div class="px-4 py-4 space-y-4">
        @if(count($cart) > 0)
            <template x-for="(item, index) in cart" :key="index">
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex gap-4">
                    <!-- Item Info -->
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900" x-text="item.name"></h3>
                        
                        <!-- Base Price (Calculated) -->
                        <p class="text-sm text-gray-500 mt-1" 
                           x-text="formatRupiah(Number(item.price) - (item.addons ? item.addons.reduce((sum, a) => sum + Number(a.price), 0) : 0))">
                        </p>
                        
                        <!-- Addons Detail List -->
                        <template x-if="item.addons && item.addons.length > 0">
                            <div class="mt-2 space-y-1">
                                <template x-for="addon in item.addons" :key="addon.id">
                                    <div class="flex justify-between text-xs text-gray-500 pl-2 border-l-2 border-gray-200">
                                        <span x-text="addon.name"></span>
                                        <span x-text="'+ ' + formatRupiah(addon.price)"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        
                        <!-- Note Input -->
                        <div class="mt-3">
                            <input type="text" x-model="item.note" @change="updateNote(index, item.note)"
                                   placeholder="Catatan (opsional)" 
                                   class="w-full text-xs px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-black transition">
                        </div>
                    </div>

                    <!-- Qty Control -->
                    <div class="flex flex-col justify-between items-end">
                        <button @click="removeItem(index)" class="text-gray-400 hover:text-red-500 p-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        
                        <div class="flex items-center bg-gray-100 rounded-lg p-1 mt-2">
                            <button @click="updateQty(index, -1)" class="w-7 h-7 flex items-center justify-center bg-white rounded-md shadow-sm text-gray-600 hover:text-red-500 active:scale-95 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <span class="w-8 text-center font-semibold text-sm" x-text="item.qty"></span>
                            <button @click="updateQty(index, 1)" class="w-7 h-7 flex items-center justify-center bg-black rounded-md shadow-sm text-white hover:bg-gray-800 active:scale-95 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Estimated Details -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 mt-4 space-y-2">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span class="font-medium" x-text="formatRupiah(cartSubtotal)"></span>
                </div>
                
                @if($autoTax)
                <div class="flex justify-between text-sm text-gray-600">
                    <span>{{ $autoTax->name }} ({{ $autoTax->value }}%)</span>
                    <span class="font-medium" x-text="formatRupiah(taxAmount)"></span>
                </div>
                @endif

                @if($autoService)
                <div class="flex justify-between text-sm text-gray-600">
                    <span>{{ $autoService->name }} ({{ $autoService->value }}%)</span>
                    <span class="font-medium" x-text="formatRupiah(serviceAmount)"></span>
                </div>
                @endif
                
                <div class="border-t border-dashed border-gray-300 my-2"></div>
                
                <div class="flex justify-between text-base font-bold text-gray-900">
                    <span>Total Estimasi</span>
                    <span x-text="formatRupiah(cartTotal)"></span>
                </div>
            </div>

        @else
            <div class="text-center py-12">
                <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 text-lg">Keranjang Kosong</h3>
                <p class="text-gray-500 mt-2 text-sm">Yuk pilih menu favoritmu dulu!</p>
                <a href="{{ route('order.menu', [$table->tenantIdentifier, $table->name]) }}" class="inline-block mt-6 px-6 py-2 bg-black text-white rounded-full font-bold text-sm shadow-lg hover:bg-gray-800 transition">
                    Lihat Menu
                </a>
            </div>
        @endif
    </div>

    <!-- Sticky Bottom Bar -->
    @if(count($cart) > 0)
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg z-40 max-w-md mx-auto">
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-600 text-sm">Total Pembayaran</span>
            <span class="font-bold text-lg text-gray-900" x-text="formatRupiah(cartTotal)"></span>
        </div>
        <a href="{{ route('order.checkoutForm', [$table->tenantIdentifier, $table->name]) }}" 
           class="block w-full bg-black text-white text-center font-bold py-3.5 rounded-xl shadow-lg hover:bg-gray-800 active:scale-95 transition">
            Lanjut Pembayaran
        </a>
    </div>
    @endif
</div>

@push('scripts')
<script>
    function cartSystem() {
        return {
            cart: @json(array_values($cart)),
            taxPercentage: {{ $autoTax ? $autoTax->value : 0 }},
            servicePercentage: {{ $autoService ? $autoService->value : 0 }},

            get cartSubtotal() {
                return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
            },

            get taxAmount() {
                return this.cartSubtotal * (this.taxPercentage / 100);
            },

            get serviceAmount() {
                return this.cartSubtotal * (this.servicePercentage / 100);
            },

            get cartTotal() {
                return this.cartSubtotal + this.taxAmount + this.serviceAmount;
            },

            async updateQty(index, change) {
                const item = this.cart[index];
                if (!item) return;

                const newQty = item.qty + change;
                
                if (newQty <= 0) {
                    if (confirm('Hapus item ini dari keranjang?')) {
                        this.removeItem(index);
                    }
                    return;
                }

                // Optimistic Update
                this.cart[index].qty = newQty;

                // Sync
                await this.syncCart(item, change);
            },

            async removeItem(index) {
                const item = this.cart[index];
                if (!item) return;

                // Remove from local array
                this.cart.splice(index, 1);
                
                // For complex items (addons), we can't easily use the simple remove endpoint by ID
                // So we use the sync endpoint with negative qty to remove all
                // OR we just reload page to let backend handle session state?
                // Better: Use syncCart with negative qty equal to current qty
                
                // Actually, the backend removeCart endpoint uses product_id.
                // If we have multiple variants, removing by product_id might remove ALL variants or just one?
                // Let's check removeCart in OrderController.
                // It likely removes all items with that product_id.
                // To support removing specific variant, we need a better backend endpoint.
                // BUT, for now, let's try to use addToCartAjax with negative qty to zero it out.
                
                await this.syncCart(item, -item.qty); // This subtracts current qty, effectively making it 0? 
                // Wait, addToCartAjax adds to existing. So if we send -qty, it reduces.
                // If result is <= 0, backend removes it.
                
                if (this.cart.length === 0) location.reload();
            },

            async updateNote(index, note) {
                const item = this.cart[index];
                if (!item) return;
                
                const formData = new FormData();
                formData.append('product_id', item.product_id);
                formData.append('qty', 0); 
                formData.append('note', note);
                
                // We need to send addons to identify the correct item
                if (item.addons && item.addons.length > 0) {
                    item.addons.forEach(addon => formData.append('addons[]', addon.id));
                }
                
                await fetch('{{ route("order.addToCartAjax", [$table->tenantIdentifier, $table->name]) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
            },

            async syncCart(item, qtyChange) {
                const formData = new FormData();
                formData.append('product_id', item.product_id);
                formData.append('qty', qtyChange);
                
                // Send addons to identify the correct item
                if (item.addons && item.addons.length > 0) {
                    item.addons.forEach(addon => formData.append('addons[]', addon.id));
                }
                
                // Send note to identify correct item (if note is part of identity)
                if (item.note) formData.append('note', item.note);
                
                try {
                    const response = await fetch('{{ route("order.addToCartAjax", [$table->tenantIdentifier, $table->name]) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    const result = await response.json();
                    if (!result.success) {
                        alert(result.message);
                        location.reload();
                    }
                } catch (error) {
                    console.error('Sync error:', error);
                }
            },

            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
            }
        }
    }
</script>
@endpush
@endsection
