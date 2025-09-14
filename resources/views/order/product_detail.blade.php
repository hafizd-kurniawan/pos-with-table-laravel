<!DOCTYPE html>
<html>
<head>
    <title>{{ $product->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="max-w-md mx-auto bg-white min-h-screen flex flex-col pb-24">
    <!-- Header -->
    <div class="flex items-center px-4 py-3 border-b">
        <a href="{{ route('order.menu', $table->name) }}" class="text-blue-500 text-sm">&larr; Back</a>
        <h1 class="flex-1 text-center font-medium">Detail Produk</h1>
        <span class="w-12"></span>
    </div>

    <!-- Gambar Produk -->
    <img src="{{ $product->image }}" class="w-full h-64 object-cover" alt="{{ $product->name }}">

    <!-- Info Produk -->
    <div class="p-4 flex-1">
        <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
        <p class="text-lg text-gray-600 mt-1">Rp{{ number_format($product->price) }}</p>
        <p class="mt-3 text-gray-800">{{ $product->description ?? 'Tidak ada deskripsi.' }}</p>
        <p class="mt-2 text-sm text-gray-500">Stok: {{ $product->stock ?? '-' }}</p>

        <!-- Form Add to Cart -->
        <form action="{{ route('order.addToCartWithNote', [$table->name, $product->id]) }}" method="POST" class="mt-5">
            @csrf
            <label class="block text-sm font-medium mb-1">Catatan:</label>
            <textarea name="notes" class="w-full border rounded p-2" placeholder="Catatan untuk pesanan..."></textarea>

            <label class="block text-sm font-medium mt-3 mb-1">Jumlah:</label>
            <input type="number" name="qty" value="1" min="1" class="w-20 border rounded p-1">

            <button type="submit" class="mt-4 bg-black text-white px-4 py-2 rounded w-full">Add to Cart</button>
        </form>
    </div>
    <!-- Cart Bar (sticky bottom) -->
    <div class="fixed bottom-0 left-0 right-0 bg-black text-white px-4 py-3 flex items-center justify-between max-w-md mx-auto"
        style="border-radius:1rem 1rem 0 0; box-shadow: 0 -2px 8px rgba(0,0,0,0.05);">
        @php
            $cart = session('cart_' . $table->name, []);
            $total = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
            $count = collect($cart)->sum('qty');
        @endphp
        <div class="flex items-center">
            <span class="bg-white text-black rounded-full w-6 h-6 flex items-center justify-center mr-2 font-bold text-sm">{{ $count }}</span>
            <span class="font-medium">Total</span> &nbsp;
            <span class="ml-1 text-lg font-bold">Rp{{ number_format($total) }}</span>
        </div>
        <a href="{{ route('order.cart', $table->name) }}"
        class="ml-3 bg-white text-black font-bold px-4 py-2 rounded shadow text-sm">CHECK OUT</a>
    </div>

</div>
</body>
</html>
