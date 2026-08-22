<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Hotel Saya - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased font-sans bg-[#F4F5F7] text-gray-800 min-h-screen flex flex-col">

    <!-- 1. NAVBAR (KONSISTEN DENGAN HALAMAN BOOKING TIKET) -->
    @include('partials.navbar')

    <!-- 2. KONTEN UTAMA - DAFTAR RESERVASI -->
    <main class="max-w-7xl mx-auto px-6 pt-32 pb-16 flex-grow w-full space-y-10">

        <!-- Header Seksi -->
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">Akomodasi Resmi Simposium</span>
            <h1 class="text-3xl md:text-4xl font-black text-[#234661]">Reservasi Hotel Saya</h1>
            <p class="text-sm md:text-base text-gray-600 leading-relaxed">
                Kelola seluruh reservasi kamar hotel Anda untuk BACT 2027 di satu tempat.
            </p>
        </div>

        <!-- Notifikasi Error jika ada -->
        @if(session('error'))
            <div class="max-w-3xl mx-auto bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm text-center">
                {{ session('error') }}
            </div>
        @endif

        <!-- Notifikasi Success jika ada -->
        @if(session('success'))
            <div class="max-w-3xl mx-auto bg-green-50 border border-green-200 text-green-700 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm text-center">
                {{ session('success') }}
            </div>
        @endif

        <!-- =========================================================
             A. KONDISI TAMU / GUEST (BELUM LOGIN)
             ========================================================= -->
        @if(isset($status) && $status === 'guest')
            <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm p-8 border border-gray-200 text-center">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Cek Reservasi Hotel Anda</h3>
                <p class="text-gray-500 mb-6 text-sm">Anda harus login terlebih dahulu untuk melihat dan mengelola reservasi hotel akomodasi.</p>
                <a href="{{ route('login') }}" class="inline-block bg-[#E19404] hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                    Login untuk Melihat Reservasi
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
             C. SUDAH LOGIN: DAFTAR RESERVASI (TANPA KATALOG)
             ========================================================= -->
        @else
            {{-- ---------- TOOLBAR JUDUL + TOMBOL RESERVASI LAGI ---------- --}}
            <section class="space-y-6 max-w-5xl mx-auto">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-black text-[#234661]">Daftar Reservasi</h2>
                        <span class="text-[11px] font-bold bg-[#FBE39D]/60 text-[#E19404] px-3 py-1 rounded-full">{{ $reservations->count() }} Reservasi Aktif</span>
                    </div>
                    <a href="{{ route('hotels.catalog') }}" class="inline-flex items-center gap-2 bg-[#E19404] hover:bg-orange-600 text-white text-xs sm:text-sm font-extrabold py-3 px-6 rounded-xl transition shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Reservasi Hotel Lagi
                    </a>
                </div>

                {{-- ---------- EMPTY STATE: BELUM PERNAH RESERVASI ---------- --}}
                @if($reservations->isEmpty())
                    <div class="bg-white rounded-3xl border-2 border-dashed border-gray-300 p-12 text-center space-y-4">
                        <div class="mx-auto w-16 h-16 rounded-full bg-[#FFF8E7] flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#E19404]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M1 5h16v14H1zM17 9h3l3 3v7h-6M5.5 19a2 2 0 104 0 2 2 0 00-4 0zm11 0a2 2 0 104 0 2 2 0 00-4 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-black text-gray-800">Anda belum memesan hotel sama sekali</h3>
                        <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                            Belum ada reservasi kamar hotel yang tercatat atas nama Anda. Jelajahi katalog kamar Hotel De Djokja dan amankan tempat menginap Anda untuk BACT 2027.
                        </p>
                        <a href="{{ route('hotels.catalog') }}" class="inline-block bg-[#234661] hover:bg-[#1c3b54] text-white font-extrabold text-sm py-3.5 px-8 rounded-xl transition shadow-md hover:shadow-lg mt-2">
                            Lihat Katalog &amp; Pesan Kamar
                        </a>
                    </div>
                @else
                    {{-- ---------- KARTU RESERVASI MENYAMPING (PENDING DI URUTAN PERTAMA) ---------- --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 items-start">
                        @foreach($reservations->sortByDesc(fn ($r) => $r->status === 'pending') as $reservation)
                            @include('hotels.partials.reservation-card', [
                                'reservation' => $reservation,
                                'statusLabel' => $reservation->status === 'pending' ? 'Pending' : 'Sudah Dibayar',
                            ])
                        @endforeach
                    </div>

                    @if($pendingReservation)
                        <p class="text-xs text-amber-700 bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-center font-medium">
                            Selesaikan pembayaran untuk kode booking <strong>{{ $pendingReservation->booking_code }}</strong> terlebih dahulu sebelum dapat memesan kamar lainnya. Pembatalan hanya berlaku sebelum pembayaran.
                        </p>
                    @endif
                @endif
            </section>
        @endif

    </main>

    <!-- 3. FOOTER (KONSISTEN DENGAN HALAMAN BOOKING) -->
    @include('partials.footer')

</body>
</html>
