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
    @include('partials.navbar')

    <!-- 2. KONTEN UTAMA - KATALOG KAMAR -->
    <main class="max-w-7xl mx-auto px-6 pt-32 pb-16 flex-grow w-full space-y-10">

        <!-- Header Seksi -->
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">Akomodasi Resmi Simposium</span>
            <h1 class="text-3xl md:text-4xl font-black text-[#234661]">Pilihan Kamar Hotel Mitra BACT 2027</h1>
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
                <p class="text-yellow-700 mb-6 text-sm">Mohon lengkapi NIK dan verifikasi email Anda terlebih dahulu pada halaman profil sebelum memesan kamar hotel.</p>
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
                        
                        <!-- Foto Kamar-->
                        <div class="h-64 sm:h-72 w-full overflow-hidden relative bg-gray-100">
                            @if(is_array($hotel->photos) && count($hotel->photos) > 0)
                                <img src="{{ asset('storage/' . $hotel->photos[0]) }}" alt="{{ $hotel->room_type }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 font-bold text-sm">Foto Tidak Tersedia</div>
                            @endif
                        </div>

                        <!-- Detail & Spesifikasi Kamar -->
                        <div class="p-7 flex flex-col flex-grow justify-between space-y-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-start gap-2">
                                    <h2 class="text-2xl font-black text-[#234661]">{{ $hotel->room_type }}</h2>
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
    @include('partials.footer')

</body>
</html>