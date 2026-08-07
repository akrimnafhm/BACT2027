<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - BACT 2026</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Custom Checkbox Color */
        .checkbox-custom:checked {
            background-color: #E19404;
            border-color: #E19404;
        }
    </style>
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans">
    
    <!-- NAVBAR -->
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

    <!-- KONTEN UTAMA HALAMAN -->
    <div class="max-w-4xl mx-auto px-4 pt-32 pb-16 grow w-full">

        <div class="space-y-8">

            @if(session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ session('error') }}
                </div>
            @endif
            
            <!-- 2. TAMPILAN POST-PURCHASE (E-TICKET JIKA SUDAH LUNAS) -->
            @if($status === 'post_purchase')
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200 relative overflow-hidden">
                    <div class="absolute top-0 right-0 bg-green-500 text-white text-xs font-bold px-4 py-2 rounded-bl-xl">Tiket Dimiliki</div>
                    <h2 class="text-xl font-extrabold text-gray-900 mb-2">E-Ticket Anda</h2>
                    <p class="text-sm text-gray-500 mb-6">Tunjukkan halaman ini atau QR Code di bawah saat kedatangan di meja registrasi.</p>
                    
                    <div class="flex flex-col md:flex-row gap-6 items-center bg-gray-50 p-6 rounded-xl border border-dashed border-gray-300">
                        <div class="w-32 h-32 bg-white p-2 border border-gray-200 rounded-lg flex items-center justify-center shadow-sm">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $booking->id }}-{{ $user->email }}" alt="QR Code" class="w-full">
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="font-extrabold text-[#E19404] text-xl mb-1">{{ str_replace([' - ', ' -', '- '], ': ', $bookedTicket->display_name) }}</h3>
                            <p class="font-bold text-gray-800 text-lg">{{ $booking->name_with_title ?: $user->name }}</p>
                            <p class="text-gray-500 text-sm mb-2">{{ $booking->institution_name }} ({{ $booking->institution_city }})</p>
                            <span class="inline-block bg-green-100 text-green-800 px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide">
                                Status: {{ $booking->status }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 3. TAMPILAN GUEST & INCOMPLETE -->
            @if($status === 'guest')
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200 text-center">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Pilih Tiket Anda</h3>
                    <p class="text-gray-500 mb-6 text-sm">Anda harus login terlebih dahulu untuk melihat dan membeli tiket.</p>
                    <a href="{{ route('login') }}" class="inline-block bg-[#E19404] hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                        Login untuk Membeli Tiket
                    </a>
                </div>
            @elseif($status === 'incomplete')
                <div class="bg-yellow-50 rounded-2xl shadow-sm p-8 border border-yellow-200 text-center">
                    <h3 class="text-lg font-bold text-yellow-800 mb-2">Profil Belum Lengkap</h3>
                    <p class="text-yellow-700 mb-6 text-sm">Mohon lengkapi NIK dan verifikasi kontak Anda terlebih dahulu pada halaman profil.</p>
                    <a href="{{ route('profile.edit') }}" class="inline-block bg-[#FFC32D] hover:bg-[#E19404] text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                        Lengkapi Profil Sekarang
                    </a>
                </div>
            @endif

            <!-- 4. FORM BOOKING (READY) -->
            @if($status === 'ready')
                <form id="bookingForm" action="{{ route('booking.process') }}" method="POST">
                    @csrf
                    
                    <input type="hidden" name="full_name" value="{{ $user->name }}">
                    <input type="hidden" name="nik" value="{{ $user->nik }}">
                    <input type="hidden" name="whatsapp_number" value="{{ $user->phone_number }}">
                    <input type="hidden" name="gmail_account" value="{{ $user->email }}">
                    <input type="hidden" id="name_with_title_hidden" name="name_with_title" value="{{ $existingBooking->name_with_title ?? $user->name }}">

                    <!-- DAFTAR TIKET -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
                        <div class="py-4 px-6 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-extrabold text-gray-900 tracking-wide uppercase">ONLINE SALES</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Pilih kategori tiket yang sesuai</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col">
                            @foreach($tickets as $ticket)
                            <label class="relative border-b border-dashed border-gray-200 last:border-b-0 cursor-pointer hover:bg-gray-50 transition py-4 px-6 flex flex-col md:flex-row md:items-center justify-between gap-3 {{ $ticket->quota <= 0 ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <input type="radio" name="ticket_id" value="{{ $ticket->id }}" required class="peer hidden" 
                                    {{ (isset($existingBooking) && $existingBooking->ticket_id == $ticket->id) ? 'checked' : '' }}
                                    {{ $ticket->quota <= 0 ? 'disabled' : '' }}>
                                
                                <div class="absolute inset-0 border-2 border-transparent peer-checked:border-[#E19404] peer-checked:bg-[#FFF8E7] transition pointer-events-none"></div>

                                <div class="relative z-10 md:w-40 shrink-0">
                                    <span class="text-base font-extrabold text-gray-800">Rp{{ number_format($ticket->price, 0, ',', '.') }}</span>
                                </div>

                                <div class="relative z-10 flex-1">
                                    <h4 class="font-bold text-gray-900 text-sm uppercase tracking-wide">{{ str_replace([' - ', ' -', '- '], ': ', $ticket->display_name) }}</h4>
                                    @if($ticket->end_date && $ticket->end_date > now())
                                        <p class="text-xs text-gray-500 mt-0.5">Penjualan berakhir {{ \Carbon\Carbon::parse($ticket->end_date)->format('d M Y') }}</p>
                                    @else
                                        <p class="text-xs text-gray-500 mt-0.5">Penjualan reguler</p>
                                    @endif
                                </div>

                                <div class="relative z-10 text-right md:w-32 flex justify-end items-center gap-3">
                                    @if($ticket->quota > 0)
                                        <span class="inline-block bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-bold peer-checked:hidden border border-green-200">Available</span>
                                        <div class="hidden peer-checked:flex items-center justify-center w-6 h-6 bg-[#E19404] rounded-full text-white shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                    @else
                                        <span class="inline-block bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs font-bold border border-gray-200">Sold Out</span>
                                    @endif
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- SINGLE BOX: DATA DIRI & INFORMASI INSTANSI -->
                    <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200 mb-8 space-y-8">
                        
                        <!-- Judul Box -->
                        <div>
                            <h3 class="text-xl font-extrabold text-gray-900 border-b pb-4">Data Diri & Informasi Instansi <span class="text-red-500">*</span></h3>
                            <p class="text-xs text-gray-400 mt-2">Data identitas di bawah diambil secara otomatis dari profil akun Anda untuk menjaga keamanan data.</p>
                        </div>

                        <!-- 1. BARIS ATAS & BAWAH: DATA INFORMASI PROFIL -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">E-mail (Gmail)</span>
                                    <span class="block text-sm font-bold text-gray-800 mt-0.5">{{ $user->email }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Nomor WhatsApp Aktif</span>
                                    <span class="block text-sm font-bold text-gray-800 mt-0.5">{{ $user->phone_number }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
                                <div>
                                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Nomor Induk Kependudukan (NIK)</span>
                                    <span class="block text-sm font-bold text-gray-800 mt-0.5">{{ $user->nik }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Nama Lengkap (Sesuai KTP)</span>
                                    <span class="block text-sm font-bold text-gray-800 mt-0.5">{{ $user->name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. BARIS TENGAH: FORMAT PENULISAN GELAR -->
                        @php
                            $dbGelarDepan = $existingBooking->gelar_depan ?? '';
                            $dbGelarBelakang = $existingBooking->gelar_belakang ?? '';
                            
                            if (empty($dbGelarDepan) && empty($dbGelarBelakang)) {
                                $fullNameDb = $existingBooking->name_with_title ?? ($existingBooking->full_name ?? '');
                                $baseNameDb = $user->name ?? '';

                                if (!empty($fullNameDb) && !empty($baseNameDb) && strcasecmp(trim($fullNameDb), trim($baseNameDb)) !== 0) {
                                    $pos = stripos($fullNameDb, $baseNameDb);
                                    if ($pos !== false) {
                                        if ($pos > 0) {
                                            $dbGelarDepan = trim(substr($fullNameDb, 0, $pos));
                                        }
                                        $afterLen = $pos + strlen($baseNameDb);
                                        if ($afterLen < strlen($fullNameDb)) {
                                            $dbGelarBelakang = ltrim(substr($fullNameDb, $afterLen), ', ');
                                        }
                                    }
                                }
                            }
                        @endphp
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Penulisan Nama & Gelar untuk Sertifikat (Sesuai SKP) <span class="text-red-500">*</span></label>
                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 bg-white p-3 rounded-xl border border-gray-300">
                                <input type="text" id="gelar_depan" name="gelar_depan" value="{{ old('gelar_depan', $dbGelarDepan) }}" placeholder="Gelar Depan (cth: dr.)" class="w-full sm:w-44 text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
                                <span class="text-sm font-bold text-gray-800 whitespace-nowrap px-1">{{ $user->name }},</span>
                                <input type="text" id="gelar_belakang" name="gelar_belakang" value="{{ old('gelar_belakang', $dbGelarBelakang) }}" placeholder="Gelar Belakang (cth: Sp.PK, M.Biomed)" class="w-full sm:flex-1 text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
                            </div>
                            <p class="text-xs text-gray-400 mt-1.5">Kosongkan kolom gelar depan atau belakang apabila tidak memiliki gelar.</p>
                        </div>

                        <!-- 3. BARIS BAWAH: EMAIL PLATARAN SEHAT & PROFESI MEDIS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">E-mail Pelataran Sehat <span class="text-red-500">*</span></label>
                                <input type="email" name="plataran_sehat_email" value="{{ old('plataran_sehat_email', $existingBooking->plataran_sehat_email ?? '') }}" required placeholder="Email terdaftar di Plataran Sehat Kemenkes" class="w-full text-sm px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Profesi Medis <span class="text-red-500">*</span></label>
                                <select name="profession" required class="w-full text-sm px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition bg-white cursor-pointer">
                                    <option value="" disabled selected>-- Pilih Profesi Sesuai SKP --</option>
                                    @php
                                        $professions = [
                                            'Dokter',
                                            'Tenaga Teknologi Laboratorium Medik',
                                            'Teknisi Pelayanan Darah',
                                            'Dokter Spesialis Penyakit Dalam',
                                            'Dokter Spesialis Anak',
                                            'Dokter Spesialis Patologi Klinik',
                                            'Tenaga Teknologi Laboratorium Medik Level 5',
                                            'Tenaga Teknologi Laboratorium Medik Level 6',
                                            'Teknisi Pelayanan Darah Level 5',
                                            'Perawat Vokasi',
                                            'Perawat Vokasi Level 5',
                                            'Perawat Vokasi Level 6',
                                            'Ners',
                                            'Ners Spesialis Keperawatan Komunitas',
                                            'Ners Spesialis Keperawatan Anak',
                                            'Ners Spesialis Keperawatan Maternitas',
                                            'Ners Spesialis Keperawatan Medikal Bedah',
                                            'Ners Spesialis Keperawatan Geriatri',
                                            'Ners Spesialis Keperawatan Jiwa',
                                            'Ners Spesialis Keperawatan Onkologi',
                                            'Ners Spesialis Keperawatan Kardiovaskuler',
                                            'Ners Spesialis Keperawatan Gawat Darurat Kritis',
                                            'Lainnya / Umum'
                                        ];
                                        $selectedProf = old('profession', $existingBooking->profession ?? '');
                                    @endphp
                                    @foreach($professions as $prof)
                                        <option value="{{ $prof }}" {{ $selectedProf == $prof ? 'selected' : '' }}>{{ $prof }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- 4. BARIS INSTANSI: NAMA RS / INSTANSI & PROVINSI -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama RS / Universitas / Instansi <span class="text-red-500">*</span></label>
                                <input type="text" name="institution_name" value="{{ old('institution_name', $existingBooking->institution_name ?? '') }}" required placeholder="Contoh: RSUD Dr. Soetomo" class="w-full text-sm px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Provinsi Lokasi Instansi <span class="text-red-500">*</span></label>
                                <select id="provinsi" name="institution_province" required class="w-full text-sm px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition bg-white cursor-pointer">
                                    <option value="">-- Pilih Provinsi --</option>
                                </select>
                            </div>
                        </div>

                        <!-- 5. BARIS PALING BAWAH: KABUPATEN & KECAMATAN -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kabupaten / Kota Instansi <span class="text-red-500">*</span></label>
                                <select id="kabupaten" name="institution_city" required disabled class="w-full text-sm px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition disabled:bg-gray-100 disabled:cursor-not-allowed bg-white cursor-pointer">
                                    <option value="">-- Pilih Kabupaten --</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kecamatan Instansi <span class="text-red-500">*</span></label>
                                <select id="kecamatan" name="institution_district" required disabled class="w-full text-sm px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition disabled:bg-gray-100 disabled:cursor-not-allowed bg-white cursor-pointer">
                                    <option value="">-- Pilih Kecamatan --</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <!-- TOMBOL PEMBAYARAN -->
                    <div class="flex justify-end">
                        <button type="submit" class="w-full md:w-auto bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-8 rounded-xl transition shadow-md text-[15px] transform hover:-translate-y-0.5 whitespace-nowrap">
                            Lanjutkan Pembayaran
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- FOOTER AREA -->
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

    <!-- SCRIPT REALTIME USER-SPECIFIC DRAFT & WILAYAH EMSIFA -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- 0. BERSIHKAN KUNCI GLOBAL LAMA ---
            ['bact_prov_id', 'bact_kab_name', 'bact_kec_name'].forEach(k => localStorage.removeItem(k));

            // --- 1. SETUP DRAFT UNIK PER USER & SAFETY LOCK ---
            const currentUserId = "{{ Auth::id() ?? 'guest' }}";
            const DRAFT_KEY = 'bact_draft_user_' + currentUserId;
            let isRestoringDraft = true;

            function loadDraft() {
                try {
                    const saved = localStorage.getItem(DRAFT_KEY);
                    return saved ? JSON.parse(saved) : {};
                } catch (e) {
                    return {};
                }
            }

            function saveDraft() {
                // Kunci pengaman agar tidak menyimpan saat pemuatan awal
                if (isRestoringDraft) return;

                const bookingForm = document.getElementById('bookingForm');
                if (!bookingForm) return;

                const selectedTicket = bookingForm.querySelector('input[name="ticket_id"]:checked');
                const gdEl = document.getElementById('gelar_depan');
                const gbEl = document.getElementById('gelar_belakang');

                const draftData = {
                    ticket_id: selectedTicket ? selectedTicket.value : '',
                    gelar_depan: gdEl ? gdEl.value : '',
                    gelar_belakang: gbEl ? gbEl.value : '',
                    plataran_sehat_email: bookingForm.querySelector('input[name="plataran_sehat_email"]')?.value || '',
                    profession: bookingForm.querySelector('select[name="profession"]')?.value || '',
                    institution_name: bookingForm.querySelector('input[name="institution_name"]')?.value || '',
                    institution_province: document.getElementById('provinsi')?.value || '',
                    institution_city: document.getElementById('kabupaten')?.value || '',
                    institution_district: document.getElementById('kecamatan')?.value || ''
                };
                localStorage.setItem(DRAFT_KEY, JSON.stringify(draftData));
            }

            const draft = loadDraft();

            // --- 2. PENGGABUNGAN GELAR DEPAN + NAMA + GELAR BELAKANG ---
            const gelarDepanInput = document.getElementById('gelar_depan');
            const gelarBelakangInput = document.getElementById('gelar_belakang');
            const hiddenNameTitle = document.getElementById('name_with_title_hidden');
            const baseName = "{{ $user->name ?? '' }}";

            function updateNameWithTitle() {
                if (!hiddenNameTitle) return;
                const dep = gelarDepanInput ? gelarDepanInput.value.trim() : '';
                const bel = gelarBelakangInput ? gelarBelakangInput.value.trim() : '';
                
                let result = baseName;
                if (dep) {
                    result = dep + (dep.endsWith('.') ? ' ' : '. ') + result;
                }
                if (bel) {
                    result = result + ', ' + bel;
                }
                hiddenNameTitle.value = result;
            }

            // --- 3. KEMBALIKAN DATA DRAFT (PRIORITAS UTAMA ATAS DATABASE) ---
            if (draft.ticket_id) {
                const radio = document.querySelector(`input[name="ticket_id"][value="${draft.ticket_id}"]`);
                if (radio && !radio.disabled) radio.checked = true;
            }
            if (gelarDepanInput && draft.gelar_depan !== undefined) {
                gelarDepanInput.value = draft.gelar_depan;
            }
            if (gelarBelakangInput && draft.gelar_belakang !== undefined) {
                gelarBelakangInput.value = draft.gelar_belakang;
            }
            if (draft.plataran_sehat_email) {
                const el = document.querySelector('input[name="plataran_sehat_email"]');
                if (el) el.value = draft.plataran_sehat_email;
            }
            if (draft.profession) {
                const el = document.querySelector('select[name="profession"]');
                if (el) el.value = draft.profession;
            }
            if (draft.institution_name) {
                const el = document.querySelector('input[name="institution_name"]');
                if (el) el.value = draft.institution_name;
            }

            updateNameWithTitle();

            // --- 4. DATA WILAYAH EMSIFA DENGAN AUTO-RESTORE DRAFT PER USER ---
            const apiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';
            const selectProv = document.getElementById('provinsi');
            const selectKab = document.getElementById('kabupaten');
            const selectKec = document.getElementById('kecamatan');

            const dbCity = "{{ $existingBooking->institution_city ?? '' }}";
            const dbDistrict = "{{ $existingBooking->institution_district ?? '' }}";

            if (selectProv && selectKab && selectKec) {
                selectProv.addEventListener('change', saveDraft);
                selectKab.addEventListener('change', saveDraft);
                selectKec.addEventListener('change', saveDraft);

                // 1. Load Provinsi
                fetch(`${apiBase}/provinces.json`)
                    .then(response => response.json())
                    .then(provinces => {
                        provinces.forEach(prov => {
                            selectProv.innerHTML += `<option value="${prov.name}" data-id="${prov.id}">${prov.name}</option>`;
                        });
                        
                        const targetProv = draft.institution_province || "{{ $existingBooking->institution_province ?? '' }}";
                        if (targetProv) {
                            selectProv.value = targetProv;
                            selectProv.dispatchEvent(new Event('change'));
                        }
                    })
                    .catch(error => console.error('Error fetching provinces:', error));

                // 2. Load Kabupaten
                selectProv.addEventListener('change', (e) => {
                    const selectedOption = e.target.options[e.target.selectedIndex];
                    const provId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                    
                    selectKab.innerHTML = '<option value="">-- Pilih Kabupaten --</option>';
                    selectKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                    selectKab.disabled = true;
                    selectKec.disabled = true;

                    if(provId) {
                        fetch(`${apiBase}/regencies/${provId}.json`)
                            .then(response => response.json())
                            .then(regencies => {
                                regencies.forEach(kab => {
                                    selectKab.innerHTML += `<option value="${kab.name}" data-id="${kab.id}">${kab.name}</option>`;
                                });
                                selectKab.disabled = false;

                                const targetKab = draft.institution_city || dbCity;
                                if (targetKab) {
                                    let optionFound = Array.from(selectKab.options).find(opt => opt.value === targetKab);
                                    if(optionFound) {
                                        selectKab.value = targetKab;
                                        selectKab.dispatchEvent(new Event('change'));
                                    }
                                }
                            });
                    }
                });

                // 3. Load Kecamatan
                selectKab.addEventListener('change', (e) => {
                    if(e.target.selectedIndex < 0) return;
                    
                    const selectedOption = e.target.options[e.target.selectedIndex];
                    const kabId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                    
                    selectKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                    selectKec.disabled = true;

                    if(kabId) {
                        fetch(`${apiBase}/districts/${kabId}.json`)
                            .then(response => response.json())
                            .then(districts => {
                                districts.forEach(kec => {
                                    selectKec.innerHTML += `<option value="${kec.name}">${kec.name}</option>`;
                                });
                                selectKec.disabled = false;

                                const targetKec = draft.institution_district || dbDistrict;
                                if (targetKec) {
                                    let optionFound = Array.from(selectKec.options).find(opt => opt.value === targetKec);
                                    if(optionFound) {
                                        selectKec.value = targetKec;
                                    }
                                }
                            });
                    }
                });
            }

            // --- 5. BUKA KUNCI PENGAMAN & PASANG PENDENGAR EKSPLISIT ---
            setTimeout(() => {
                isRestoringDraft = false;
            }, 600);

            if (gelarDepanInput && gelarBelakangInput) {
                ['input', 'change', 'blur'].forEach(evt => {
                    gelarDepanInput.addEventListener(evt, function() {
                        updateNameWithTitle();
                        saveDraft();
                    });
                    gelarBelakangInput.addEventListener(evt, function() {
                        updateNameWithTitle();
                        saveDraft();
                    });
                });
            }

            const bookingForm = document.getElementById('bookingForm');
            if (bookingForm) {
                bookingForm.addEventListener('input', saveDraft);
                bookingForm.addEventListener('change', saveDraft);

                bookingForm.addEventListener('submit', function() {
                    localStorage.removeItem(DRAFT_KEY);
                });
            }
        });
    </script>
</body>
</html>