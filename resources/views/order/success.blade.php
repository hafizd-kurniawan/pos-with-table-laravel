<!DOCTYPE html>
<html>

<head>
    <title>Payment Successful - Table {{ $table->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body class="bg-gray-100">
    <div class="max-w-md mx-auto bg-white shadow-lg min-h-screen flex flex-col justify-center items-center relative">
        <!-- Check Mark Icon -->
        <div class="flex flex-col items-center mt-20">
            <div class="rounded-full bg-green-100 p-5 mb-4">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" stroke-width="3"
                    viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                        fill="none" />
                    <path d="M7 13l3 3 7-7" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
            <div class="text-green-600 font-bold text-2xl mb-2">Payment Successful!</div>
            <div class="text-gray-600 mb-4 text-center">Your order is being processed.<br>Please wait while your food/drinks are delivered to your table.</div>
        </div>

        <!-- Order Info -->
        <div class="bg-gray-50 rounded-lg px-6 py-4 mt-2 mb-6 w-full max-w-xs text-center shadow">
            <div class="text-sm text-gray-400 mb-2">Table Number</div>
            <div class="text-lg font-bold mb-1">{{ $table->name }}</div>
            <div class="text-sm text-gray-400 mb-2">Order Code</div>
            <div class="font-mono font-bold text-base mb-1">{{ $order->code }}</div>
            <div class="text-sm text-gray-400 mb-2">Total</div>
            <div class="text-xl font-bold text-gray-700">Rp{{ number_format($order->total_amount) }}</div>
        </div>

        <!-- Button Back to Menu/Home -->
        <a href="{{ route('order.menu', $table->name) }}"
            class="inline-block bg-black text-white font-bold px-6 py-3 rounded shadow mb-16">
            Back to Menu
        </a>
    </div>
</body>

</html>
