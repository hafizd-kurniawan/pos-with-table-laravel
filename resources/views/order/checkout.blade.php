<!DOCTYPE html>
<html>

<head>
    <title>Payment - Table {{ $table->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body class="bg-gray-100">
    <div class="max-w-md mx-auto bg-white shadow-lg min-h-screen flex flex-col pb-32 relative">
        <!-- Header -->
        <div class="py-3 px-4 border-b font-semibold text-center sticky top-0 bg-white z-10 flex items-center">
            <a href="{{ route('order.cart', $table->name) }}" class="mr-2">&larr;</a>
            <span class="flex-1">Payment</span>
        </div>

        <!-- Order Type -->
        <div class="px-4 pt-4">
            <label class="block text-xs text-gray-500 mb-1 font-medium">Order Type</label>
            <div class="flex items-center border rounded px-3 py-2 bg-gray-50 text-sm">
                <span class="flex-1 font-semibold">Dine In</span>
            </div>
        </div>

        <!-- Customer Information -->
        <form action="{{ route('order.checkout', $table->name) }}" method="post" class="flex-1">
            @csrf
            <div class="px-4 pt-5">
                <div class="text-base font-semibold mb-2">Customer Information</div>
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded mb-3">
                        <ul class="list-disc list-inside text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Full Name<span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <svg width="18" height="18" fill="none">
                                <path
                                    d="M12 12c2.67 0 8 1.34 8 4v2H4v-2c0-2.66 5.33-4 8-4zM8 9c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z"
                                    fill="#bbb" />
                            </svg>
                        </span>
                        <input type="text" name="customer_name" required
                            class="block w-full pl-10 pr-3 py-2 border rounded bg-gray-50" placeholder="Full Name"
                            value="{{ old('customer_name') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Whatsapp Number <span
                            class="text-xs text-gray-400">(for send order summary)</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <svg width="18" height="18" fill="none">
                                <path
                                    d="M6.62 10.79a15.054 15.054 0 006.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1v3.5c0 .55-.45 1-1 1C7.61 21 2 15.39 2 8.5 2 7.95 2.45 7.5 3 7.5H6.5c.55 0 1 .45 1 1 0 1.24.2 2.45.57 3.57.12.35.03.75-.24 1.02l-2.2 2.2z"
                                    fill="#bbb" />
                            </svg>
                        </span>
                        <input type="text" name="customer_phone"
                            class="block w-full pl-10 pr-3 py-2 border rounded bg-gray-50" placeholder="Whatsapp Number"
                            value="{{ old('customer_phone') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Send Receipt to Email</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <svg width="18" height="18" fill="none">
                                <path
                                    d="M2 4a2 2 0 012-2h12a2 2 0 012 2v1.18l-8 5.2-8-5.2V4zm0 2.23V16a2 2 0 002 2h12a2 2 0 002-2V6.23l-8 5.2-8-5.2z"
                                    fill="#bbb" />
                            </svg>
                        </span>
                        <input type="email" name="customer_email"
                            class="block w-full pl-10 pr-3 py-2 border rounded bg-gray-50" placeholder="Email"
                            value="{{ old('customer_email') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border rounded bg-gray-50 px-3 py-2 min-h-[48px]"
                        placeholder="Add a note for your order (optional)">{{ old('notes') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Table Name<span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <svg width="18" height="18" fill="none">
                                <circle cx="9" cy="9" r="7" stroke="#bbb" stroke-width="2" />
                                <rect x="4" y="13" width="10" height="2" rx="1" fill="#bbb" />
                            </svg>
                        </span>
                        <input type="text" name="table_name" readonly
                            class="block w-full pl-10 pr-3 py-2 border rounded bg-gray-50" value="{{ $table->name }}">
                    </div>
                </div>
            </div>
            <!-- Payment Section -->
            <div class="px-4 pt-6">
                <div class="font-semibold mb-2">Complete Payment</div>
                <!-- QRIS Option -->
                <label class="flex items-center border px-4 py-3 rounded-lg mb-2 bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="qris" checked class="form-radio mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-gray-700" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <rect x="3" y="3" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="15" y="3" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="3" y="15" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="15" y="15" width="2" height="2" fill="currentColor" />
                        <rect x="19" y="15" width="2" height="2" fill="currentColor" />
                        <rect x="15" y="19" width="2" height="2" fill="currentColor" />
                        <rect x="19" y="19" width="2" height="2" fill="currentColor" />
                    </svg>
                    QRIS (Scan QR Code)
                </label>

                <!-- >>>> GOPAY -->
                {{-- <label class="flex items-center border px-4 py-3 rounded-lg mb-2 bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="gopay" class="form-radio mr-3">
                    <img src="https://logos-world.net/wp-content/uploads/2023/03/GoPay-Logo.png"
                        alt="GoPay" class="h-5 mr-2">
                    <span>GoPay (QR/Deeplink)</span>
                </label> --}}

                <!-- 🔹 Cash -->
                {{-- <label class="flex items-center border px-4 py-3 rounded-lg mb-2 bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="cash" class="form-radio mr-3">
                    <span class="font-medium text-gray-700">Cash / Bayar di kasir</span>
                </label> --}}
            </div>

            <!-- Sticky Payment Bar -->
            <div
                class="fixed bottom-0 left-0 right-0 bg-white border-t px-4 py-3 flex flex-col max-w-md mx-auto shadow-xl">
                @php
                    // Get cart data
                    $cart = session('cart_' . $table->name, []);
                    $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
                    
                    // Get tax percentage from settings
                    $taxPercentage = tax_percentage();
                    $taxAmount = $subtotal * ($taxPercentage / 100);
                    $total = $subtotal + $taxAmount;
                @endphp
                <div class="flex justify-between items-center mb-2">
                    <div class="text-sm text-gray-600">Subtotal</div>
                    <div class="text-sm">Rp{{ number_format($subtotal) }}</div>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <div class="text-sm text-gray-600">PPN ({{ number_format($taxPercentage, 0) }}%)</div>
                    <div class="text-sm">Rp{{ number_format($taxAmount) }}</div>
                </div>
                <div class="flex justify-between items-center">
                    <div>
                        <div class="text-xs uppercase tracking-wide">Total Payment</div>
                        <div class="font-bold text-xl">Rp{{ number_format($total) }}</div>
                    </div>
                    <button type="submit"
                        class="ml-3 bg-black text-white font-bold px-6 py-2 rounded shadow text-base">Pay</button>
                </div>
            </div>
        </form>
    </div>
</body>

</html>
