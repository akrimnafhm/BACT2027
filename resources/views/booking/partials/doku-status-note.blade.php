{{-- Label terpisah: status LOKAL aplikasi vs status terakhir yang tercatat dari DOKU.
     Status lokal adalah satu-satunya penentu alur (bayar/batal); status DOKU
     hanya info tampilan dan tidak pernah disamakan dengan status lokal. --}}
@php
    $dokuTxn = $booking->last_doku_transaction_status ?? null;
    $dokuOrder = $booking->last_doku_order_status ?? null;
    $dokuChecked = $booking->last_doku_checked_at ?? null;
@endphp
<div class="mt-3 text-xs text-gray-500 space-y-1">
    <p>Status pesanan (aplikasi): <span class="font-bold text-gray-700 uppercase">{{ $booking->status }}</span></p>
    @if($dokuTxn || $dokuOrder)
        <p>
            Status DOKU terakhir: transaksi
            <span class="font-bold text-gray-700">{{ $dokuTxn ?: '-' }}</span>
            / order
            <span class="font-bold text-gray-700">{{ $dokuOrder ?: '-' }}</span>
            @if($dokuChecked)
                <span class="text-gray-400">({{ $dokuChecked->format('d M Y H:i') }})</span>
            @endif
        </p>
    @else
        <p class="text-gray-400">Status DOKU terakhir: belum ada hasil Check Status yang tercatat.</p>
    @endif
</div>
