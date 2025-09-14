<!DOCTYPE html>
<html>

<head>
    <title>Order Cart Table {{ $table->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .cart-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }

        .cart-scroll {
            max-height: 64vh;
            overflow-y: auto;
        }

        .note-box {
            resize: none;
            width: 100%;
            border-radius: 6px;
            border: 1px solid #eee;
            padding: 0.5em;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="max-w-md mx-auto bg-white shadow-lg min-h-screen flex flex-col relative pb-28">
        <!-- Header -->
        <div class="py-3 px-4 border-b font-medium text-center sticky top-0 bg-white z-10 flex items-center">
            <a href="{{ route('order.menu', $table->name) }}" class="mr-2">&larr;</a>
            <span class="flex-1">Order</span>
        </div>
        <!-- Ordered Items -->
        <div class="px-4 py-2 border-b flex items-center justify-between">
            <div class="font-semibold">Ordered Items ({{ count($cart) }})</div>
            <a href="{{ route('order.menu', $table->name) }}" class="text-xs text-blue-600 rounded py-1 px-2 border">+
                Add Item</a>
        </div>

        <!-- Cart List -->
        <div class="px-4 pt-2 cart-scroll">
            @if (count($cart))
                @foreach ($cart as $i => $item)
                    <div class="border rounded mb-3 p-3 shadow-sm bg-white relative">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-medium">{{ $item['name'] }}</div>
                            </div>
                            <!-- Tombol Hapus -->
                            <form action="{{ route('order.removeCart', [$table->name, $item['product_id']]) }}" method="post">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 text-sm">Remove</button>
                            </form>
                        </div>

                        <div class="flex items-center mt-2">
                            <div class="font-bold text-lg flex-1">Rp{{ number_format($item['price']) }}</div>
                            <!-- Tombol Minus -->
                            <form action="{{ route('order.addToCart', $table->name) }}" method="post" class="inline">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                <input type="hidden" name="qty" value="-1">
                                <button type="submit" class="px-2 text-lg">-</button>
                            </form>
                            <span class="mx-2">{{ $item['qty'] }}</span>
                            <!-- Tombol Plus -->
                            <form action="{{ route('order.addToCart', $table->name) }}" method="post" class="inline">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="px-2 text-lg">+</button>
                            </form>
                        </div>
                    </div>
                @endforeach


                {{-- @foreach ($cart as $i => $item)
                    <div class="border rounded mb-3 p-3 shadow-sm bg-white relative">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-medium">{{ $item['name'] }}</div>
                                @if (isset($item['variant']))
                                    <div class="text-xs text-gray-500">{{ $item['variant'] }}</div>
                                @endif
                                @if (isset($item['side']))
                                    <div class="text-xs text-gray-500">{{ $item['side'] }}</div>
                                @endif --}}
                                {{-- @if (!empty($item['note']))
                                    <div class="text-xs mt-1 text-gray-400 flex"><svg xmlns="http://www.w3.org/2000/svg"
                                            width="14" height="14" fill="none" viewBox="0 0 24 24"
                                            class="mr-1">
                                            <path
                                                d="M2 21v-2a4 4 0 014-4h3v-2.48A7.94 7.94 0 012 5.13 7.97 7.97 0 017.63 2c2.12 0 4.22.83 5.85 2.45A7.94 7.94 0 0122 12.37c0 2.12-.83 4.22-2.45 5.85A7.94 7.94 0 0112.37 22h-2.48v-3a4 4 0 00-4-4H3.95A1.978 1.978 0 012 17.05V21zm8-8.48V17h-3a2 2 0 00-2 2v3.05c0 .55.45 1 1 1H7.95c.56 0 1.02-.45 1.02-1V19h3c.55 0 1-.45 1-1v-3h-2.48z"
                                                fill="currentColor" />
                                        </svg>
                                        {{ $item['note'] }}</div>
                                @else
                                    <div class="text-xs text-gray-400 italic">No notes yet</div>
                                @endif --}}
                            {{-- </div>

                        </div>
                        <div class="flex items-center mt-2">
                            <div class="font-bold text-lg flex-1">Rp{{ number_format($item['price']) }}</div>
                            <!-- Qty controls -->
                            <form action="{{ route('order.addToCart', $table->name) }}" method="post" class="inline">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                <input type="hidden" name="qty" value="-1">
                                <button type="submit" class="px-2 text-lg">-</button>
                            </form>
                            <span class="mx-2">{{ $item['qty'] }}</span>
                            <form action="{{ route('order.addToCart', $table->name) }}" method="post" class="inline">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="px-2 text-lg">+</button>
                            </form>
                        </div>
                    </div>
                @endforeach --}}
            @else
                <div class="text-center text-gray-400 py-6">Empty Cart.</div>
            @endif
        </div>

        <!-- Note box global -->
        {{-- <div class="px-4 pt-2">
            <form>
                <textarea class="note-box" name="notes" rows="2" placeholder="Add notes"></textarea>
            </form>
        </div> --}}

        <!-- Payment Details -->
        <div class="p-4 mt-2">
            @php
                $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
                $rounding = round($subtotal, -2) - $subtotal;

                $total = $subtotal;
            @endphp
            <div class="border rounded-lg bg-gray-50 px-4 py-3">
                <div class="font-semibold mb-1">Payment Details</div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Subtotal ({{ count($cart) }} menu)</span>
                    <span>Rp{{ number_format($subtotal) }}</span>
                </div>

                <div class="flex justify-between font-bold mt-2 text-lg">
                    <span>Total</span>
                    <span>Rp{{ number_format($total) }}</span>
                </div>
            </div>
        </div>

        <!-- Sticky Checkout Bar -->
        <div
            class="cart-fixed bg-white border-t px-4 py-3 flex items-center justify-between max-w-md mx-auto shadow-xl">
            <div class="font-normal text-base">
                Total Payment
                <div class="font-bold text-xl mt-1">Rp{{ number_format($total) }}</div>
            </div>
            <a href="{{ route('order.checkoutForm', $table->name) }}"
                class="ml-3 bg-black text-white font-bold px-4 py-2 rounded shadow text-sm"
                id="checkout-btn">
                Continue to Payment
            </a>
        </div>
    </div>

    <!-- <script>
        // Real-time cart validation
        async function validateCart() {
            try {
                const cartData = @json($cart);
                const response = await fetch('/api/cart/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ cart: cartData })
                });
                
                const result = await response.json();
                const checkoutBtn = document.getElementById('checkout-btn');
                
                if (!result.is_valid) {
                    // Show validation errors
                    checkoutBtn.classList.add('bg-red-500');
                    checkoutBtn.classList.remove('bg-black');
                    checkoutBtn.textContent = 'Stock Issues Found';
                    checkoutBtn.style.pointerEvents = 'none';
                    
                    // Show error message
                    showValidationErrors(result.errors);
                } else {
                    // Cart is valid
                    checkoutBtn.classList.add('bg-green-500');
                    checkoutBtn.classList.remove('bg-black', 'bg-red-500');
                    checkoutBtn.textContent = 'Continue to Payment ✓';
                    checkoutBtn.style.pointerEvents = 'auto';
                }
            } catch (error) {
                console.error('Validation error:', error);
            }
        }
        
        function showValidationErrors(errors) {
            // Remove existing error messages
            document.querySelectorAll('.stock-error').forEach(el => el.remove());
            
            // Add error message at top
            const errorDiv = document.createElement('div');
            errorDiv.className = 'stock-error bg-red-50 border border-red-200 rounded p-3 mx-4 mt-2';
            errorDiv.innerHTML = `
                <div class="text-red-600 font-medium mb-1">⚠️ Stock Issues:</div>
                <div class="text-sm text-red-500">
                    ${errors.map(error => `• ${error}`).join('<br>')}
                </div>
            `;
            
            // Insert after header
            const header = document.querySelector('.border-b');
            header.insertAdjacentElement('afterend', errorDiv);
        }
        
        // Validate cart on page load
        document.addEventListener('DOMContentLoaded', validateCart);
        
        // Auto-validate every 10 seconds
        setInterval(validateCart, 10000);
        
        // Validate before checkout
        document.getElementById('checkout-btn').addEventListener('click', function(e) {
            if (this.style.pointerEvents === 'none') {
                e.preventDefault();
                alert('Please resolve stock issues before proceeding to checkout.');
            }
        });
    </script> -->
</body>

</html>
