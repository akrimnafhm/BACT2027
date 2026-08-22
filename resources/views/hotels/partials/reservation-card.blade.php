@php($isPaid = $reservation->status === 'paid')
@php($quantity = max(1, $reservation->quantity))

<div class="bg-white rounded-2xl shadow-sm border overflow-hidden flex flex-col {{ $isPaid ? 'border-gray-200' : 'border-yellow-300 ring-2 ring-yellow-200' }}">

    <!-- Strip Status -->
    <div class="px-5 py-3 flex items-center justify-between gap-2 {{ $isPaid ? 'bg-green-50' : 'bg-yellow-50' }}">
        <span class="inline-flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wide {{ $isPaid ? 'text-green-700' : 'text-amber-700' }}">
            <span class="w-2 h-2 rounded-full {{ $isPaid ? 'bg-green-500' : 'bg-amber-500 animate-pulse' }}"></span>
            {{ $statusLabel }}
        </span>
        <span class="font-mono text-xs font-bold text-gray-500">{{ $reservation->booking_code }}</span>
    </div>

    <!-- Isi Ringkas -->
    <div class="p-5 space-y-4 flex-grow">
        <div>
            <h3 class="text-base font-black text-[#234661] leading-snug">{{ $reservation->hotelRoom->room_type ?? 'Kamar Hotel' }}</h3>
            <p class="text-xs text-gray-400 font-bold mt-0.5">{{ $quantity }} Kamar &middot; {{ $reservation->total_nights }} Malam</p>
        </div>

        <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-50 rounded-xl border border-gray-100 px-3 py-2.5">
                <span class="block text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Check-in</span>
                <span class="block text-sm font-black text-gray-900 mt-0.5">{{ $reservation->check_in->format('d M Y') }}</span>
            </div>
            <div class="bg-gray-50 rounded-xl border border-gray-100 px-3 py-2.5">
                <span class="block text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Check-out</span>
                <span class="block text-sm font-black text-gray-900 mt-0.5">{{ $reservation->check_out->format('d M Y') }}</span>
            </div>
        </div>

        <div class="flex items-end justify-between pt-1">
            <span class="text-[11px] font-extrabold text-gray-400 uppercase tracking-wider pb-0.5">Total Tagihan</span>
            <div class="text-right">
                <span class="block text-lg font-black text-[#E19404] leading-none">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</span>
                <span class="block text-[10px] text-gray-400 mt-1">Rp {{ number_format($reservation->hotelRoom->price_per_night ?? 0, 0, ',', '.') }}/malam/kamar</span>
            </div>
        </div>
    </div>

    <!-- Aksi -->
    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/60">
        @if($isPaid)
            <a href="{{ route('hotels.invoice.preview', $reservation->id) }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-[#234661] hover:bg-[#1c3b54] text-white text-xs font-extrabold transition shadow-sm">
                Lihat Receipt
            </a>
        @else
            <div class="space-y-2">
                <a href="{{ route('hotels.checkout', $reservation->id) }}" class="block w-full text-center py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-extrabold transition shadow-sm">
                    Lanjutkan Pembayaran
                </a>
                <form method="POST" action="{{ route('hotels.cancel', $reservation->id) }}" onsubmit="return confirm('Batalkan reservasi ini? Kamu bisa memesan ulang setelahnya.')">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-extrabold transition">
                        Batalkan Reservasi
                    </button>
                </form>
            </div>
        @endif
    </div>

</div>
