@extends('layouts.order')

@section('title', 'Pembayaran Berhasil - ' . $table->name)

@section('content')
<div x-data="{ countdown: 5 }" x-init="
    setInterval(() => {
        countdown--;
        if (countdown <= 0) window.location.href = '{{ route('order.menu', [$table->tenantIdentifier, $table->name]) }}';
    }, 1000)
" class="min-h-screen bg-white flex flex-col items-center justify-center p-6 text-center">

    <!-- Success Animation -->
    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-6 animate-bounce">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">Pembayaran Berhasil!</h1>
    <p class="text-gray-500 mb-8">Pesanan Anda sedang diproses dan akan segera diantar ke meja.</p>

    <!-- Receipt Card -->
    <div class="bg-gray-50 rounded-2xl p-6 w-full max-w-xs border border-gray-100 mb-8">
        <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-500">Order ID</span>
            <span class="font-mono font-bold text-gray-900">{{ $order->code }}</span>
        </div>
        <div class="flex justify-between text-sm mb-4">
            <span class="text-gray-500">Meja</span>
            <span class="font-bold text-gray-900">{{ $table->name }}</span>
        </div>
        <div class="border-t border-dashed border-gray-300 my-4"></div>
        <div class="flex justify-between items-center">
            <span class="text-gray-600 font-medium">Total</span>
            <span class="text-xl font-bold text-gray-900">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Action -->
    <a href="{{ route('order.menu', [$table->tenantIdentifier, $table->name]) }}" 
       class="w-full max-w-xs bg-black text-white font-bold py-3.5 rounded-xl shadow-lg hover:bg-gray-800 active:scale-95 transition mb-4">
        Kembali ke Menu
    </a>

    <p class="text-xs text-gray-400">
        Otomatis kembali dalam <span x-text="countdown" class="font-bold text-gray-600"></span> detik
    </p>
</div>
@endsection
