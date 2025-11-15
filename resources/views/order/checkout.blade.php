<!DOCTYPE html>
<html>
<head>
    <title>Payment - Table {{ $table->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Custom scrollbar for calculation section */
        .scrollbar-thin::-webkit-scrollbar {
            width: 4px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #9ca3af;
            border-radius: 10px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-md mx-auto bg-white shadow-lg min-h-screen flex flex-col relative">
        <!-- Header -->
        <div class="py-3 px-4 border-b font-semibold text-center sticky top-0 bg-white z-20 flex items-center">
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

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto pb-[200px]">
            <!-- Customer Information Form -->
            <form action="{{ route('order.checkout', $table->name) }}" method="post" id="checkoutForm">
                @csrf
                <div class="px-4 pt-4">
                <div class="text-sm font-semibold mb-3">Customer Information</div>
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded mb-3 text-xs">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Customer Name -->
                <div class="mb-2.5">
                    <label class="block text-xs text-gray-500 mb-1">Full Name<span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" required
                        class="block w-full px-3 py-2 text-sm border rounded bg-gray-50" placeholder="Full Name"
                        value="{{ old('customer_name') }}">
                </div>

                <!-- Phone -->
                <div class="mb-2.5">
                    <label class="block text-xs text-gray-500 mb-1">Whatsapp Number</label>
                    <input type="text" name="customer_phone"
                        class="block w-full px-3 py-2 text-sm border rounded bg-gray-50" placeholder="Whatsapp Number"
                        value="{{ old('customer_phone') }}">
                </div>

                <!-- Email -->
                <div class="mb-2.5">
                    <label class="block text-xs text-gray-500 mb-1">Email</label>
                    <input type="email" name="customer_email"
                        class="block w-full px-3 py-2 text-sm border rounded bg-gray-50" placeholder="Email"
                        value="{{ old('customer_email') }}">
                </div>

                <!-- Notes -->
                <div class="mb-2.5">
                    <label class="block text-xs text-gray-500 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border rounded bg-gray-50 px-3 py-2 text-sm min-h-[48px]"
                        placeholder="Add a note for your order (optional)">{{ old('notes') }}</textarea>
                </div>

                <!-- Table Name -->
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Table Name<span class="text-red-500">*</span></label>
                    <input type="text" name="table_name" readonly
                        class="block w-full px-3 py-2 text-sm border rounded bg-gray-200" value="{{ $table->name }}">
                </div>
            </div>

            @php
                // Get cart data
                $cart = session('cart_' . $table->name, []);
                $itemsSubtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
                
                // Get selected items from settings (ONLY if enabled!)
                $selectedDiscounts = get_selected_discounts();
                $selectedTaxes = get_selected_taxes();
                $selectedServices = get_selected_services();
                
                // Auto-select first available items (for auto-apply) ONLY if items exist
                $autoTax = $selectedTaxes->first();
                $autoService = $selectedServices->first();
                
                // Debug: Check what we got
                // dd([
                //     'discounts' => $selectedDiscounts->pluck('name'),
                //     'taxes' => $selectedTaxes->pluck('name'),
                //     'services' => $selectedServices->pluck('name'),
                // ]);
            @endphp

            </div>

            <!-- Discount Section (ONLY if items selected in Order Settings) -->
            @if($selectedDiscounts->isNotEmpty())
            <div class="px-4 py-3 bg-white border-t border-gray-200">
                <div class="font-semibold mb-2 text-sm">Apply Discount (Optional)</div>
                <select name="discount_id" id="discountSelect" form="checkoutForm" class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-gray-400 focus:border-gray-400">
                    <option value="">No Discount</option>
                    @foreach($selectedDiscounts as $discount)
                        <option value="{{ $discount->id }}" 
                                data-type="{{ $discount->type }}" 
                                data-value="{{ $discount->value }}"
                                {{ old('discount_id') == $discount->id ? 'selected' : '' }}>
                            {{ $discount->name }} 
                            ({{ $discount->type === 'percentage' ? $discount->value . '%' : 'Rp' . number_format($discount->value) }} OFF)
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Hidden inputs for auto-applied tax/service (ONLY if selected) --}}
            @if($autoTax)
                <input type="hidden" name="tax_id" value="{{ $autoTax->id }}" form="checkoutForm">
            @endif

            @if($autoService)
                <input type="hidden" name="service_id" value="{{ $autoService->id }}" form="checkoutForm">
            @endif

            <!-- Payment Method Section -->
            <div class="px-4 pt-4 pb-6">
                <div class="font-semibold mb-2 text-sm">Complete Payment</div>
                <!-- QRIS Option -->
                <label class="flex items-center border-2 border-gray-200 px-4 py-3 rounded-lg bg-gray-50 cursor-pointer hover:border-black hover:bg-gray-100 transition">
                    <input type="radio" name="payment_method" value="qris" checked form="checkoutForm" class="form-radio mr-3 text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-700" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <rect x="3" y="3" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="15" y="3" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="3" y="15" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="15" y="15" width="2" height="2" fill="currentColor" />
                        <rect x="19" y="15" width="2" height="2" fill="currentColor" />
                        <rect x="15" y="19" width="2" height="2" fill="currentColor" />
                        <rect x="19" y="19" width="2" height="2" fill="currentColor" />
                    </svg>
                    <span class="text-sm font-medium">QRIS (Scan QR Code)</span>
                </label>
        </div>

        <!-- Sticky Payment Bar with Calculation (Scrollable) -->
        <div class="bg-white border-t-2 border-gray-300 shadow-2xl">
            <!-- Scrollable Calculation Section -->
            <div class="px-4 py-2 max-h-[160px] overflow-y-auto bg-gray-50 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                <!-- Subtotal -->
                <div class="flex justify-between items-center py-2">
                    <div class="text-xs text-gray-600">Subtotal ({{ count($cart) }} items)</div>
                    <div class="text-xs font-medium" id="subtotalDisplay">Rp{{ number_format($itemsSubtotal) }}</div>
                </div>

                <!-- Discount -->
                @if($selectedDiscounts->isNotEmpty())
                <div class="flex justify-between items-center py-2 bg-green-50" id="discountRow" style="display: none;">
                    <div class="text-xs text-green-600 font-medium">Discount Applied</div>
                    <div class="text-xs text-green-600 font-bold" id="discountDisplay">- Rp0</div>
                </div>
                @endif

                <!-- Subtotal After Discount -->
                <div class="flex justify-between items-center py-2 border-t border-b border-gray-300 bg-white" id="subtotalAfterDiscountRow" style="display: none;">
                    <div class="text-xs font-semibold text-gray-800">Subtotal After Discount</div>
                    <div class="text-xs font-semibold text-gray-800" id="subtotalAfterDiscountDisplay">Rp{{ number_format($itemsSubtotal) }}</div>
                </div>

                <!-- Tax (Auto-applied) -->
                @if($autoTax)
                <div class="flex justify-between items-center py-2">
                    <div class="text-xs text-gray-600">{{ $autoTax->name }} ({{ $autoTax->value }}%)</div>
                    <div class="text-xs font-medium" id="taxDisplay">Rp0</div>
                </div>
                @endif

                <!-- Service Charge (Auto-applied) -->
                @if($autoService)
                <div class="flex justify-between items-center py-2">
                    <div class="text-xs text-gray-600">{{ $autoService->name }} ({{ $autoService->value }}%)</div>
                    <div class="text-xs font-medium" id="serviceDisplay">Rp0</div>
                </div>
                @endif
            </div>

            <!-- Total Payment (Always Visible, Compact) -->
            <div class="px-4 py-2.5 border-t-2 border-gray-300 bg-white flex justify-between items-center">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Total Payment</div>
                    <div class="font-bold text-lg text-gray-900" id="totalDisplay">Rp{{ number_format($itemsSubtotal) }}</div>
                </div>
                <button type="submit" form="checkoutForm" class="ml-3 bg-black text-white font-bold px-6 py-2.5 rounded-lg shadow-lg text-sm">
                    Pay Now
                </button>
            </div>
        </div>
    </div>

    <script>
        // Initial calculation variables
        const itemsSubtotal = {{ $itemsSubtotal }};
        const hasDiscounts = {{ $selectedDiscounts->isNotEmpty() ? 'true' : 'false' }};
        const taxPercentage = {{ $autoTax ? $autoTax->value : 0 }};
        const servicePercentage = {{ $autoService ? $autoService->value : 0 }};

        // Calculate and update display
        function calculateTotal() {
            let subtotal = itemsSubtotal;
            let discountAmount = 0;
            
            // Get selected discount (optional - customer choice)
            if (hasDiscounts) {
                const discountSelect = document.getElementById('discountSelect');
                if (discountSelect && discountSelect.value) {
                    const selectedOption = discountSelect.options[discountSelect.selectedIndex];
                    const discountType = selectedOption.getAttribute('data-type');
                    const discountValue = parseFloat(selectedOption.getAttribute('data-value'));
                    
                    if (discountType === 'percentage') {
                        discountAmount = subtotal * (discountValue / 100);
                    } else {
                        discountAmount = Math.min(discountValue, subtotal);
                    }
                    
                    // Show discount row
                    document.getElementById('discountRow').style.display = 'flex';
                    document.getElementById('discountDisplay').textContent = '- Rp' + Math.round(discountAmount).toLocaleString('id-ID');
                    
                    // Show subtotal after discount
                    document.getElementById('subtotalAfterDiscountRow').style.display = 'flex';
                } else {
                    // Hide discount rows
                    document.getElementById('discountRow').style.display = 'none';
                    document.getElementById('subtotalAfterDiscountRow').style.display = 'none';
                }
            }
            
            // Subtotal after discount
            const subtotalAfterDiscount = subtotal - discountAmount;
            if (hasDiscounts) {
                document.getElementById('subtotalAfterDiscountDisplay').textContent = 'Rp' + Math.round(subtotalAfterDiscount).toLocaleString('id-ID');
            }
            
            // Calculate tax (auto-applied if available)
            const taxAmount = subtotalAfterDiscount * (taxPercentage / 100);
            if (document.getElementById('taxDisplay')) {
                document.getElementById('taxDisplay').textContent = 'Rp' + Math.round(taxAmount).toLocaleString('id-ID');
            }
            
            // Calculate service charge (auto-applied if available)
            const serviceAmount = subtotalAfterDiscount * (servicePercentage / 100);
            if (document.getElementById('serviceDisplay')) {
                document.getElementById('serviceDisplay').textContent = 'Rp' + Math.round(serviceAmount).toLocaleString('id-ID');
            }
            
            // Total
            const total = subtotalAfterDiscount + taxAmount + serviceAmount;
            document.getElementById('totalDisplay').textContent = 'Rp' + Math.round(total).toLocaleString('id-ID');
        }
        
        // Listen for discount changes only (tax & service are auto-applied)
        if (hasDiscounts) {
            document.getElementById('discountSelect').addEventListener('change', calculateTotal);
        }
        
        // Initial calculation
        calculateTotal();
    </script>
</body>
</html>
