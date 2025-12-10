@extends('layouts.order')

@section('title', 'Pembayaran QRIS - ' . $table->name)

@section('content')
<div x-data="paymentSystem()" x-init="initPayment()" class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <div class="bg-white border-b border-gray-100 px-4 py-3 flex items-center justify-center relative shadow-sm">
        <h1 class="font-bold text-lg text-gray-900">Pembayaran</h1>
    </div>

    <div class="flex-1 px-4 py-6 flex flex-col items-center">
        <!-- Amount Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 w-full text-center mb-6">
            <p class="text-sm text-gray-500 mb-1">Total Pembayaran</p>
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</h2>
            
            <div class="flex justify-center gap-2 text-xs text-gray-400 bg-gray-50 py-2 rounded-lg">
                <span>Order ID: <span class="font-mono text-gray-600 font-bold">{{ $order->code }}</span></span>
                <span>•</span>
                <span>Meja {{ $table->name }}</span>
            </div>
        </div>

        <!-- Timer -->
        <div class="mb-6 flex flex-col items-center">
            <p class="text-xs text-gray-500 mb-2">Selesaikan pembayaran dalam</p>
            <div class="bg-red-50 text-red-600 font-bold text-xl px-4 py-2 rounded-lg border border-red-100 tabular-nums"
                 x-text="timerDisplay" :class="{'text-red-600': timeLeft < 60, 'text-gray-800': timeLeft >= 60}">
                --:--
            </div>
        </div>

        <!-- QR Code Area -->
        <div class="bg-white p-6 rounded-3xl shadow-lg border border-gray-100 w-full max-w-xs aspect-square flex flex-col items-center justify-center relative overflow-hidden">
            <!-- Loading State -->
            <div x-show="!qrLoaded" class="absolute inset-0 flex items-center justify-center bg-gray-50 z-10">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-black"></div>
            </div>

            @if ($order->qr_string)
                <div class="w-full h-full flex items-center justify-center" x-init="qrLoaded = true">
                    {!! QrCode::size(250)->generate($order->qr_string) !!}
                </div>
            @elseif($order->payment_url)
                 <div class="w-full h-full flex items-center justify-center" x-init="qrLoaded = true">
                    {!! QrCode::size(250)->generate($order->payment_url) !!}
                </div>
            @else
                <p class="text-red-500 text-center text-sm">QR Code tidak tersedia.<br>Silakan hubungi kasir.</p>
            @endif
        </div>

        <p class="text-center text-sm text-gray-500 mt-6 max-w-xs">
            Scan QR di atas menggunakan aplikasi 
            <span class="font-bold text-gray-700">GoPay, OVO, Dana, ShopeePay</span> atau Mobile Banking lainnya.
        </p>

        <!-- Manual Check Button -->
        <div class="mt-auto w-full pt-8 pb-4">
            <button @click="checkStatus(true)" 
                    class="w-full bg-white border border-gray-200 text-gray-900 font-bold py-3.5 rounded-xl shadow-sm hover:bg-gray-50 active:scale-95 transition flex items-center justify-center gap-2"
                    :disabled="isChecking">
                <span x-show="!isChecking">Cek Status Pembayaran</span>
                <span x-show="isChecking" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Mengecek...
                </span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function paymentSystem() {
        return {
            timeLeft: 120, // 2 minutes
            timerDisplay: '02:00',
            isChecking: false,
            qrLoaded: false,
            checkInterval: null,
            timerInterval: null,

            initPayment() {
                // Start Countdown
                this.startTimer();

                // Auto check every 3 seconds
                this.checkInterval = setInterval(() => {
                    this.checkStatus(false);
                }, 3000);
            },

            startTimer() {
                const endTime = new Date('{{ $order->created_at }}').getTime() + (2 * 60 * 1000); // 2 mins from creation
                
                this.timerInterval = setInterval(() => {
                    const now = new Date().getTime();
                    const distance = endTime - now;
                    
                    if (distance < 0) {
                        clearInterval(this.timerInterval);
                        this.timerDisplay = "EXPIRED";
                        this.timeLeft = 0;
                        // Handle expired logic if needed
                        return;
                    }

                    this.timeLeft = Math.floor(distance / 1000);
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    this.timerDisplay = minutes.toString().padStart(2, '0') + ":" + seconds.toString().padStart(2, '0');
                }, 1000);
            },

            async checkStatus(manual = false) {
                if (manual) this.isChecking = true;

                try {
                    const response = await fetch('{{ route("order.qris.check-status", [$table->tenantIdentifier, $table->name, $order->code]) }}');
                    const data = await response.json();

                    if (data.status === 'paid') {
                        clearInterval(this.checkInterval);
                        window.location.href = data.redirect_url;
                    } else if (data.status === 'failed') {
                        clearInterval(this.checkInterval);
                        alert('Pembayaran Gagal atau Dibatalkan.');
                        window.location.href = '{{ route("order.menu", [$table->tenantIdentifier, $table->name]) }}';
                    }
                } catch (error) {
                    console.error('Check status error:', error);
                } finally {
                    if (manual) {
                        setTimeout(() => this.isChecking = false, 500);
                    }
                }
            }
        }
    }
</script>
@endpush
@endsection
