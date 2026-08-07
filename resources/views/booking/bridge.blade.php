<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Tiket - BACT 2026</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans pt-28">

    <nav class="fixed top-0 w-full z-50 px-6 py-4 bg-[#FFFFFF] shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="/" class="flex items-center">
                <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-12 w-auto">
            </a>

            <div class="hidden md:flex space-x-6 text-[13px] lg:text-sm font-semibold">
                <a href="/#beranda" class="nav-scroll hover:text-[#E19404] transition {{ request()->is('/') ? 'text-[#E19404]' : 'text-gray-700' }}">Beranda</a>
                <a href="/#pembicara" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Pembicara</a>
                <a href="/#jadwal" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Jadwal</a>
                <a href="/#lokasi" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Lokasi</a>
                <a href="/#galeri" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Galeri</a>
                <a href="/program-ilmiah" class="hover:text-[#E19404] transition {{ request()->is('program-ilmiah*') ? 'text-[#E19404]' : 'text-gray-700' }}">Program Ilmiah</a>
                <a href="{{ route('booking.index') }}" class="hover:text-[#E19404] transition {{ request()->routeIs('booking.*') || request()->is('booking*') || request()->is('checkout*') ? 'text-[#E19404]' : 'text-gray-700' }}">Pesan Tiket</a>
                <a href="/hotel"
                    class="hover:text-[#E19404] transition {{ request()->is('hotel*') ? 'text-[#E19404]' : 'text-gray-700' }}">Pesan
                    Hotel
                </a>
                @auth
                    @if(Auth::user()->role === 'admin')
                        <span class="text-gray-300">|</span>
                        <a href="/admin/dashboard"
                            class="hover:text-red-600 transition text-red-500 font-bold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Panel Admin
                        </a>
                    @endif
                @endauth
            </div>

            <div class="flex items-center space-x-4">
                @guest
                    <a href="/login"
                        class="text-sm font-bold uppercase tracking-widest text-[#FFFFFF] bg-[#FFC32D] hover:bg-[#E19404] px-6 py-2.5 rounded-full transition shadow-md">
                        Masuk
                    </a>
                @endguest

                @auth
                    <div class="relative group cursor-pointer">
                        <div class="flex items-center space-x-2 text-gray-700 hover:text-[#E19404] transition font-bold">
                            <span>Hai, {{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </div>

                        <div
                            class="absolute right-0 mt-2 w-48 bg-white border border-gray-100 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                            <a href="/profile"
                                class="block px-4 py-3 text-sm text-gray-700 hover:bg-[#FBE39D] hover:text-[#E19404] rounded-t-lg font-medium transition">
                                Edit Profil
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="block m-0">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-b-lg font-medium transition">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <div class="text-center max-w-3xl mx-auto space-y-3 pt-4">
        <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">Pemesanan Tiket</span>
        <h1 class="text-3xl md:text-4xl font-black text-gray-900">Pemesanan Tiket Acara</h1>
        <p class="text-sm md:text-base text-gray-600 leading-relaxed">
           Pesan tiket acara sekarang agar terdaftar di acara yang dipilih.
        </p>
    </div>

    <div class="max-w-4xl mx-auto px-4 pt-10 pb-16 grow w-full">
        @if(session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if($status === 'post_purchase')
            <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 bg-green-500 text-white text-xs font-bold px-4 py-2 rounded-bl-xl">Tiket Dimiliki</div>
                <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Pesanan Tiket Anda</h1>
                <p class="text-sm text-gray-500 mb-6">Halaman ini menampilkan QR pesanan tiket Anda. Simpan halaman ini untuk registrasi.</p>

                <div class="flex flex-col md:flex-row gap-6 items-center bg-gray-50 p-6 rounded-xl border border-dashed border-gray-300">
                    <div class="w-32 h-32 bg-white p-2 border border-gray-200 rounded-lg flex items-center justify-center shadow-sm">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $booking->id }}-{{ $user->email }}" alt="QR Code" class="w-full">
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="font-extrabold text-[#E19404] text-xl mb-1">{{ $bookedTicket ? str_replace([' - ', ' -', '- '], ': ', $bookedTicket->display_name) : 'Tiket Anda' }}</h2>
                        <p class="font-bold text-gray-800 text-lg">{{ $booking->name_with_title ?: $user->name }}</p>
                        <p class="text-gray-500 text-sm mb-2">{{ $booking->institution_name }} ({{ $booking->institution_city }})</p>
                        <span class="inline-block bg-green-100 text-green-800 px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide">
                            Status: {{ $booking->status }}
                        </span>
                    </div>
                </div>
            </div>
        @elseif($status === 'incomplete')
            <div class="rounded-2xl">
                <div class="max-w-3xl mx-auto bg-yellow-50 rounded-2xl shadow-sm p-8 border border-yellow-200 text-center">
                    <h3 class="text-lg font-bold text-yellow-800 mb-2">Profil Belum Lengkap</h3>
                    <p class="text-yellow-700 mb-6 text-sm">Mohon lengkapi NIK dan verifikasi kontak Anda terlebih dahulu pada halaman profil sebelum memesan tiket acara.</p>
                    <a href="{{ route('profile.edit') }}" class="inline-block bg-[#FFC32D] hover:bg-[#E19404] text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                        Lengkapi Profil Sekarang
                    </a>
                </div>
            </div>
        @elseif($status === 'pending')
            <div class="rounded-2xl">
                <div class="max-w-3xl mx-auto bg-yellow-50 rounded-2xl shadow-sm p-8 border border-yellow-200 text-center">
                    <h3 class="text-lg font-bold text-yellow-800 mb-2">Pembayaran Belum Selesai</h3>
                    <p class="text-yellow-700 mb-6 text-sm">Mohon selesaikan pembayaran Anda untuk mendapatkan QR tiket acara.</p>
                    <a href="{{ route('checkout', $existingBooking->id) }}" class="inline-flex justify-center items-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-xl transition shadow-md">
                        Lanjutkan Pembayaran
                    </a>
                </div>
            </div>
        @elseif($status === 'bridge')
            <script>
                window.location.href = "{{ route('booking.form') }}";
            </script>
        @else
            <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200 text-center">
                <h1 class="text-2xl font-extrabold text-gray-900 mb-3">Pesan Tiket</h1>
                <p class="text-sm text-gray-500 mb-6">Silakan login untuk melanjutkan pemesanan tiket dan menampilkan QR pesanan Anda di sini.</p>
                <a href="{{ route('login') }}" class="inline-block bg-[#E19404] hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                    Login untuk Melanjutkan
                </a>
            </div>
        @endif
    </div>

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
