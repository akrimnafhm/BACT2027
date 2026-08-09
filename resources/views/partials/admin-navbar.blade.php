<!-- NAVBAR ADMIN (dipakai di semua halaman admin) -->
@php
    $active = $active ?? 'dashboard';
    $subNavItems = [
        ['route' => '/admin/dashboard', 'key' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => '/admin/tickets', 'key' => 'tickets', 'label' => 'Kuota & Harga'],
        ['route' => '/admin/participants', 'key' => 'participants', 'label' => 'Tiket Peserta'],
        ['route' => '/admin/hotels', 'key' => 'hotels', 'label' => 'Tiket Hotel'],
        ['route' => '/admin/content', 'key' => 'content', 'label' => 'Kelola Konten'],
        ['route' => '/admin/broadcast', 'key' => 'broadcast', 'label' => 'Broadcast WA'],
        ['route' => '/admin/notifications', 'key' => 'notifications', 'label' => 'Template Notifikasi'],
        ['route' => '/admin/checkin', 'key' => 'checkin', 'label' => 'QR Check-In'],
    ];
@endphp

<nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-30 px-6 py-4">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="/">
                <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-10 w-auto">
            </a>
            <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 bg-[#FBE39D] text-[#E19404] rounded-md hidden sm:inline-block">
                Admin Panel
            </span>
        </div>

        <div class="flex items-center gap-6">
            <span class="text-sm font-semibold text-gray-600 hidden md:inline">
                Halo, <span class="text-gray-900 font-bold">{{ Auth::user()->name }}</span>
            </span>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="border border-red-200 hover:bg-red-50 text-red-600 px-4 py-2 rounded-full text-xs font-bold transition shadow-sm">
                    Keluar Akun
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="bg-white border-b border-gray-200 shadow-sm sticky top-[73px] z-20">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex space-x-6 overflow-x-auto no-scrollbar py-1">
            @foreach($subNavItems as $item)
                <a href="{{ $item['route'] }}" class="border-b-2 {{ $active === $item['key'] ? 'border-[#E19404] text-[#E19404] font-bold' : 'border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 font-semibold' }} py-3.5 px-1 text-sm transition whitespace-nowrap">{{ $item['label'] }}</a>
            @endforeach
        </div>
    </div>
</div>
