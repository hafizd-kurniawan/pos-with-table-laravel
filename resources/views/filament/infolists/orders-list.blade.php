@php
    $record = $getRecord();
    $orders = \App\Models\Order::where('tenant_id', $record->tenant_id)
        ->where('created_at', '>=', $record->started_at)
        ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
        ->whereIn('status', ['paid', 'complete', 'completed'])
        ->with('orderItems.product')
        ->latest()
        ->get();
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-4 py-3">Order Code</th>
                <th scope="col" class="px-4 py-3">Time</th>
                <th scope="col" class="px-4 py-3">Payment</th>
                <th scope="col" class="px-4 py-3 text-right">Total</th>
                <th scope="col" class="px-4 py-3">Items</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $order->code }}
                    </td>
                    <td class="px-4 py-3">
                        {{ $order->created_at->format('H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="bg-{{ $order->payment_method === 'cash' ? 'green' : 'blue' }}-100 text-{{ $order->payment_method === 'cash' ? 'green' : 'blue' }}-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-{{ $order->payment_method === 'cash' ? 'green' : 'blue' }}-900 dark:text-{{ $order->payment_method === 'cash' ? 'green' : 'blue' }}-300">
                            {{ strtoupper($order->payment_method) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-bold">
                        {{ format_rupiah($order->total_amount) }}
                    </td>
                    <td class="px-4 py-3">
                        <ul class="list-disc list-inside">
                            @foreach ($order->orderItems as $item)
                                <li>
                                    {{ $item->product->name ?? 'Unknown' }} 
                                    <span class="text-gray-500">x{{ $item->quantity }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-3 text-center">Belum ada order</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
