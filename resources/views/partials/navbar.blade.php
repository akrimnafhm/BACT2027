<!-- NAVBAR PUBLIK (dipakai di semua halaman public: homepage, booking, hotels, profile) -->
@php
    $navbarBg = $navbarBg ?? 'bg-[#FFFFFF] shadow-sm';
    $navbarLogoHref = $navbarLogoHref ?? '/';
    $navbarShowGuestLogin = $navbarShowGuestLogin ?? true;
    $navbarShowAdmin = $navbarShowAdmin ?? true;
    $navbarShowGaleri = $navbarShowGaleri ?? true;
    $navbarMenuBerita = $navbarMenuBerita ?? false;   // profile/edit pakai "Berita" bukan "Galeri"
    $navbarProfileStyle = $navbarProfileStyle ?? false; // profile/edit: dropdown & "Hai" berwarna kuning
@endphp
<nav class="fixed top-0 w-full z-50 px-6 py-4 {{ $navbarBg }} border-b border-gray-100">
    <div class="max-w-7xl mx-auto flex justify-between items-center">

        <a href="{{ $navbarLogoHref }}" class="flex items-center">
            <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-12 w-auto">
        </a>

        <!-- Tombol Hamburger (Mobile < md) -->
        <button type="button" id="navbarMobileToggle" aria-label="Buka menu" aria-expanded="false"
            class="md:hidden flex items-center justify-center w-11 h-11 rounded-xl border border-gray-200 bg-white text-gray-700 hover:text-[#E19404] transition cursor-pointer">
            <svg id="iconOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg id="iconClose" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Menu Navigasi (Desktop; di mobile dipindah ke drawer) -->
        <div class="hidden md:flex space-x-6 items-center">
            <a href="/#beranda" class="font-semibold nav-scroll hover:text-[#E19404] transition {{ request()->is('/') ? 'text-[#E19404]' : 'text-gray-700' }}">Beranda</a>
            <a href="/#pembicara" class="font-semibold nav-scroll hover:text-[#E19404] transition text-gray-700">Pembicara</a>
            <a href="/#jadwal" class="nav-scroll font-semibold hover:text-[#E19404] transition text-gray-700">Jadwal</a>
            <a href="/#lokasi" class="nav-scroll font-semibold hover:text-[#E19404] transition text-gray-700">Lokasi</a>
            @if($navbarMenuBerita)
                <a href="/#berita" class="font-semibold hover:text-[#E19404] transition text-gray-700">Berita</a>
            @elseif($navbarShowGaleri)
                <a href="/#galeri" class="font-semibold nav-scroll hover:text-[#E19404] transition text-gray-700">Galeri</a>
            @endif
            <!-- <a href="/program-ilmiah" class="hover:text-[#E19404] transition {{ request()->is('program-ilmiah*') ? 'text-[#E19404]' : 'text-gray-700' }}">Program Ilmiah</a> -->
            <a href="{{ route('booking.index') }}" class="font-semibold hover:text-[#E19404] transition {{ request()->routeIs('booking.*') || request()->is('booking*') || request()->is('checkout*') ? 'text-[#E19404]' : 'text-gray-700' }}">Pesan Tiket</a>
            <a href="/hotel" class="font-semibold hover:text-[#E19404] transition {{ request()->is('hotel*') ? 'text-[#E19404] font-bold' : 'text-gray-700' }}">Pesan Hotel</a>

            @auth
                @if($navbarShowAdmin && Auth::user()->role === 'admin')
                    <span class="text-gray-300">|</span>
                    <a href="/admin/dashboard" class="hover:text-red-600 transition text-red-500 font-bold flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Panel Admin
                    </a>
                @endif
            @endauth
        </div>

        <!-- Tombol Login / Profil (Desktop only; di mobile dipindah ke drawer) -->
        <div class="hidden md:flex items-center space-x-4">
            @guest
                @if($navbarShowGuestLogin)
                    <a href="/login" class="text-sm font-bold uppercase tracking-widest text-[#FFFFFF] bg-[#FFC32D] hover:bg-[#E19404] px-6 py-2.5 rounded-full transition shadow-md">
                        Masuk
                    </a>
                @endif
            @endguest

            @auth
                <div class="relative group cursor-pointer">
                    <div class="flex items-center space-x-2 {{ $navbarProfileStyle ? 'text-[#E19404]' : 'text-gray-700 hover:text-[#E19404]' }} transition font-bold">
                        <span>Hai, {{ Auth::user()->name }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>

                    <div class="absolute right-0 mt-2 w-48 bg-white border border-gray-100 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                        @if($navbarProfileStyle)
                            <a href="/profile" class="block px-4 py-3 text-sm text-[#E19404] bg-[#FFF8E7] rounded-t-lg font-bold transition">
                                Edit Profil
                            </a>
                        @else
                            <a href="/profile" class="block px-4 py-3 text-sm text-gray-700 hover:bg-[#FBE39D] hover:text-[#E19404] rounded-t-lg font-medium transition">
                                Edit Profil
                            </a>
                        @endif
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

<!-- ================= MOBILE DRAWER (Hamburger) - di luar <nav> agar position:fixed benar ================= -->
<div id="navbarMobileBackdrop" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 hidden"></div>

<div id="navbarMobileDrawer"
    class="fixed top-0 right-0 bottom-0 w-72 max-w-[85vw] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 flex flex-col md:hidden">

    <!-- Header Drawer -->
    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-10 w-auto">
        <button type="button" id="navbarMobileClose" aria-label="Tutup menu"
            class="w-9 h-9 rounded-xl border border-gray-200 text-gray-500 hover:text-[#E19404] flex items-center justify-center transition cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Link Navigasi -->
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
        <a href="/#beranda" class="mobile-nav-link nav-scroll block px-4 py-3 text-sm font-bold text-gray-700 rounded-xl hover:bg-[#FFF8E7] hover:text-[#E19404] transition {{ request()->is('/') ? 'text-[#E19404] bg-[#FFF8E7]' : '' }}">Beranda</a>
        <a href="/#pembicara" class="mobile-nav-link nav-scroll block px-4 py-3 text-sm font-bold text-gray-700 rounded-xl hover:bg-[#FFF8E7] hover:text-[#E19404] transition">Pembicara</a>
        <a href="/#jadwal" class="mobile-nav-link nav-scroll block px-4 py-3 text-sm font-bold text-gray-700 rounded-xl hover:bg-[#FFF8E7] hover:text-[#E19404] transition">Jadwal</a>
        <a href="/#lokasi" class="mobile-nav-link nav-scroll block px-4 py-3 text-sm font-bold text-gray-700 rounded-xl hover:bg-[#FFF8E7] hover:text-[#E19404] transition">Lokasi</a>
        @if($navbarMenuBerita)
            <a href="/#berita" class="mobile-nav-link nav-scroll block px-4 py-3 text-sm font-bold text-gray-700 rounded-xl hover:bg-[#FFF8E7] hover:text-[#E19404] transition">Berita</a>
        @elseif($navbarShowGaleri)
            <a href="/#galeri" class="mobile-nav-link nav-scroll block px-4 py-3 text-sm font-bold text-gray-700 rounded-xl hover:bg-[#FFF8E7] hover:text-[#E19404] transition">Galeri</a>
        @endif
        <a href="{{ route('booking.index') }}" class="mobile-nav-link block px-4 py-3 text-sm font-bold {{ request()->routeIs('booking.*') || request()->is('booking*') || request()->is('checkout*') ? 'text-[#E19404] bg-[#FFF8E7]' : 'text-gray-700' }} rounded-xl hover:bg-[#FFF8E7] hover:text-[#E19404] transition">Pesan Tiket</a>
        <a href="/hotel" class="mobile-nav-link block px-4 py-3 text-sm font-bold {{ request()->is('hotel*') ? 'text-[#E19404] bg-[#FFF8E7]' : 'text-gray-700' }} rounded-xl hover:bg-[#FFF8E7] hover:text-[#E19404] transition">Pesan Hotel</a>

        <!-- Admin Link (jika admin) -->
        @auth
            @if($navbarShowAdmin && Auth::user()->role === 'admin')
                <div class="pt-3 mt-2 border-t border-gray-100">
                    <a href="/admin/dashboard" class="mobile-nav-link block px-4 py-3 text-sm font-bold text-red-500 rounded-xl hover:bg-red-50 transition">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Panel Admin
                        </span>
                    </a>
                </div>
            @endif
        @endauth
    </div>

    <!-- Footer Drawer: Login / Profil -->
    <div class="px-4 py-4 border-t border-gray-100 space-y-2">
        @guest
            @if($navbarShowGuestLogin)
                <a href="/login" class="block text-center text-sm font-bold uppercase tracking-widest text-white bg-[#FFC32D] hover:bg-[#E19404] px-6 py-3 rounded-full transition shadow-md">
                    Masuk
                </a>
            @endif
        @endguest

        @auth
            <div class="flex items-center gap-2 px-2 py-1">
                <div class="w-9 h-9 rounded-full bg-[#FFF8E7] border border-[#E19404]/20 flex items-center justify-center text-[#E19404] font-black text-sm">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <span class="text-sm font-bold {{ $navbarProfileStyle ? 'text-[#E19404]' : 'text-gray-700' }}">Hai, {{ Auth::user()->name }}</span>
            </div>
            <a href="/profile" class="block px-4 py-3 text-sm font-bold text-gray-700 rounded-xl bg-gray-50 hover:bg-[#FFF8E7] hover:text-[#E19404] transition">
                Edit Profil
            </a>
            <form action="{{ route('logout') }}" method="POST" class="block m-0">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-3 text-sm font-bold text-red-600 rounded-xl hover:bg-red-50 transition">
                    Keluar
                </button>
            </form>
        @endauth
    </div>
</div>

<script>
    (function () {
        const drawer = document.getElementById('navbarMobileDrawer');
        const backdrop = document.getElementById('navbarMobileBackdrop');
        const toggle = document.getElementById('navbarMobileToggle');
        const closeBtn = document.getElementById('navbarMobileClose');
        const iconOpen = document.getElementById('iconOpen');
        const iconClose = document.getElementById('iconClose');

        function openMenu() {
            drawer.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
            toggle.setAttribute('aria-expanded', 'true');
            iconOpen.classList.add('hidden');
            iconClose.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            drawer.classList.add('translate-x-full');
            backdrop.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', openMenu);
        closeBtn.addEventListener('click', closeMenu);
        backdrop.addEventListener('click', closeMenu);

        // Tutup saat link drawer diklik (termasuk anchor /#seksi)
        drawer.addEventListener('click', function (e) {
            if (e.target.closest('a, button[type="submit"]')) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });
    })();
</script>
