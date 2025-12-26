@php
    $record = $getRecord();
    $orders = \App\Models\Order::where('tenant_id', $record->tenant_id)
        ->where('created_at', '>=', $record->started_at)
        ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
        ->whereIn('status', ['paid', 'complete', 'completed'])
        ->get();

    $breakdown = $orders->groupBy('payment_method')->map(function ($group, $method) {
        return [
            'method' => strtoupper($method),
            'count' => $group->count(),
            'gross_sales' => $group->sum('total_amount'),
            'tax' => $group->sum('tax_amount'),
            'service_charge' => $group->sum('service_charge_amount'),
            'discount' => $group->sum('discount_amount'),
            'net_sales' => $group->sum('total_amount') - $group->sum('tax_amount') - $group->sum('service_charge_amount'),
        ];
    })->values();
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-4 py-3">Metode</th>
                <th scope="col" class="px-4 py-3 text-center">Transaksi</th>
                <th scope="col" class="px-4 py-3 text-right">Gross</th>
                <th scope="col" class="px-4 py-3 text-right">Tax</th>
                <th scope="col" class="px-4 py-3 text-right">Service</th>
                <th scope="col" class="px-4 py-3 text-right">Discount</th>
                <th scope="col" class="px-4 py-3 text-right font-bold">Net Sales</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($breakdown as $item)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <span class="bg-{{ $item['method'] === 'CASH' ? 'green' : 'blue' }}-100 text-{{ $item['method'] === 'CASH' ? 'green' : 'blue' }}-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-{{ $item['method'] === 'CASH' ? 'green' : 'blue' }}-900 dark:text-{{ $item['method'] === 'CASH' ? 'green' : 'blue' }}-300">
                            {{ $item['method'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        {{ $item['count'] }} Trx
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{ format_rupiah($item['gross_sales']) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{ format_rupiah($item['tax']) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{ format_rupiah($item['service_charge']) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{ format_rupiah($item['discount']) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                        {{ format_rupiah($item['net_sales']) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-3 text-center">Belum ada transaksi</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
