<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Hotel & Akomodasi - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased font-sans bg-[#F4F5F7] text-gray-800 min-h-screen flex flex-col">

    <!-- 1. NAVBAR (KONSISTEN DENGAN HALAMAN BOOKING TIKET) -->
    <nav class="fixed top-0 w-full z-50 px-6 py-4 bg-[#FFFFFF] shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto flex justify-between items-center">

            <a href="/" class="flex items-center">
                <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-12 w-auto">
            </a>

            <!-- Menu Navigasi -->
            <div class="hidden md:flex space-x-6 text-[13px] lg:text-sm font-semibold">
                <a href="/#beranda" class="nav-scroll hover:text-[#E19404] transition {{ request()->is('/') ? 'text-[#E19404]' : 'text-gray-700' }}">Beranda</a>
                <a href="/#pembicara" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Pembicara</a>
                <a href="/#jadwal" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Jadwal</a>
                <a href="/#lokasi" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Lokasi</a>
                <a href="/#galeri" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Galeri</a>
                <a href="/program-ilmiah" class="hover:text-[#E19404] transition {{ request()->is('program-ilmiah*') ? 'text-[#E19404]' : 'text-gray-700' }}">Program Ilmiah</a>
                <a href="{{ route('booking.index') }}" class="hover:text-[#E19404] transition {{ request()->routeIs('booking.*') || request()->is('booking*') || request()->is('checkout*') ? 'text-[#E19404]' : 'text-gray-700' }}">Pesan Tiket</a>
                <a href="/hotel" class="hover:text-[#E19404] transition {{ request()->is('hotel*') ? 'text-[#E19404] font-bold' : 'text-gray-700' }}">Pesan Hotel</a>
                
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

    <!-- 2. KONTEN UTAMA - KATALOG KAMAR -->
    <main class="max-w-7xl mx-auto px-6 pt-32 pb-16 flex-grow w-full space-y-10">

        <!-- Header Seksi -->
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">Akomodasi Resmi Simposium</span>
            <h1 class="text-3xl md:text-4xl font-black text-gray-900">Pilihan Kamar Hotel Mitra BACT 2027</h1>
            <p class="text-sm md:text-base text-gray-600 leading-relaxed">
                Nikmati kenyamanan menginap di venue utama simposium dengan harga khusus peserta. Pilih tipe kamar yang sesuai dengan preferensimu.
            </p>
        </div>

        <!-- Notifikasi Error jika ada -->
        @if(session('error'))
            <div class="max-w-3xl mx-auto bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm text-center">
                {{ session('error') }}
            </div>
        @endif

        <!-- =========================================================
             A. KONDISI TAMU / GUEST (BELUM LOGIN)
             ========================================================= -->
        @if(isset($status) && $status === 'guest')
            <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm p-8 border border-gray-200 text-center">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Pilih Kamar Hotel Anda</h3>
                <p class="text-gray-500 mb-6 text-sm">Anda harus login terlebih dahulu untuk melihat ketersediaan dan memesan kamar hotel akomodasi.</p>
                <a href="{{ route('login') }}" class="inline-block bg-[#E19404] hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                    Login untuk Memesan Hotel
                </a>
            </div>

        <!-- =========================================================
             B. KONDISI PROFIL BELUM LENGKAP
             ========================================================= -->
        @elseif(isset($status) && $status === 'incomplete')
            <div class="max-w-3xl mx-auto bg-yellow-50 rounded-2xl shadow-sm p-8 border border-yellow-200 text-center">
                <h3 class="text-lg font-bold text-yellow-800 mb-2">Profil Belum Lengkap</h3>
                <p class="text-yellow-700 mb-6 text-sm">Mohon lengkapi NIK dan verifikasi kontak Anda terlebih dahulu pada halaman profil sebelum memesan kamar hotel.</p>
                <a href="{{ route('profile.edit') }}" class="inline-block bg-[#FFC32D] hover:bg-[#E19404] text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                    Lengapi Profil Sekarang
                </a>
            </div>

        <!-- =========================================================
             C. KONDISI RESERVASI PENDING (BELUM LUNAS)
             ========================================================= -->
        @elseif(isset($status) && $status === 'pending')
            <div class="max-w-3xl mx-auto space-y-6">

                <div class="bg-yellow-50 rounded-2xl shadow-sm p-8 border border-yellow-200 text-center">
                    <h3 class="text-lg font-bold text-yellow-800 mb-2">Pembayaran Belum Selesai</h3>
                    <p class="text-yellow-700 mb-6 text-sm">Mohon selesaikan pembayaran untuk reservasi hotel Anda. Pesanan akan diproses setelah pembayaran dikonfirmasi.</p>
                    <a href="{{ route('hotels.checkout', $reservation->id) }}" class="inline-flex justify-center items-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-xl transition shadow-md">
                        Lanjutkan Pembayaran
                    </a>
                </div>

                @include('hotels.partials.invoice', ['reservation' => $reservation, 'statusLabel' => 'Pending'])
            </div>

        <!-- =========================================================
             D. KONDISI RESERVASI PAID (SUDAH DIBAYAR)
             ========================================================= -->
        @elseif(isset($status) && $status === 'paid')
            <div class="max-w-3xl mx-auto space-y-6">

                @include('hotels.partials.invoice', ['reservation' => $reservation, 'statusLabel' => 'Sudah Dibayar'])
            </div>

        <!-- =========================================================
             E. KONDISI SIAP / READY (SUDAH LOGIN & PROFIL LENGKAP)
             ========================================================= -->
        @else
            <!-- Grid Daftar Kamar (Deluxe King & Deluxe Twin) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                @forelse($hotels as $hotel)
                    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm hover:shadow-xl transition duration-300 flex flex-col overflow-hidden group">
                        
                        <!-- Foto Kamar -->
                        <div class="h-64 sm:h-72 w-full overflow-hidden relative bg-gray-100">
                            @if($hotel->image)
                                <img src="{{ asset('storage/' . $hotel->image) }}" alt="{{ $hotel->room_type }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 font-bold text-sm">Foto Tidak Tersedia</div>
                            @endif
                        </div>

                        <!-- Detail & Spesifikasi Kamar -->
                        <div class="p-7 flex flex-col flex-grow justify-between space-y-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-start gap-2">
                                    <h2 class="text-2xl font-black text-gray-900">{{ $hotel->room_type }}</h2>
                                    <div class="text-right">
                                        <span class="text-xl font-black text-[#E19404]">Rp {{ number_format($hotel->price_per_night, 0, ',', '.') }}</span>
                                        <span class="block text-[11px] font-bold text-gray-400 uppercase">per malam</span>
                                    </div>
                                </div>

                                <!-- Deskripsi -->
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    {{ $hotel->description ?: 'Kamar bernuansa nyaman dan elegan untuk mendukung kelancaran aktivitasmu selama simposium berlangsung.' }}
                                </p>

                                <!-- Facilities Tag / Badge -->
                                @if($hotel->facilities)
                                    <div class="space-y-1.5 pt-2 border-t border-gray-100">
                                        <span class="text-[11px] font-bold uppercase text-gray-400 tracking-wider">Fasilitas Termasuk:</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach(explode(',', $hotel->facilities) as $fac)
                                                <span class="px-3 py-1 bg-[#FBE39D]/40 border border-[#E19404]/20 text-gray-800 text-xs font-bold rounded-lg">
                                                    {{ trim($fac) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Tombol Action -->
                            <div class="pt-4 border-t border-gray-100">
                                <a href="{{ route('hotels.book', $hotel->id) }}" class="block w-full text-center py-3.5 px-6 rounded-2xl bg-[#E19404] hover:bg-orange-600 text-white font-extrabold text-sm transition shadow-md hover:shadow-lg">
                                    Pesan Kamar Ini &rarr;
                                </a>
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="col-span-full py-16 bg-white rounded-3xl border border-gray-200 text-center text-gray-500 space-y-2">
                        <p class="text-base font-bold text-gray-700">Belum Ada Tipe Kamar yang Tersedia</p>
                        <p class="text-xs text-gray-400">Silakan hubungi panitia atau cek kembali halaman ini secara berkala.</p>
                    </div>
                @endforelse
            </div>
        @endif

    </main>

    <!-- 3. FOOTER (KONSISTEN DENGAN HALAMAN BOOKING) -->
    <footer class="bg-white border-t border-gray-200 mt-auto pt-10 pb-6 w-full">
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-8 text-center md:text-left">
                <div>
                    <h4 class="font-extrabold text-gray-900 text-lg mb-2">Hubungi Panitia BACT</h4>
                    <p class="text-sm text-gray-500 mb-1">Email: support@bactevent.com</p>
                    <p class="text-sm text-gray-500">Instagram: @bact_2026</p>
                </div>
                <div class="flex flex-col items-center md:items-end">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-10 w-auto opacity-75 mb-3">
                    <p class="text-sm text-gray-500 font-medium">Simposium Nasional Medis & Kesehatan</p>
                </div>
            </div>
            <div class="border-t border-gray-100 pt-6 flex flex-col md:flex-row justify-between items-center gap-4 text-center md:text-left">
                <p class="text-xs text-gray-400">&copy; {{ date('Y') }} BACT Event System. Hak Cipta Dilindungi.</p>
                <p class="text-xs text-gray-400 font-semibold">Developed for BACT 2026</p>
            </div>
        </div>
    </footer>

</body>
</html>