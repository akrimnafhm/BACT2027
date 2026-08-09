<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Tiket - BACT 2026</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans pt-28">

    <!-- NAVBAR -->
    @include('partials.navbar')

    <!-- KONTEN CHECKOUT -->
    <div class="max-w-3xl mx-auto px-4 flex-grow w-full mb-16 mt-4">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header Card -->
            <div class="bg-[#FBE39D] px-8 py-6 border-b border-[#E19404]/20">
                <h2 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-[#E19404]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Verifikasi Pesanan
                </h2>
                <p class="text-gray-700 text-sm mt-1">Pastikan seluruh data diri dan instansi Anda sudah benar sebelum melanjutkan ke pembayaran.</p>
            </div>

            <div class="p-8 space-y-8">
                
                <!-- 1. INFORMASI PESERTA & INSTANSI (9 DATA LENGKAP) -->
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-4">Informasi Peserta & Instansi</h3>
                    
                    <div class="bg-gray-50 rounded-xl border border-gray-200 divide-y divide-gray-200/70 text-sm">
                        
                        <!-- E-mail (Gmail) -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">E-mail (Gmail)</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->gmail_account ?: Auth::user()->email }}</span>
                        </div>

                        <!-- Nomor WhatsApp -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Nomor WhatsApp</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->whatsapp_number ?: Auth::user()->phone_number }}</span>
                        </div>

                        <!-- NIK -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Nomor Induk Kependudukan (NIK)</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->nik ?: Auth::user()->nik }}</span>
                        </div>

                        <!-- Nama Lengkap (KTP) -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Nama Lengkap (Sesuai KTP)</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->full_name ?: Auth::user()->name }}</span>
                        </div>

                        <!-- Nama dengan Gelar (Sertifikat) -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Nama & Gelar (Untuk Sertifikat)</span>
                            <span class="font-bold text-[#E19404] mt-0.5 sm:mt-0">{{ $booking->name_with_title ?: Auth::user()->name }}</span>
                        </div>

                        <!-- E-mail Plataran Sehat -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">E-mail Plataran Sehat</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->plataran_sehat_email }}</span>
                        </div>

                        <!-- Profesi Medis -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Profesi Medis</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->profession }}</span>
                        </div>

                        <!-- Nama Instansi -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                            <span class="text-gray-500 font-medium">Nama Instansi</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->institution_name }}</span>
                        </div>

                        <!-- Alamat Instansi -->
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start py-3 px-5">
                            <span class="text-gray-500 font-medium sm:pt-0.5">Alamat Instansi</span>
                            <span class="font-bold text-gray-900 mt-0.5 sm:mt-0 sm:text-right">
                                {{ $booking->institution_district }}, {{ $booking->institution_city }}<br>
                                <span class="text-xs font-semibold text-gray-500">{{ $booking->institution_province }}</span>
                            </span>
                        </div>

                    </div>
                </div>

                <!-- 2. DETAIL TIKET (TANPA GARIS DI ATAS BOX NOMINAL) -->
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3">Detail Tiket</h3>
                    
                    <!-- Garis border-b dihilangkan agar langsung mulus ke box pembayaran -->
                    <div class="flex justify-between items-center py-3">
                        <div>
                            <h4 class="font-extrabold text-gray-900 text-sm uppercase tracking-wide">
                                {{ str_replace([' - ', ' -', '- '], ': ', $displayName) }}
                            </h4>
                            <p class="text-xs text-gray-500 mt-0.5">Simposium Nasional BACT 2026</p>
                        </div>
                        <div class="text-base font-extrabold text-gray-900">
                            Rp{{ number_format($booking->amount, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- 3. CHECKOUT BAR PROPORTIONAL & PROFESIONAL -->
                <div class="bg-[#E19404] rounded-xl py-4 px-6 text-white flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 shadow-sm mt-2">
                    <div>
                        <span class="block text-[11px] font-semibold text-white/85 uppercase tracking-wider">Total Pembayaran</span>
                        <span class="block text-xl font-extrabold mt-0.5">
                            Rp{{ number_format($booking->amount, 0, ',', '.') }}
                        </span>
                    </div>
                    <a href="{{ $paymentUrl }}" class="bg-white text-[#E19404] hover:bg-gray-50 font-extrabold py-2.5 px-6 rounded-lg shadow-sm transition transform active:scale-95 flex justify-center items-center gap-2 text-sm text-center whitespace-nowrap">
                            <span>Bayar Sekarang</span>
                    </a>

                    <!-- <div class="grid gap-4 grid-cols-2">
                        <a href="{{ route('booking.form') }}" class="text-white border border-white hover:bg-yellow-600 font-extrabold py-2.5 px-6 rounded-lg shadow-sm transition transform active:scale-95 flex justify-center items-center gap-2 text-sm text-center whitespace-nowrap">
                            <span>Edit Data</span>
                        </a>
                        <a href="{{ $paymentUrl }}" class="bg-white text-[#E19404] hover:bg-gray-50 font-extrabold py-2.5 px-6 rounded-lg shadow-sm transition transform active:scale-95 flex justify-center items-center gap-2 text-sm text-center whitespace-nowrap">
                            <span>Bayar Sekarang</span>
                        </a>
                    </div> -->
                </div>

            </div>
        </div>
    </div>

</body>
</html>