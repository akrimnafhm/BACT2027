<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Header Card -->
    @php($isPaid = $reservation->status === 'paid')
    <div class="px-8 py-6 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 {{ $isPaid ? 'bg-green-50 border-green-200' : 'bg-[#FBE39D] border-[#E19404]/20' }}">
        <div>
            <h2 class="text-xl font-extrabold text-gray-900">Detail Pemesanan Hotel</h2>
            <p class="text-gray-700 text-sm mt-1">Simpan halaman ini untuk keperluan registrasi.</p>
        </div>
        <span class="inline-block px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide {{ $isPaid ? 'bg-green-500 text-white' : 'bg-white border border-[#E19404]/30 text-[#E19404]' }}">
            Status: {{ $statusLabel }}
        </span>
    </div>

    <div class="p-8 space-y-6">

        <!-- KODE BOOKING -->
        <div class="bg-gray-50 rounded-xl border border-dashed border-gray-300 px-5 py-4 text-center">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Kode Booking</span>
            <span class="block text-2xl font-black text-gray-900 tracking-wide mt-1">{{ $reservation->booking_code }}</span>
        </div>

        <!-- INFORMASI PEMESAN -->
        <div>
            <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3">Informasi Pemesan</h3>
            <div class="bg-gray-50 rounded-xl border border-gray-200 divide-y divide-gray-200/70 text-sm">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                    <span class="text-gray-500 font-medium">Nama Lengkap</span>
                    <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->guest_name ?: $reservation->user->name ?? '' }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                    <span class="text-gray-500 font-medium">E-mail</span>
                    <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->guest_email ?: $reservation->user->email ?? '' }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                    <span class="text-gray-500 font-medium">Nomor WhatsApp</span>
                    <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->guest_phone ?: $reservation->user->phone_number ?? '' }}</span>
                </div>
            </div>
        </div>

        <!-- DETAIL MENGINAP -->
        <div>
            <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3">Detail Menginap</h3>
            <div class="bg-gray-50 rounded-xl border border-gray-200 divide-y divide-gray-200/70 text-sm">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                    <span class="text-gray-500 font-medium">Tipe Kamar</span>
                    <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->hotelRoom->room_type ?? 'Kamar Hotel' }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                    <span class="text-gray-500 font-medium">Check-in</span>
                    <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->check_in->format('d M Y') }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                    <span class="text-gray-500 font-medium">Check-out</span>
                    <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->check_out->format('d M Y') }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                    <span class="text-gray-500 font-medium">Total Malam</span>
                    <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->total_nights }} Malam</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                    <span class="text-gray-500 font-medium">Total Harga</span>
                    <span class="font-bold text-[#E19404] mt-0.5 sm:mt-0 text-base">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

    </div>
</div>
