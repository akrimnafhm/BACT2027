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

        <!-- Menu Navigasi -->
        <div class="hidden md:flex space-x-6 text-[13px] lg:text-sm font-semibold">
            <a href="/#beranda" class="nav-scroll hover:text-[#E19404] transition {{ request()->is('/') ? 'text-[#E19404]' : 'text-gray-700' }}">Beranda</a>
            <a href="/#pembicara" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Pembicara</a>
            <a href="/#jadwal" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Jadwal</a>
            <a href="/#lokasi" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Lokasi</a>
            @if($navbarMenuBerita)
                <a href="/#berita" class="hover:text-[#E19404] transition text-gray-700">Berita</a>
            @elseif($navbarShowGaleri)
                <a href="/#galeri" class="nav-scroll hover:text-[#E19404] transition text-gray-700">Galeri</a>
            @endif
            <!-- <a href="/program-ilmiah" class="hover:text-[#E19404] transition {{ request()->is('program-ilmiah*') ? 'text-[#E19404]' : 'text-gray-700' }}">Program Ilmiah</a> -->
            <a href="{{ route('booking.index') }}" class="hover:text-[#E19404] transition {{ request()->routeIs('booking.*') || request()->is('booking*') || request()->is('checkout*') ? 'text-[#E19404]' : 'text-gray-700' }}">Pesan Tiket</a>
            <a href="/hotel" class="hover:text-[#E19404] transition {{ request()->is('hotel*') ? 'text-[#E19404] font-bold' : 'text-gray-700' }}">Pesan Hotel</a>

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

        <!-- Tombol Login / Profil -->
        <div class="flex items-center space-x-4">
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
