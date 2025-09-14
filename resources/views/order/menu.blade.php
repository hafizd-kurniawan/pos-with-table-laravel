<!DOCTYPE html>
<html>

<head>
    <title>Menu Table {{ $table->name }} - {{ app_name() }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic Favicon -->
    @if(setting('logo_url'))
        <link rel="icon" type="image/png" href="{{ app_logo() }}">
        <link rel="shortcut icon" type="image/png" href="{{ app_logo() }}">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-default.svg') }}">
        <link rel="shortcut icon" type="image/svg+xml" href="{{ asset('images/logo-default.svg') }}">
    @endif

    <style>
        :root {
            --primary-color: {{ primary_color() }};
        }

        .active-tab {
            border-bottom: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .scroll-hide::-webkit-scrollbar {
            display: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            color: white;
            opacity: 0.9;
        }

        .btn-add {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background-color: transparent;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .text-primary {
            color: var(--primary-color);
        }

        .border-primary {
            border-color: var(--primary-color);
        }

        /* Category Filter Scroll Styles - Enhanced */
        .category-filter {
            display: flex;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
            scroll-behavior: smooth;
            padding: 0 16px;
            gap: 12px;
            -webkit-overflow-scrolling: touch; /* iOS momentum scrolling */
        }

        .category-filter::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        .category-tab {
            flex: 0 0 auto; /* Prevent shrinking and growing */
            white-space: nowrap;
            padding: 10px 20px;
            border-radius: 25px;
            background-color: #f3f4f6;
            color: #6b7280;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            user-select: none;
            min-width: fit-content;
        }

        .category-tab:hover {
            background-color: #e5e7eb;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .category-tab.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .category-tab:focus {
            outline: none;
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        .category-container {
            position: relative;
            background: white;
            border-bottom: 1px solid #e5e7eb;
        }

        .category-scroll-hint {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 40px;
            background: linear-gradient(to left, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.8) 50%, transparent 100%);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .scroll-indicator {
            color: #9ca3af;
            animation: fadeInOut 2s infinite;
        }

        @keyframes fadeInOut {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* Cart quantity bubble styles */
        .cart-bubble {
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }

        .cart-bubble.danger {
            animation: pulse-danger 1.5s infinite;
        }

        @keyframes pulse-danger {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 1px 3px rgba(55, 65, 81, 0.3);
            }
            50% { 
                transform: scale(1.1);
                box-shadow: 0 2px 6px rgba(55, 65, 81, 0.5);
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="max-w-md mx-auto bg-white shadow-lg min-h-screen flex flex-col relative">
        <!-- Header dengan Logo dan Table Info -->
        <div class="py-3 px-4 border-b text-center top-0 bg-white z-10">
            <div class="flex items-center justify-center space-x-2">
                <!-- <img src="{{ app_logo() }}"
                     alt="{{ app_name() }}"
                     class="w-6 h-6 object-contain"
                     onerror="this.style.display='none'"> -->
                <span class="text-primary text-lg font-bold">{{ app_name() }}</span>
            </div>
            <div class="text-sm text-gray-500 mt-1">Menu Table {{ $table->name }} </div>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mx-4 mt-2 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mx-4 mt-2 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Tab Kategori dengan Scroll Horizontal yang Diperbaiki -->
        <div class="category-container py-3">
            <div class="category-filter">
                @foreach ($categories as $i => $category)
                    <button class="category-tab {{ $i == 0 ? 'active' : '' }}"
                            onclick="showTab('cat-{{ $category->id }}', this)"
                            data-category="{{ $category->id }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
            <!-- Enhanced scroll hint -->
            @if($categories->count() > 3)
                <div class="category-scroll-hint" id="scrollHint">
                    <svg class="w-4 h-4 scroll-indicator" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            @endif
        </div>
        <!-- List Menu per Kategori -->
        <div class="flex-1 px-0 pb-24">
            @foreach ($categories as $i => $category)
                <div id="cat-{{ $category->id }}" class="menu-tab" style="{{ $i == 0 ? '' : 'display:none' }}">
                    @if ($category->products->count())
                        @foreach ($category->products as $product)
                            @php
                                $cart = session('cart_' . $table->name, []);
                                $qtyInCart = collect($cart)->where('product_id', $product->id)->sum('qty');
                                $availableQty = $product->stock - $qtyInCart;
                                $canAdd = $product->isAvailable() && $availableQty > 0;
                            @endphp
                            <div class="flex items-center border-b p-4 bg-white hover:bg-gray-50 transition-colors {{ !$canAdd ? 'opacity-70' : '' }}">
                                <!-- Link ke detail produk -->
                                <a href="{{ route('order.detail', [$table->name, $product->id]) }}" class="flex items-center flex-1 min-w-0">
                                    <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('images/no-image.svg') }}"
                                         class="w-20 h-20 object-cover rounded-lg mr-4 flex-shrink-0 shadow-sm"
                                         alt="{{ $product->name }}"
                                         onerror="this.src='{{ asset('images/no-image.svg') }}'">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                            @if($qtyInCart > 0)
                                                @php
                                                    $isOverStock = $qtyInCart > $product->stock;
                                                    $bubbleClass = $isOverStock ? 'cart-bubble danger' : 'cart-bubble';
                                                @endphp
                                                <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white rounded-full bg-gray-900 {{ $bubbleClass }}" 
                                                      title="{{ $isOverStock ? 'Quantity exceeds available stock!' : 'Items in cart' }}">
                                                    {{ $qtyInCart }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-primary text-sm font-semibold">Rp{{ number_format($product->price) }}</div>
                                        <div class="mt-2">
                                            <span class="text-xs text-gray-500" data-stock-id="{{ $product->id }}">Stock: {{ $product->stock }}</span>
                                        </div>
                                    </div>
                                </a>

                                <!-- Tombol Add -->
                                @if($canAdd)
                                    <form class="add-to-cart-form ml-3 flex-shrink-0" data-table="{{ $table->name }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <div data-product-id="{{ $product->id }}" class="product-card"></div>
                                        <input type="hidden" name="qty" value="1">
                                        <input type="hidden" name="category_id" value="{{ $category->id }}">
                                        <button type="submit" class="btn-add rounded-lg px-4 py-2 font-medium text-sm add-btn" data-product-name="{{ $product->name }}">
                                            <span class="btn-text">Add</span>
                                            <span class="loading-text hidden">...</span>
                                        </button>
                                    </form>
                                @else
                                    <div class="ml-3 flex-shrink-0">
                                        <span class="text-gray-400 border border-gray-300 rounded-lg px-4 py-2 font-medium text-sm cursor-not-allowed">
                                            Add
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="p-4 text-center text-gray-400">Belum ada produk</div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Cart Bar (sticky bottom) -->
        <div class="fixed bottom-0 left-0 right-0 text-white px-4 py-3 flex items-center justify-between max-w-md mx-auto btn-primary"
            style="border-radius:1rem 1rem 0 0; box-shadow: 0 -2px 8px rgba(0,0,0,0.05);">
            @php
                $cart = session('cart_' . $table->name, []);
                $total = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
                $count = collect($cart)->sum('qty');
            @endphp
            <div class="flex items-center">
                <span
                    class="bg-white rounded-full w-6 h-6 flex items-center justify-center mr-2 font-bold text-sm text-primary cart-count">{{ $count }}</span>
                <span class="font-medium">Total</span> &nbsp;
                <span class="ml-1 text-lg font-bold cart-total">Rp{{ number_format($total) }}</span>
            </div>
            <a href="{{ route('order.cart', $table->name) }}"
                class="ml-3 bg-white font-bold px-4 py-2 rounded shadow text-sm text-primary">CHECK OUT</a>
        </div>
    </div>
    <!-- Enhanced JS Tab with Better Scroll Management -->
    <script>
        let isScrolling = false;

        // Restore selected category from localStorage on page load
        document.addEventListener('DOMContentLoaded', () => {
            const selectedCategory = "{{ session('selectedCategory', 0) }}" || localStorage.getItem('selectedCategory') || '0';
            const categoryButton = document.querySelector(`.category-tab[data-category="${selectedCategory}"]`);
            if (categoryButton) {
                showTab('cat-' + selectedCategory, categoryButton);
            }
        });

        function showTab(id, buttonElement) {
            // Prevent multiple rapid calls
            if (isScrolling) return;
            isScrolling = true;

            // Hide semua tab
            document.querySelectorAll('.menu-tab').forEach(tab => {
                tab.style.display = 'none';
            });

            // Show selected tab
            const selectedTab = document.getElementById(id);
            if (selectedTab) {
                selectedTab.style.display = '';
            }

            // Update button states
            document.querySelectorAll('.category-tab').forEach(btn => {
                btn.classList.remove('active');
            });

            // Set active state
            if (buttonElement) {
                buttonElement.classList.add('active');
                scrollToActiveTab(buttonElement);
            }

            // Reset scroll lock after animation
            setTimeout(() => {
                isScrolling = false;
            }, 300);

            // Store selected category in localStorage
            if (buttonElement) {
                localStorage.setItem('selectedCategory', buttonElement.dataset.category);
            }
        }

        function scrollToActiveTab(activeButton) {
            const container = document.querySelector('.category-filter');
            if (!container || !activeButton) return;

            // Calculate scroll position to center the active button
            const containerRect = container.getBoundingClientRect();
            const buttonRect = activeButton.getBoundingClientRect();
            const containerScrollLeft = container.scrollLeft;

            const buttonCenter = activeButton.offsetLeft + (activeButton.offsetWidth / 2);
            const containerCenter = container.offsetWidth / 2;
            const targetScrollLeft = buttonCenter - containerCenter;

            // Smooth scroll to center the active button
            container.scrollTo({
                left: Math.max(0, targetScrollLeft),
                behavior: 'smooth'
            });
        }

        // Enhanced scroll management on page load
        document.addEventListener('DOMContentLoaded', function() {
            const categoryFilter = document.querySelector('.category-filter');
            const scrollHint = document.getElementById('scrollHint');

            if (categoryFilter) {
                // Check if scrolling is needed
                function checkScrollNeeded() {
                    if (scrollHint) {
                        const isScrollNeeded = categoryFilter.scrollWidth > categoryFilter.clientWidth;
                        const isAtEnd = categoryFilter.scrollLeft >= (categoryFilter.scrollWidth - categoryFilter.clientWidth - 10);

                        scrollHint.style.display = (isScrollNeeded && !isAtEnd) ? 'flex' : 'none';
                    }
                }

                // Initial check
                checkScrollNeeded();

                // Monitor scroll position
                categoryFilter.addEventListener('scroll', checkScrollNeeded);

                // Handle touch scrolling on mobile
                let startX = 0;
                let scrollLeft = 0;

                categoryFilter.addEventListener('touchstart', function(e) {
                    startX = e.touches[0].pageX - categoryFilter.offsetLeft;
                    scrollLeft = categoryFilter.scrollLeft;
                }, { passive: true });

                categoryFilter.addEventListener('touchmove', function(e) {
                    if (!startX) return;

                    const x = e.touches[0].pageX - categoryFilter.offsetLeft;
                    const walk = (x - startX) * 2; // Scroll speed multiplier
                    categoryFilter.scrollLeft = scrollLeft - walk;
                    checkScrollNeeded();
                }, { passive: true });

                categoryFilter.addEventListener('touchend', function() {
                    startX = 0;
                }, { passive: true });

                // Auto-scroll to active tab on load
                const activeTab = document.querySelector('.category-tab.active');
                if (activeTab) {
                    setTimeout(() => scrollToActiveTab(activeTab), 100);
                }
            }
        });

        // Real-time stock updates
        async function updateRealTimeStock() {
            try {
                const productIds = Array.from(document.querySelectorAll('[data-product-id]'))
                    .map(el => el.getAttribute('data-product-id'));
                
                const response = await fetch('/api/cart/stock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ product_ids: productIds })
                });
                
                const result = await response.json();
                
                if (result.success && result.stock_data) {
                    result.stock_data.forEach(stock => {
                        const stockElement = document.querySelector(`[data-stock-id="${stock.product_id}"]`);
                        if (stockElement) {
                            stockElement.textContent = `Stock: ${stock.available_stock}`;
                            
                            // Update availability
                            const productCard = stockElement.closest('.product-card');
                            if (stock.available_stock <= 0) {
                                productCard?.classList.add('opacity-50');
                                const addBtn = productCard?.querySelector('.btn-add');
                                if (addBtn) {
                                    addBtn.disabled = true;
                                    addBtn.textContent = 'Out of Stock';
                                    addBtn.classList.add('bg-gray-300', 'text-gray-500');
                                }
                            } else {
                                productCard?.classList.remove('opacity-50');
                                const addBtn = productCard?.querySelector('.btn-add');
                                if (addBtn && addBtn.textContent === 'Out of Stock') {
                                    addBtn.disabled = false;
                                    addBtn.textContent = '+';
                                    addBtn.classList.remove('bg-gray-300', 'text-gray-500');
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Stock update error:', error);
            }
        }
        
        // Update stock every 15 seconds
        setInterval(updateRealTimeStock, 15000);
        
        // Update stock on page load
        document.addEventListener('DOMContentLoaded', updateRealTimeStock);

        // AJAX Add to Cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 AJAX Cart system initializing...');
            
            // CSRF token for AJAX requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                             document.querySelector('input[name="_token"]')?.value;
            
            console.log('🔑 CSRF Token:', csrfToken ? 'Found' : 'Missing');

            // Handle all add to cart forms
            const forms = document.querySelectorAll('.add-to-cart-form');
            console.log('📋 Found', forms.length, 'add-to-cart forms');
            
            forms.forEach((form, index) => {
                console.log(`📝 Setting up form ${index + 1}:`, form);
                
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    console.log('🎯 Form submitted!', this);

                    const button = this.querySelector('.add-btn');
                    const btnText = button.querySelector('.btn-text');
                    const loadingText = button.querySelector('.loading-text');
                    const productName = button.dataset.productName;
                    const tableName = this.dataset.table;

                    console.log('📊 Form data: productName=' + productName + ', tableName=' + tableName);

                    // Disable button and show loading
                    button.disabled = true;
                    btnText.classList.add('hidden');
                    loadingText.classList.remove('hidden');

                    try {
                        const formData = new FormData(this);
                        const productId = formData.get('product_id'); // Get product ID from form
                        
                        console.log('🔍 Sending request with productId:', productId); // Debug log
                        
                        const response = await fetch(`/ajax/order/${tableName}/add-cart`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        });

                        const result = await response.json();
                        console.log('🔍 AJAX Response:', result); // Debug log
                        console.log('🛒 Full cart data:', result.cart); // Debug full cart
                        console.log('📊 Cart items array:', result.cart.items); // Debug cart items

                        if (result.success) {
                            // Show success message
                            showNotification(result.message, 'success');
                            
                            // Use correct product ID from form (not response)
                            console.log('📦 Updating bubble for product:', productId, 'with cart:', result.cart.items); // Debug log
                            updateProductBubble(productId, result.cart.items);
                            updateAllProductBubbles(result.cart.items); // Update all bubbles
                            updateCartTotal(result.cart.total, result.cart.item_count);
                            
                            // Update button to show success briefly
                            btnText.textContent = '✓';
                            setTimeout(() => {
                                btnText.textContent = 'Add';
                            }, 1000);

                        } else {
                            // Show error message
                            showNotification(result.message, 'error');
                        }

                    } catch (error) {
                        console.error('Add to cart error:', error);
                        showNotification('Failed to add item to cart. Please try again.', 'error');
                    } finally {
                        // Re-enable button
                        button.disabled = false;
                        btnText.classList.remove('hidden');
                        loadingText.classList.add('hidden');
                    }
                });
            });

            function showNotification(message, type) {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `fixed top-4 left-1/2 transform -translate-x-1/2 z-50 px-4 py-2 rounded-lg text-white font-medium text-sm transition-all duration-300 ${
                    type === 'success' ? 'bg-green-500' : 'bg-red-500'
                }`;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }

            function updateCartTotal(total, itemCount) {
                // Update cart total in bottom bar
                const cartTotalElement = document.querySelector('.cart-total');
                const cartCountElement = document.querySelector('.cart-count');
                
                if (cartTotalElement) {
                    cartTotalElement.textContent = `Rp${total.toLocaleString('id-ID')}`;
                }
                
                if (cartCountElement) {
                    cartCountElement.textContent = itemCount;
                }
            }

            function updateProductBubble(productId, cartItems) {
                console.log('🎯 updateProductBubble called for product:', productId, '(type:', typeof productId, ')');
                console.log('🛒 Cart items received:', cartItems);
                
                // Ensure productId is a string for comparison
                const targetProductId = String(productId);
                
                // Find current product in cart (compare as strings)
                const productInCart = cartItems.find(item => String(item.product_id) === targetProductId);
                const qtyInCart = productInCart ? productInCart.qty : 0;
                
                console.log('📊 Product in cart:', productInCart, 'Qty:', qtyInCart);
                
                // Find ALL bubbles for this product (there might be multiple in different categories)
                const productCards = document.querySelectorAll(`[data-product-id="${targetProductId}"]`);
                console.log('🔍 Product cards found:', productCards.length, 'for product ID:', targetProductId);
                
                productCards.forEach((productCard, index) => {
                    console.log(`🔍 Processing product card ${index + 1}:`, productCard);
                    
                    const productContainer = productCard.closest('.flex.items-center');
                    if (!productContainer) {
                        console.error('❌ Product container not found for card', index + 1);
                        return;
                    }
                    
                    // Remove existing bubble in this container
                    const existingBubble = productContainer.querySelector('.cart-bubble, span[title*="Items in cart"], span[title*="Quantity exceeds"]');
                    if (existingBubble) {
                        console.log(`🗑️ Removing existing bubble from card ${index + 1}`);
                        existingBubble.remove();
                    }
                    
                    // Add new bubble if qty > 0
                    if (qtyInCart > 0) {
                        // Find the name div where we should add the bubble
                        const nameDiv = productContainer.querySelector('.font-medium');
                        if (nameDiv && nameDiv.parentNode) {
                            const bubble = document.createElement('span');
                            bubble.className = 'inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white rounded-full bg-gray-900 cart-bubble';
                            bubble.textContent = qtyInCart;
                            bubble.title = 'Items in cart';
                            
                            // Insert bubble after the name div
                            nameDiv.parentNode.insertBefore(bubble, nameDiv.nextSibling);
                            console.log(`✅ Bubble added to card ${index + 1} with qty:`, qtyInCart);
                        } else {
                            console.error(`❌ Name div not found for bubble placement in card ${index + 1}`);
                        }
                    } else {
                        console.log(`ℹ️ No bubble needed for card ${index + 1} (qty = 0)`);
                    }
                });
            }

            // Update all product bubbles based on cart items
            function updateAllProductBubbles(cartItems) {
                console.log('🔄 Updating all product bubbles with cart:', cartItems);
                
                // Get all unique product IDs from cart
                const productIdsInCart = [...new Set(cartItems.map(item => String(item.product_id)))];
                
                // Also get all product IDs visible on page
                const allProductCards = document.querySelectorAll('[data-product-id]');
                const allProductIds = [...new Set(Array.from(allProductCards).map(card => card.getAttribute('data-product-id')))];
                
                console.log('🔍 All product IDs on page:', allProductIds);
                console.log('🛒 Product IDs in cart:', productIdsInCart);
                
                // Update bubbles for all products (both in cart and not in cart)
                allProductIds.forEach(productId => {
                    const productInCart = cartItems.find(item => String(item.product_id) === String(productId));
                    const qtyInCart = productInCart ? productInCart.qty : 0;
                    
                    console.log(`📝 Product ${productId}: qty in cart = ${qtyInCart}`);
                    
                    // Update all instances of this product
                    const productCards = document.querySelectorAll(`[data-product-id="${productId}"]`);
                    productCards.forEach((productCard) => {
                        const productContainer = productCard.closest('.flex.items-center');
                        if (!productContainer) return;
                        
                        // Remove existing bubble
                        const existingBubble = productContainer.querySelector('.cart-bubble, span[title*="Items in cart"], span[title*="Quantity exceeds"]');
                        if (existingBubble) {
                            existingBubble.remove();
                        }
                        
                        // Add new bubble if qty > 0
                        if (qtyInCart > 0) {
                            const nameDiv = productContainer.querySelector('.font-medium');
                            if (nameDiv && nameDiv.parentNode) {
                                const bubble = document.createElement('span');
                                bubble.className = 'inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white rounded-full bg-gray-900 cart-bubble';
                                bubble.textContent = qtyInCart;
                                bubble.title = 'Items in cart';
                                
                                // Insert bubble after the name div
                                nameDiv.parentNode.insertBefore(bubble, nameDiv.nextSibling);
                            }
                        }
                    });
                });
                
                console.log('✅ All bubbles updated');
            }
        });
    </script>
</body>

</html>
