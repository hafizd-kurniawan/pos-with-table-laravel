@extends('layouts.order')

@section('title', 'Menu - ' . $table->name)

@section('content')
<div x-data="orderSystem()" x-init="initSystem()" class="pb-24">
    <!-- Sticky Header & Search -->
    <div class="sticky top-0 z-30 bg-white shadow-sm">
        <!-- Brand & Table Info -->
        <div class="px-4 py-3 relative flex items-center justify-center border-b border-gray-100">
            <div class="text-center">
                <h1 class="font-bold text-lg text-gray-900">{{ app_name() }}</h1>
                <p class="text-xs text-gray-500">Table {{ $table->name }}</p>
            </div>
            <!-- Search Toggle -->
            <button @click="toggleSearch()" class="absolute right-4 p-2 text-gray-600 hover:bg-gray-100 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </div>

        <!-- Search Bar (Collapsible) -->
        <div x-show="showSearch" x-transition.origin.top class="px-4 py-2 bg-gray-50 border-b border-gray-100">
            <input type="text" x-model="searchQuery" placeholder="Cari menu..." 
                class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent text-sm">
        </div>

        <!-- Category Tabs (No longer Spy Scroll) -->
        <div class="flex overflow-x-auto no-scrollbar py-2 px-4 gap-2 bg-white border-b border-gray-100" id="category-nav">
            <template x-for="category in categories" :key="category.id">
                <button @click="selectCategory(category.id)"
                    :class="activeCategory === category.id ? 'bg-black text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="whitespace-nowrap px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-300 flex-shrink-0">
                    <span x-text="category.name"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Menu List -->
    <div class="px-4 py-4 space-y-8 min-h-[50vh]">
        @foreach ($categories as $category)
            <div id="cat-{{ $category->id }}" class="category-section" x-show="shouldShowCategory({{ $category->id }})"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                
                <!-- Category Title (Only show when searching, otherwise redundant with tabs) -->
                <h2 x-show="searchQuery.length > 0" class="font-bold text-lg text-gray-800 mb-3">{{ $category->name }}</h2>
                
                <div class="space-y-4">
                    @foreach ($category->products as $product)
                        <div class="flex gap-4 bg-white p-3 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow"
                             x-show="productMatchesSearch('{{ strtolower($product->name) }}')">
                            
                            <!-- Product Image -->
                            <div @click="openProductModal({{ $product->id }})" class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden cursor-pointer relative">
                                <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('images/no-image.svg') }}" 
                                     class="w-full h-full object-cover" 
                                     loading="lazy"
                                     onerror="this.src='{{ asset('images/no-image.svg') }}'">
                                @if(!$product->isAvailable() || $product->stock <= 0)
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                        <span class="text-white text-xs font-bold px-2 py-1 bg-red-500 rounded">Habis</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Product Info -->
                            <div class="flex-1 flex flex-col justify-between">
                                <div @click="openProductModal({{ $product->id }})" class="cursor-pointer">
                                    <h3 class="font-semibold text-gray-900 line-clamp-2">{{ $product->name }}</h3>
                                    <p class="text-sm font-bold text-gray-900 mt-1">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Stok: <span x-text="getStock({{ $product->id }}, {{ $product->stock }})"></span></p>
                                </div>

                                <!-- Add Button -->
                                <div class="flex justify-end mt-2">
                                    @if($product->isAvailable() && $product->stock > 0)
                                        <div x-show="getCartQty({{ $product->id }}) > 0" class="flex items-center bg-gray-100 rounded-lg p-1">
                                            <button @click="updateCart({{ $product->id }}, -1)" class="w-7 h-7 flex items-center justify-center bg-white rounded-md shadow-sm text-gray-600 hover:text-red-500 active:scale-95 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <span class="w-8 text-center font-semibold text-sm" x-text="getCartQty({{ $product->id }})"></span>
                                            <button @click="updateCart({{ $product->id }}, 1)" class="w-7 h-7 flex items-center justify-center bg-black rounded-md shadow-sm text-white hover:bg-gray-800 active:scale-95 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <button x-show="getCartQty({{ $product->id }}) === 0" 
                                                @click="updateCart({{ $product->id }}, 1)"
                                                class="px-4 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold text-gray-700 shadow-sm hover:border-black hover:text-black transition active:scale-95">
                                            Add
                                        </button>
                                    @else
                                        <button disabled class="px-3 py-1 bg-gray-100 text-gray-400 text-xs rounded-lg cursor-not-allowed">
                                            Habis
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        
        <!-- Empty Search State -->
        <div x-show="searchQuery.length > 0 && products.filter(p => productMatchesSearch(p.name.toLowerCase())).length === 0" 
             class="text-center py-12 text-gray-500">
            <p>Tidak ada menu yang cocok dengan pencarian Anda.</p>
        </div>
    </div>

    <!-- Floating Cart Bar -->
    <div x-show="cartTotalQty > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         class="fixed bottom-4 left-4 right-4 z-40">
        <a href="{{ route('order.cart', [$table->tenantIdentifier, $table->name]) }}" 
           class="bg-black text-white rounded-xl shadow-xl p-4 flex justify-between items-center active:scale-95 transition-transform">
            <div class="flex items-center gap-3">
                <div class="bg-white text-black w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm" x-text="cartTotalQty"></div>
                <div class="flex flex-col">
                    <span class="text-xs text-gray-300">Total</span>
                    <span class="font-bold text-sm" x-text="formatRupiah(cartTotalPrice)"></span>
                </div>
            </div>
            <div class="flex items-center font-semibold text-sm">
                Lihat Keranjang
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </a>
    </div>

    <!-- Product Modal -->
    <div x-show="isModalOpen" 
         class="fixed inset-0 z-50 flex items-end justify-center sm:items-center p-0 sm:p-4"
         x-cloak>
        <!-- Backdrop -->
        <div x-show="isModalOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeModal()"
             class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>

        <!-- Modal Content -->
        <div x-show="isModalOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full sm:scale-95 sm:opacity-0"
             x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100"
             x-transition:leave-end="translate-y-full sm:scale-95 sm:opacity-0"
             class="relative bg-white w-full max-w-md rounded-t-2xl sm:rounded-2xl overflow-hidden shadow-2xl max-h-[90vh] flex flex-col">
            
            <div class="overflow-y-auto flex-1">
                <!-- Image -->
                <div class="h-64 bg-gray-100 relative">
                    <img :src="activeProduct?.image_url || '{{ asset('images/no-image.svg') }}'" 
                         class="w-full h-full object-cover">
                    <button @click="closeModal()" class="absolute top-4 right-4 bg-white rounded-full p-2 shadow-md hover:bg-gray-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-5">
                    <h2 class="text-xl font-bold text-gray-900" x-text="activeProduct?.name"></h2>
                    <p class="text-lg font-bold text-black mt-1" x-text="formatRupiah(activeProduct?.price || 0)"></p>
                    <p class="text-gray-600 text-sm mt-4 leading-relaxed" x-text="activeProduct?.description || 'Tidak ada deskripsi'"></p>
                    
                    <!-- Notes Input -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                        <textarea x-model="productNote" rows="2" placeholder="Contoh: Pedas, Tanpa Bawang..." 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-black focus:border-transparent"></textarea>
                    </div>

                    <!-- Addons Selection -->
                    <div class="mt-6" x-show="activeProduct?.addons && activeProduct.addons.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tambahan (Opsional)</label>
                        <div class="space-y-2">
                            <template x-for="addon in activeProduct?.addons" :key="addon.id">
                                <label class="flex items-center justify-between p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="selectedAddons.includes(addon.id) ? 'border-black bg-gray-50' : ''">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" :value="addon.id" x-model="selectedAddons" class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black">
                                        <span class="text-sm font-medium text-gray-900" x-text="addon.name"></span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900" x-text="'+ ' + formatRupiah(addon.price)"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="p-4 border-t border-gray-100 bg-white">
                <div class="flex gap-3">
                    <!-- Qty Control -->
                    <div class="flex items-center bg-gray-100 rounded-xl px-2">
                        <button @click="modalQty > 1 ? modalQty-- : null" class="p-2 text-gray-600 hover:text-black disabled:opacity-50" :disabled="modalQty <= 1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <span class="w-8 text-center font-bold" x-text="modalQty"></span>
                        <button @click="modalQty++" class="p-2 text-gray-600 hover:text-black">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <!-- Add Button -->
                    <button @click="addToCartFromModal()" 
                            class="flex-1 bg-black text-white font-bold py-3 rounded-xl shadow-lg hover:bg-gray-800 active:scale-95 transition flex justify-between px-6">
                        <span>Tambah Pesanan</span>
                        <span x-text="formatRupiah(modalTotalPrice)"></span>
                    </button>
                </div>
                
                <!-- Secondary Action: Add without Extras (if addons exist but none selected) -->
                <div class="mt-3" x-show="activeProduct?.addons && activeProduct.addons.length > 0 && selectedAddons.length === 0">
                    <button @click="addToCartFromModal()" 
                            class="w-full py-3 rounded-xl border-2 border-gray-200 text-gray-600 font-bold hover:border-black hover:text-black transition">
                        Tambah Tanpa Tambahan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function orderSystem() {
        return {
            categories: @json($categories),
            cart: @json(session('cart_' . $table->tenantIdentifier . '_' . $table->name, [])),
            products: [], // Will be populated from categories
            
            // State
            activeCategory: {{ $categories->first()->id ?? 0 }},
            searchQuery: '',
            showSearch: false,
            isModalOpen: false,
            activeProduct: null,
            modalQty: 1,
            productNote: '',
            selectedAddons: [], // Array of addon IDs
            
            // Computed
            get cartTotalQty() {
                return this.cart.reduce((total, item) => total + item.qty, 0);
            },
            
            get cartTotalPrice() {
                return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
            },

            get modalTotalPrice() {
                if (!this.activeProduct) return 0;
                let basePrice = this.activeProduct.price;
                let addonsPrice = 0;
                
                this.selectedAddons.forEach(addonId => {
                    const addon = this.activeProduct.addons.find(a => a.id == addonId);
                    if (addon) addonsPrice += parseFloat(addon.price);
                });
                
                return (basePrice + addonsPrice) * this.modalQty;
            },

            initSystem() {
                // Flatten products for easier access
                this.categories.forEach(cat => {
                    cat.products.forEach(prod => {
                        this.products.push({
                            id: prod.id,
                            name: prod.name,
                            price: prod.price,
                            stock: prod.stock,
                            image_url: prod.image ? '/storage/' + prod.image : null,
                            description: prod.description,
                            category_id: cat.id,
                            addons: prod.addons || [] // Ensure addons exist
                        });
                    });
                });
            },

            toggleSearch() {
                this.showSearch = !this.showSearch;
                if (this.showSearch) {
                    setTimeout(() => document.querySelector('input[x-model="searchQuery"]').focus(), 100);
                } else {
                    this.searchQuery = '';
                }
            },

            productMatchesSearch(productName) {
                if (!this.searchQuery) return true;
                return productName.includes(this.searchQuery.toLowerCase());
            },

            shouldShowCategory(categoryId) {
                if (this.searchQuery) {
                    // If searching, show category if it has matching products
                    const category = this.categories.find(c => c.id === categoryId);
                    return category.products.some(p => p.name.toLowerCase().includes(this.searchQuery.toLowerCase()));
                }
                // Tab Logic: Only show active category
                return this.activeCategory === categoryId;
            },

            selectCategory(id) {
                this.activeCategory = id;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                // Scroll nav to active button
                const nav = document.getElementById('category-nav');
                const btn = nav.querySelector(`button:nth-child(${this.categories.findIndex(c => c.id === id) + 1})`);
                if (btn) {
                    nav.scrollTo({ left: btn.offsetLeft - 20, behavior: 'smooth' });
                }
            },

            // Cart Logic
            getCartQty(productId) {
                // Sum qty of all items with this product_id (regardless of variants)
                return this.cart.filter(i => i.product_id == productId).reduce((sum, i) => sum + i.qty, 0);
            },

            getStock(productId, initialStock) {
                // In a real app, you might want to fetch real-time stock here
                // For now, we subtract cart qty from initial stock
                const inCart = this.getCartQty(productId);
                return Math.max(0, initialStock - inCart);
            },

            async updateCart(productId, change, note = null, addons = [], skipModal = false) {
                // If product has addons and we are adding (change > 0) without specifying addons, OPEN MODAL
                // BUT, if called from modal (addons specified) or removing, proceed.
                // Simplified: If adding from list view and product has addons, open modal.
                if (change > 0 && addons.length === 0 && !skipModal) {
                    const product = this.products.find(p => p.id == productId);
                    if (!product) {
                        console.error('Product not found for ID:', productId);
                        return;
                    }
                    if (product && product.addons && product.addons.length > 0) {
                        this.openProductModal(productId);
                        return;
                    }
                }

                // Proceed with update
                // Note: For products with addons, each unique combination is a separate cart item.
                // This simple updateCart logic needs to be smarter or we rely on backend to merge?
                // For now, let's assume backend handles "add new item" vs "update existing".
                // But wait, updateCart logic below assumes merging by product_id only.
                
                // REFACTOR: If we have addons, we ALWAYS add a new item or find exact match.
                // Current logic:
                // const existingItemIndex = this.cart.findIndex(i => i.product_id == productId);
                
                // We need to match addons too.
                // Let's assume addons is array of IDs.
                
                const addonsKey = addons.sort().join(',');
                
                const existingItemIndex = this.cart.findIndex(i => 
                    i.product_id == productId && 
                    (i.addons || []).sort().join(',') === addonsKey &&
                    (i.note || '') === (note || '')
                );

                let newQty = 0;
                if (existingItemIndex > -1) {
                    newQty = this.cart[existingItemIndex].qty + change;
                    if (newQty <= 0) {
                        this.cart.splice(existingItemIndex, 1);
                    } else {
                        this.cart[existingItemIndex].qty = newQty;
                    }
                } else if (change > 0) {
                    const product = this.products.find(p => p.id == productId);
                    
                    if (!product) {
                        console.error('Product not found for ID:', productId);
                        return;
                    }

                    // Calculate price with addons
                    let finalPrice = product.price;
                    addons.forEach(addonId => {
                        const addon = product.addons.find(a => a.id == addonId);
                        if (addon) finalPrice += parseFloat(addon.price);
                    });

                    this.cart.push({
                        product_id: productId,
                        name: product.name,
                        price: finalPrice,
                        qty: change,
                        note: note || '',
                        addons: addons,
                        // Helper for display
                        addon_names: addons.map(id => {
                            const a = product.addons.find(x => x.id == id);
                            return a ? `${a.name} (+${this.formatRupiah(a.price)})` : '';
                        }).join(', ')
                    });
                }

                // Sync with Backend
                try {
                    const formData = new FormData();
                    formData.append('product_id', productId);
                    formData.append('qty', change); // Send delta
                    if (note) formData.append('note', note);
                    if (addons.length > 0) {
                        addons.forEach(id => formData.append('addons[]', id));
                    }
                    
                    // If we are updating specific item (e.g. removing), we might need more info?
                    // For now, backend `addToCart` handles "add". 
                    // But "remove" (-1) is tricky if we don't specify WHICH item to remove.
                    // If change is -1, we should probably pass the cart_index or similar?
                    // Or just disable -1 from list view for complex items?
                    // For simplicity: List view +/- only works for simple items.
                    // Complex items must be managed in Cart page.
                    
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
                    console.error('Cart sync error:', error);
                    alert('Gagal menambahkan ke keranjang. Silakan coba lagi. Error: ' + error.message);
                }
            },

            // Modal Logic
            openProductModal(productId) {
                this.activeProduct = this.products.find(p => p.id == productId);
                this.modalQty = 1;
                this.productNote = '';
                this.selectedAddons = [];
                this.isModalOpen = true;
                document.body.style.overflow = 'hidden'; // Prevent background scroll
            },

            closeModal() {
                this.isModalOpen = false;
                setTimeout(() => {
                    this.activeProduct = null;
                    document.body.style.overflow = '';
                }, 300);
            },

            addToCartFromModal() {
                if (!this.activeProduct) return;
                
                // Pass true for skipModal to prevent re-opening modal
                this.updateCart(this.activeProduct.id, this.modalQty, this.productNote, this.selectedAddons, true);
                this.closeModal();
            },

            // Utilities
            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
            }
        }
    }
</script>
@endpush
@endsection
