<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Hotel - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans pt-28">

    <!-- NAVBAR -->
    @include('partials.navbar', [
        'navbarShowGuestLogin' => false,
        'navbarShowAdmin' => false,
    ])

    <!-- KONTEN CHECKOUT -->
    <div class="max-w-3xl mx-auto px-4 flex-grow w-full mb-16 mt-4">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header Card -->
            <div class="bg-[#FBE39D] px-8 py-6 border-b border-[#E19404]/20">
                <h2 class="text-xl font-extrabold text-[#234661] flex items-center gap-2">
                    <svg class="w-6 h-6 text-[#E19404]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Verifikasi Pesanan Hotel
                </h2>
                <p class="text-gray-700 text-sm mt-1">Pastikan seluruh detail pemesanan sudah benar sebelum melanjutkan ke pembayaran.</p>
            </div>

            <div class="p-8 space-y-8">

                <!-- KODE BOOKING & STATUS -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Kode Booking</span>
                        <span class="block text-xl font-black text-gray-900 tracking-wide">{{ $reservation->booking_code }}</span>
                    </div>
                    <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide">
                        Status: {{ $reservation->status === 'paid' ? 'Sudah Dibayar' : 'Pending' }}
                    </span>
                </div>

                <!-- 1. INFORMASI PEMESAN -->
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-4">Informasi Pemesan</h3>

                    <div class="bg-gray-50 rounded-xl border border-gray-200 divide-y divide-gray-200/70 text-sm">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Nama Lengkap</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->guest_name ?: Auth::user()->name }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">E-mail</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->guest_email ?: Auth::user()->email }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Nomor WhatsApp</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $reservation->guest_phone ?: Auth::user()->phone_number }}</span>
                        </div>
                    </div>
                </div>

                <!-- 2. DETAIL PEMESANAN KAMAR -->
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3">Detail Pemesanan</h3>

                    <div class="bg-gray-50 rounded-xl border border-gray-200 divide-y divide-gray-200/70 text-sm">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Tipe Kamar</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $room->room_type ?? 'Kamar Hotel' }}</span>
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
                            <span class="text-gray-500 font-medium">Harga per Malam</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">Rp {{ number_format($room->price_per_night ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- 3. TOTAL PEMBAYARAN & TOMBOL BAYAR -->
                <div class="bg-[#E19404] rounded-xl py-4 px-6 text-white flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 shadow-sm mt-2">
                    <div>
                        <span class="block text-[11px] font-semibold text-white/85 uppercase tracking-wider">Total Pembayaran</span>
                        <span class="block text-xl font-extrabold mt-0.5">
                            Rp{{ number_format($reservation->total_price, 0, ',', '.') }}
                        </span>
                    </div>
                    <a href="{{ $paymentUrl }}" class="bg-white text-[#E19404] hover:bg-gray-50 font-extrabold py-2.5 px-6 rounded-lg shadow-sm transition transform active:scale-95 flex justify-center items-center gap-2 text-sm text-center whitespace-nowrap">
                            <span>Bayar Sekarang</span>
                    </a>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
