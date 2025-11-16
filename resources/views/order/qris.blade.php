<!DOCTYPE html>
<html>

<head>
    <title>QR Payment - Table {{ $table->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body class="bg-gray-100">
    <div class="max-w-md mx-auto bg-white shadow-lg min-h-screen flex flex-col pb-8">
        <!-- Header -->
        <div class="py-3 px-4 border-b font-semibold text-center sticky top-0 bg-white z-10 flex items-center">
            <a href="{{ route('order.menu', [$table->tenantIdentifier, $table->name]) }}" class="mr-2">&larr;</a>
            <span class="flex-1">
                {{ strtoupper($order->payment_method) }} Payment
            </span>
        </div>

        <!-- Informasi Order -->
        <div class="px-4 pt-5 pb-2 text-center">
            <div class="font-semibold text-lg mb-1">Total Payment</div>
            <div class="font-bold text-2xl mb-3 text-gray-800">
                Rp{{ number_format($order->total_amount) }}
            </div>
            <div class="text-xs text-gray-500 mb-2">
                Table <b>{{ $table->name }}</b> | Order ID:
                <span class="font-mono">{{ $order->code }}</span>
            </div>

            <!-- Countdown Timer -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">
                <div class="text-sm text-red-600 font-medium mb-1">Payment expires in:</div>
                <div id="countdown-timer" class="text-lg font-bold text-red-700">02:00</div>
                <div class="text-xs text-red-500 mt-1">
                    Order will be automatically cancelled after timeout
                </div>
            </div>

            <!-- Payment Status Indicator -->
            <div id="payment-status" class="">
                <!-- <div class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                    <span class="text-sm text-blue-600">Checking payment status...</span>
                </div> -->
            </div>
        </div>

        <!-- QR Code Section -->
        <div class="flex flex-col items-center pt-0">
            @php
                $meta = is_array($order->meta) ? $order->meta : json_decode($order->meta, true);
                $isMobile = preg_match('/Mobile|Android|iPhone|iPad/i', request()->header('User-Agent'));
            @endphp

            @if ($order->payment_method === 'gopay' && $isMobile && !empty($meta['deeplink_url']))
                {{-- 🔹 Jika mobile dan ada deeplink_url → redirect otomatis --}}
                <script>
                    window.location.href = "{{ $meta['deeplink_url'] }}";
                </script>
                <p class="text-center text-gray-600 text-sm">
                    Mengalihkan ke aplikasi GoPay... <br>
                    Jika tidak otomatis, <a href="{{ $meta['deeplink_url'] }}" class="text-green-600 font-semibold">
                        klik di sini
                    </a>
                </p>
            @else
                <img src='{{ asset('storage/qris.png') }}'
                    alt="QRIS logo with a QR code in the center"
                    class="w-32 h-20 object-contain mx-auto" />
                <div class="bg-white rounded-lg p-4 shadow border mb-3">
                    @if ($order->qr_string)
                        {{-- QRIS QR string --}}
                        {!! QrCode::size(240)->generate($order->qr_string) !!}
                    @elseif($order->payment_method === 'gopay' && !empty($meta['qr_code_url']))
                        {{-- GoPay QR dari URL --}}
                        <img src="{{ $meta['qr_code_url'] }}" class="w-64 h-64" alt="QR Code GoPay">
                    @elseif($order->payment_url)
                        {{-- fallback QR dari payment_url --}}
                        {!! QrCode::size(240)->generate($order->payment_url) !!}
                    @else
                        <p class="text-red-500">QR Code tidak tersedia</p>
                    @endif
                </div>

                <div class="flex items-center justify-center mb-1">
                    <span class="text-gray-600">
                        @if ($order->payment_method === 'gopay')
                            Scan QR via GoPay / klik tombol di bawah
                        @else
                            Scan QR via aplikasi e-wallet / banking (QRIS)
                        @endif
                    </span>
                </div>
            @endif
        </div>

        <!-- Cek Status -->
        <form method="POST" action="{{ route('order.qris.confirm', [$table->tenantIdentifier, $table->name, $order->code]) }}"
            class="text-center px-4 mt-1 mb-1">
            @csrf
            <button type="submit"
                class="w-full bg-black text-white font-bold py-2 rounded-md mt-1 text-base shadow hover:bg-gray-900 transition">
                Sudah Bayar / Cek Status Pembayaran
            </button>
        </form>

        @if(app()->environment(['local', 'development']))
        <!-- DEBUG Button untuk testing -->
        <div class="text-center px-4 mt-2 mb-1">
            <button onclick="forcePaymentSuccess()" 
                class="w-full bg-green-600 text-white font-bold py-2 rounded-md text-sm shadow hover:bg-green-700 transition">
                🚀 DEBUG: Force Payment Success
            </button>
        </div>
        @endif

        @if(app()->environment(['local', 'development']))
        <!-- DEBUG Button untuk testing -->
        <div class="text-center px-4 mt-2 mb-1">
            <button onclick="simulatePaymentSuccess()" 
                class="w-full bg-green-600 text-white font-bold py-2 rounded-md text-sm shadow hover:bg-green-700 transition">
                🚀 DEBUG: Simulate Payment Success
            </button>
        </div>
        @endif

        <!-- Info Bantuan -->
        <div class="text-xs text-gray-400 text-center mt-6 px-4">
            Jika sudah membayar namun status tidak berubah,<br>
            klik tombol di atas untuk cek status atau hubungi kasir.
        </div>
    </div>

    <!-- Script Timer -->
    <script>
        console.log('🚀 Timer starting...');

        const timer = document.getElementById('countdown-timer');
        const orderDate = new Date('{{ $order->created_at->toISOString() }}');
        const endTime = new Date(orderDate.getTime() + (2 * 60 * 1000)); // 2 minutes

        function updateCountdown() {
            const now = new Date();
            const timeLeft = Math.max(0, endTime - now);

            if (timeLeft <= 0) {
                console.log('⏰ Order expired! Auto-redirecting...');
                timer.innerHTML = '⏰ EXPIRED';
                timer.className = 'text-lg font-bold text-red-900';

                clearInterval(window.countdownInterval);
                clearInterval(window.statusCheckInterval);

                window.location.href = '{{ route("order.menu", [$table->tenantIdentifier, $table->name]) }}';
                return;
            }

            const minutes = Math.floor(timeLeft / 60000);
            const seconds = Math.floor((timeLeft % 60000) / 1000);

            timer.innerHTML = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft > 60000) {
                timer.className = 'text-lg font-bold text-green-700';
            } else if (timeLeft > 30000) {
                timer.className = 'text-lg font-bold text-orange-700';
            } else {
                timer.className = 'text-lg font-bold text-red-700';
            }
        }

        updateCountdown();
        window.countdownInterval = setInterval(updateCountdown, 1000);

        console.log('✅ Timer started!');

        // Auto check payment status setiap 3 detik
        function checkPaymentStatus() {
            console.log('🔍 Checking payment status...');
            
            const statusDiv = document.getElementById('payment-status');
            
            fetch('{{ route("order.qris.check-status", [$table->tenantIdentifier, $table->name, $order->code]) }}')
                .then(response => response.json())
                .then(data => {
                    console.log('📡 Status response:', data);
                    
                    if (data.status === 'paid') {
                        console.log('✅ Payment successful! Stopping countdown and redirecting...');
                        
                        // Stop countdown timer
                        clearInterval(window.countdownInterval);
                        clearInterval(window.statusCheckInterval);
                        
                        // Update timer display
                        const timer = document.getElementById('countdown-timer');
                        timer.innerHTML = '✅ PAID';
                        timer.className = 'text-lg font-bold text-green-700';
                        
                        // Update status indicator
                        statusDiv.innerHTML = `
                            <div class="flex items-center justify-center">
                                <span class="text-green-600 font-semibold">✅ Payment Successful!</span>
                            </div>
                        `;
                        statusDiv.className = 'bg-green-50 border border-green-200 rounded-lg p-3 mb-3';
                        
                        // Redirect to success page
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1500);
                        
                    } else if (data.status === 'failed') {
                        console.log('❌ Payment failed! Stopping countdown and redirecting...');
                        
                        // Stop countdown timer
                        clearInterval(window.countdownInterval);
                        clearInterval(window.statusCheckInterval);
                        
                        // Update timer display
                        const timer = document.getElementById('countdown-timer');
                        timer.innerHTML = '❌ FAILED';
                        timer.className = 'text-lg font-bold text-red-900';
                        
                        // Update status indicator
                        statusDiv.innerHTML = `
                            <div class="flex items-center justify-center">
                                <span class="text-red-600 font-semibold">❌ Payment Failed</span>
                            </div>
                        `;
                        statusDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-3 mb-3';
                        
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                        
                    } 
                    // else {
                    //     console.log('⏳ Payment still pending...');
                    //     // Update status to show last check time
                    //     const now = new Date().toLocaleTimeString();
                    //     statusDiv.innerHTML = `
                    //         <div class="flex items-center justify-center">
                    //             <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                    //             <span class="text-sm text-blue-600">Waiting for payment... (last check: ${now})</span>
                    //         </div>
                    //     `;
                    // }
                })
                .catch(error => {
                    console.error('❌ Error checking payment status:', error);
                    const statusDiv = document.getElementById('payment-status');
                    statusDiv.innerHTML = `
                        <div class="flex items-center justify-center">
                            <span class="text-orange-600 text-sm">⚠️ Error checking status. Please try manually.</span>
                        </div>
                    `;
                    statusDiv.className = 'bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3';
                });
        }

        // Start auto-checking every 3 seconds
        window.statusCheckInterval = setInterval(checkPaymentStatus, 3000);
        
                // Check immediately on page load
        setTimeout(checkPaymentStatus, 1000);

        @if(app()->environment(['local', 'development']))
        // DEBUG function untuk force payment success
        function forcePaymentSuccess() {
            console.log('🚀 Manually forcing payment success...');
            
            fetch('{{ route("debug.order.force-success", [$table->tenantIdentifier, $table->name, $order->code]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ Payment forced to success:', data);
                // Trigger status check immediately after forcing
                setTimeout(checkPaymentStatus, 500);
            })
            .catch(error => {
                console.error('❌ Error forcing payment success:', error);
            });
        }
        @endif
    </script>

        @if(app()->environment(['local', 'development']))
        // DEBUG function untuk simulate payment success
        function simulatePaymentSuccess() {
            console.log('🚀 Simulating payment success...');
            
            // Trigger immediate status check (the endpoint will force success in local env)
            checkPaymentStatus();
        }
        @endif
    </script>
</body>

</html>
