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
    <nav class="fixed top-0 w-full z-50 px-6 py-4 bg-[#FFFFFF] shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="/" class="flex items-center">
                <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-12 w-auto">
            </a>

            <!-- Menu Navigasi-->
            <div class="hidden md:flex space-x-6 text-[13px] lg:text-sm font-semibold">
                <a href="/#beranda" class="nav-scroll hover:text-[#E19404] transition {{ request()->is('/') ? 'text-[#E19404]' : 'text-gray-700' }}">Beranda</a>
                <a href="/#pembicara" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Pembicara</a>
                <a href="/#jadwal" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Jadwal</a>
                <a href="/#lokasi" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Lokasi</a>
                <a href="/#galeri" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Galeri</a>
                <a href="/program-ilmiah" class="hover:text-[#E19404] transition {{ request()->is('program-ilmiah*') ? 'text-[#E19404]' : 'text-gray-700' }}">Program Ilmiah</a>
                <a href="{{ route('booking.index') }}" class="hover:text-[#E19404] transition {{ request()->routeIs('booking.*') || request()->is('booking*') || request()->is('checkout*') ? 'text-[#E19404]' : 'text-gray-700' }}">Pesan Tiket</a>
                <a href="/#hotel" class="hover:text-[#E19404] transition {{ request()->is('hotel*') ? 'text-[#E19404]' : 'text-gray-700' }}">Pesan Hotel</a>
                
                @auth
                    @if(Auth::user()->role === 'admin')
                        <span class="text-gray-300">|</span>
                        <a href="/admin/dashboard" class="hover:text-red-600 transition text-red-500 font-bold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Panel Admin
                        </a>
                    @endif
                @endauth
            </div>

            <!-- Tombol Login / Profil -->
            <div class="flex items-center space-x-4">
                @guest
                    <a href="/login" class="text-sm font-bold uppercase tracking-widest text-[#FFFFFF] bg-[#FFC32D] hover:bg-[#E19404] px-6 py-2.5 rounded-full transition shadow-md">
                        Masuk
                    </a>
                @endguest

                @auth
                    <div class="relative group cursor-pointer">
                        <div class="flex items-center space-x-2 text-gray-700 hover:text-[#E19404] transition font-bold">
                            <span>Hai, {{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>

                        <div class="absolute right-0 mt-2 w-48 bg-white border border-gray-100 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                            <a href="/profile" class="block px-4 py-3 text-sm text-gray-700 hover:bg-[#FBE39D] hover:text-[#E19404] rounded-t-lg font-medium transition">
                                Edit Profil
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="block m-0">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-b-lg font-medium transition">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

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
                </div>

            </div>
        </div>
    </div>

</body>
</html>