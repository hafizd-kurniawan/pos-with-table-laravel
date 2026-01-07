@php
    $record = $getRecord();
    $transactions = $record->transactions()->latest()->get();
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-4 py-3">Time</th>
                <th scope="col" class="px-4 py-3">Type</th>
                <th scope="col" class="px-4 py-3 text-right">Amount</th>
                <th scope="col" class="px-4 py-3">Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3">
                        {{ $transaction->created_at->format('H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="bg-{{ $transaction->type === 'in' ? 'yellow' : 'red' }}-100 text-{{ $transaction->type === 'in' ? 'yellow' : 'red' }}-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-{{ $transaction->type === 'in' ? 'yellow' : 'red' }}-900 dark:text-{{ $transaction->type === 'in' ? 'yellow' : 'red' }}-300">
                            {{ $transaction->type === 'in' ? 'PAY IN' : 'PAY OUT' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-bold">
                        {{ format_rupiah($transaction->amount) }}
                    </td>
                    <td class="px-4 py-3">
                        {{ $transaction->description }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-3 text-center">Belum ada transaksi manual (Pay In/Out)</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
