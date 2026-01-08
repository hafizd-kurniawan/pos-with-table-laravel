@extends('layouts.order')

@section('title', 'Checkout - ' . $table->name)

@section('content')
<div x-data="checkoutSystem()" x-init="initCheckout()" class="pb-32">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white border-b border-gray-100 px-4 py-3 flex items-center">
        <a href="{{ route('order.menu', [$table->tenantIdentifier, $table->name]) }}" class="p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-full transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="font-bold text-lg text-gray-900 ml-2">Konfirmasi Pesanan</h1>
    </div>

    <form action="{{ route('order.checkout', [$table->tenantIdentifier, $table->name]) }}" method="POST" id="checkoutForm" @submit="saveCustomerData()">
        @csrf
        
        <!-- Order Summary (Collapsible) -->
        <div class="px-4 py-4 border-b border-gray-100 bg-white">
            <button type="button" @click="showSummary = !showSummary" class="flex justify-between items-center w-full">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-800">Ringkasan Pesanan</span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full" x-text="cart.length + ' item'"></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 transition-transform duration-200" :class="{'rotate-180': showSummary}" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            
            <div x-show="showSummary" x-collapse class="mt-4 space-y-3">
                <template x-for="(item, index) in cart" :key="index">
                    <div class="flex justify-between items-start py-2 border-b border-gray-50 last:border-0">
                        <div class="flex gap-3 flex-1">
                            <div class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center text-xs font-bold text-gray-600 shrink-0" x-text="item.qty + 'x'"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800" x-text="item.name"></p>
                                
                                <!-- Base Price (Calculated) -->
                                <p class="text-xs text-gray-500" 
                                   x-text="formatRupiah(Number(item.price) - (item.addons ? item.addons.reduce((sum, a) => sum + Number(a.price), 0) : 0))">
                                </p>

                                <!-- Addons Detail List -->
                                <template x-if="item.addons && item.addons.length > 0">
                                    <div class="mt-1 space-y-0.5">
                                        <template x-for="(addon, addonIndex) in item.addons" :key="addonIndex">
                                            <div class="flex justify-between text-xs text-gray-500 pl-2 border-l-2 border-gray-200">
                                                <span x-text="addon.name"></span>
                                                <span x-text="'+ ' + formatRupiah(addon.price)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <p x-show="item.note" class="text-xs text-gray-500 italic mt-1" x-text="'Catatan: ' + item.note"></p>
                            </div>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 ml-2" x-text="formatRupiah(item.price * item.qty)"></p>
                    </div>
                </template>

                <!-- Discount Selection -->
                @if($selectedDiscounts->count() > 0)
                <div class="pt-3 mt-3 border-t border-dashed border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Diskon</label>
                    <select name="discount_id" x-model="selectedDiscountId" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-black">
                        <option value="">-- Tidak ada diskon --</option>
                        @foreach($selectedDiscounts as $discount)
                            <option value="{{ $discount->id }}" data-type="{{ $discount->type }}" data-value="{{ $discount->value }}">
                                {{ $discount->name }} ({{ $discount->type == 'percentage' ? $discount->value . '%' : 'Rp' . number_format($discount->value) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Calculation Breakdown -->
                <div class="border-t border-dashed border-gray-200 pt-3 mt-3 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-medium" x-text="formatRupiah(calculateSubtotal())"></span>
                    </div>

                    <div x-show="calculateDiscount() > 0" class="flex justify-between text-sm text-green-600">
                        <span>Diskon</span>
                        <span class="font-medium" x-text="'- ' + formatRupiah(calculateDiscount())"></span>
                    </div>
                    
                    @if($autoTax)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>{{ $autoTax->name }} ({{ $autoTax->value }}%)</span>
                        <span class="font-medium" x-text="formatRupiah(calculateTax())"></span>
                    </div>
                    @endif

                    @if($autoService)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>{{ $autoService->name }} ({{ $autoService->value }}%)</span>
                        <span class="font-medium" x-text="formatRupiah(calculateService())"></span>
                    </div>
                    @endif
                    
                    <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-100">
                        <span>Total</span>
                        <span x-text="formatRupiah(calculateTotal())"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="px-4 py-6 border-b border-gray-100 bg-white">
            <h2 class="font-bold text-gray-800 mb-4">Informasi Pemesan</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" x-model="customer.name" required
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-black focus:border-transparent transition"
                           placeholder="Masukkan nama anda">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp (Opsional)</label>
                    <input type="tel" name="customer_phone" x-model="customer.phone" 
                           @input.debounce.500ms="checkMember()"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-black focus:border-transparent transition"
                           placeholder="08xxxxxxxxxx">
                    
                    <!-- Member Info Display -->
                    <div x-show="member.found" class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-blue-800" x-text="'Halo, ' + member.name + '!'"></p>
                            <p class="text-xs text-blue-600" x-text="'Poin Anda: ' + member.points"></p>
                        </div>
                        <div class="bg-blue-200 text-blue-800 text-xs px-2 py-1 rounded-full font-bold">Member</div>
                    </div>
                    
                    <p class="text-xs text-gray-500 mt-1" x-show="!member.found">Kami akan mengirimkan notifikasi status pesanan.</p>
                </div>
            </div>
        </div>

        <!-- Order Notes -->
        <div class="px-4 py-6 border-b border-gray-100 bg-white">
            <h2 class="font-bold text-gray-800 mb-4">Catatan Pesanan</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                <textarea name="notes" rows="2"
                          class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-black focus:border-transparent transition"
                          placeholder="Contoh: Jangan terlalu pedas, minta sendok plastik..."></textarea>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="px-4 py-6 bg-white mb-20">
            <h2 class="font-bold text-gray-800 mb-4">Metode Pembayaran</h2>
            
            <div class="grid grid-cols-1 gap-3">
                <label class="relative flex items-center p-4 rounded-xl border-2 cursor-pointer transition-all duration-200"
                       :class="paymentMethod === 'qris' ? 'border-black bg-gray-50' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio" name="payment_method" value="qris" x-model="paymentMethod" class="absolute opacity-0">
                    <div class="flex-1 flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-lg border border-gray-100 flex items-center justify-center p-1">
                            <!-- QRIS Icon Placeholder -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM6 6h6v6H6V6zm12 0h-6v6h6V6zm-6 12H6v-6h6v6z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">QRIS</p>
                            <p class="text-xs text-gray-500">Scan QR Code (GoPay, OVO, Dana, dll)</p>
                        </div>
                    </div>
                    <div x-show="paymentMethod === 'qris'" class="w-5 h-5 bg-black rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </label>
            </div>
        </div>

        <!-- Sticky Bottom Payment Bar -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg z-40 max-w-md mx-auto">
            <div class="flex justify-between items-center mb-3">
                <span class="text-gray-600 text-sm">Total Pembayaran</span>
                <span class="font-bold text-lg text-gray-900" x-text="formatRupiah(calculateTotal())"></span>
            </div>
            <button type="submit" class="w-full bg-black text-white font-bold py-3.5 rounded-xl shadow-lg hover:bg-gray-800 active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!customer.name">
                Bayar Sekarang
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function checkoutSystem() {
        return {
            cart: @json($cart),
            showSummary: true,
            paymentMethod: 'qris',
            customer: {
                name: '',
                phone: ''
            },
            member: {
                found: false,
                name: '',
                points: 0
            },
            
            // Settings from backend
            taxPercentage: {{ $autoTax ? $autoTax->value : 0 }},
            servicePercentage: {{ $autoService ? $autoService->value : 0 }},
            discounts: @json($selectedDiscounts),
            selectedDiscountId: '',

            initCheckout() {
                // Load from LocalStorage
                const savedName = localStorage.getItem('customer_name');
                const savedPhone = localStorage.getItem('customer_phone');
                
                if (savedName) this.customer.name = savedName;
                if (savedPhone) {
                    this.customer.phone = savedPhone;
                    this.checkMember(); // Auto check if phone exists
                }
            },
            
            async checkMember() {
                if (!this.customer.phone || this.customer.phone.length < 9) {
                    this.member.found = false;
                    return;
                }
                
                try {
                    const response = await fetch('{{ route('order.check-member', $table->tenantIdentifier) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ phone: this.customer.phone })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.member.found = true;
                        this.member.name = data.member.name;
                        this.member.points = data.member.points;
                        
                        // Auto-fill name if empty
                        if (!this.customer.name) {
                            this.customer.name = data.member.name;
                        }
                    } else {
                        this.member.found = false;
                    }
                } catch (error) {
                    console.error('Error checking member:', error);
                    this.member.found = false;
                }
            },

            saveCustomerData() {
                localStorage.setItem('customer_name', this.customer.name);
                localStorage.setItem('customer_phone', this.customer.phone);
            },

            calculateSubtotal() {
                return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
            },

            calculateDiscount() {
                if (!this.selectedDiscountId) return 0;
                
                const discount = this.discounts.find(d => d.id == this.selectedDiscountId);
                if (!discount) return 0;

                const subtotal = this.calculateSubtotal();
                
                let discountAmount = 0;
                if (discount.type === 'percentage') {
                    discountAmount = subtotal * (discount.value / 100);
                } else {
                    discountAmount = Math.min(discount.value, subtotal);
                }
                return Math.round(discountAmount);
            },

            calculateTax() {
                const subtotalAfterDiscount = this.calculateSubtotal() - this.calculateDiscount();
                return Math.round(Math.max(0, subtotalAfterDiscount * (this.taxPercentage / 100)));
            },

            calculateService() {
                const subtotalAfterDiscount = this.calculateSubtotal() - this.calculateDiscount();
                return Math.round(Math.max(0, subtotalAfterDiscount * (this.servicePercentage / 100)));
            },

            calculateTotal() {
                const subtotal = this.calculateSubtotal();
                const discount = this.calculateDiscount();
                const tax = this.calculateTax();
                const service = this.calculateService();
                
                return Math.max(0, subtotal - discount + tax + service);
            },

            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { 
                    style: 'currency', 
                    currency: 'IDR', 
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0 
                }).format(number);
            }
        }
    }
</script>
@endpush
@endsection
