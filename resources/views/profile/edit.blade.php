<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - BACT 2026</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans pt-28">

    <!-- NAVBAR (Konsisten dengan Halaman Lain) -->
    <nav class="fixed top-0 w-full z-50 px-6 py-4 bg-[#FFFFFF] shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="/" class="flex items-center">
                <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-12 w-auto">
            </a>

            <!-- Menu Navigasi -->
            <div class="hidden md:flex space-x-6 text-[13px] lg:text-sm font-semibold text-gray-700">
                <a href="/" class="hover:text-[#E19404] transition">Beranda</a>
                <a href="/#pembicara" class="hover:text-[#E19404] transition">Pembicara</a>
                <a href="/#jadwal" class="hover:text-[#E19404] transition">Jadwal</a>
                <a href="/#berita" class="hover:text-[#E19404] transition">Berita</a>
                <a href="/#lokasi" class="hover:text-[#E19404] transition">Lokasi</a>
                <a href="/program-ilmiah" class="hover:text-[#E19404] transition">Program Ilmiah</a>
                <a href="{{ route('booking.index') }}" class="hover:text-[#E19404] transition">Pesan Tiket</a>
                <a href="/#hotel" class="hover:text-[#E19404] transition">Pesan Hotel</a>
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
                        <div class="flex items-center space-x-2 text-[#E19404] transition font-bold">
                            <span>Hai, {{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>

                        <div class="absolute right-0 mt-2 w-48 bg-white border border-gray-100 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                            <a href="/profile" class="block px-4 py-3 text-sm text-[#E19404] bg-[#FFF8E7] rounded-t-lg font-bold transition">
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

    <!-- FORM TERSEMBUNYI UNTUK VERIFIKASI -->
    <form id="form-send-email-otp" action="{{ route('email.sendOtp') }}" method="POST" class="hidden">@csrf</form>
    <form id="form-verify-email-otp" action="{{ route('email.verifyOtp') }}" method="POST" class="hidden">@csrf</form>
    <form id="form-send-otp" action="{{ route('phone.sendOtp') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="phone_number" id="hidden_phone_number">
    </form>
    <form id="form-verify-otp" action="{{ route('phone.verifyOtp') }}" method="POST" class="hidden">@csrf</form>

    <!-- KONTEN UTAMA PROFIL -->
    <div class="max-w-3xl mx-auto px-4 flex-grow w-full mb-16 mt-4">
        
        <!-- Flash Message -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl mb-6 text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl mb-6 text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl mb-6 text-sm font-medium shadow-sm">
                <p class="font-bold mb-1">Terdapat kesalahan pada isian Anda:</p>
                @foreach ($errors->all() as $error)
                    <p class="text-xs">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- BOX 1: INFORMASI AKUN -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
                <!-- Judul tanpa garis bawah dan warna profesional -->
                <h2 class="text-lg font-extrabold text-gray-900 mb-6">Informasi Akun</h2>
                
                <div class="space-y-6">
                    <!-- Email -->
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Email (Akun Login)</label>
                        <div class="flex items-center justify-between">
                            <input type="email" value="{{ $user->email }}" readonly class="w-full bg-transparent border-none text-gray-800 font-bold focus:ring-0 p-0 outline-none text-sm">
                            
                            @if($user->email_verified_at)
                                <span class="inline-flex items-center text-xs font-bold px-3 py-1 bg-green-100 text-green-700 rounded-full">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Terverifikasi
                                </span>
                            @else
                                @if(session('email_otp_sent'))
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="email_otp_code" form="form-verify-email-otp" placeholder="6 Digit" required maxlength="6" class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center tracking-widest text-sm focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none">
                                        <button type="submit" form="form-verify-email-otp" class="text-xs font-bold px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition shadow-sm">Cek</button>
                                    </div>
                                @else
                                    <button type="submit" form="form-send-email-otp" class="text-xs font-bold px-4 py-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-full transition whitespace-nowrap shadow-sm">
                                        Verifikasi
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Nomor WhatsApp -->
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nomor WhatsApp Aktif</label>
                        <div class="flex items-start md:items-center flex-col md:flex-row gap-3">
                            <input type="text" id="phone_input" name="phone_number" value="{{ $user->phone_number }}" placeholder="Contoh: 081234567890" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-sm font-semibold text-gray-800">
                            
                            <div class="md:w-auto w-full flex justify-end">
                                @if($user->phone_verified_at)
                                    <span class="inline-flex items-center text-xs font-bold px-3 py-1 bg-green-100 text-green-700 rounded-full">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Terverifikasi
                                    </span>
                                @else
                                    @if(session('otp_sent'))
                                        <div class="flex items-center gap-2">
                                            <input type="text" name="otp_code" form="form-verify-otp" placeholder="6 Digit" required maxlength="6" class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center tracking-widest text-sm focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none">
                                            <button type="submit" form="form-verify-otp" class="text-xs font-bold px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition shadow-sm">Cek</button>
                                        </div>
                                    @else
                                        <button type="button" onclick="submitOtpRequest()" class="text-xs font-bold px-5 py-2.5 bg-[#FFC32D] hover:bg-[#E19404] text-white rounded-lg transition whitespace-nowrap shadow-sm">
                                            Kirim OTP
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOX 2: DATA PRIBADI (Gelar Sudah Dihilangkan) -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
                <!-- Judul tanpa garis bawah dan warna profesional -->
                <h2 class="text-lg font-extrabold text-gray-900 mb-6">Data Pribadi</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nama Lengkap -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap (Sesuai KTP) <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ $user->name }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-sm font-medium text-gray-800">
                    </div>

                    <!-- NIK -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" name="nik" value="{{ $user->nik }}" placeholder="16 Digit NIK" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-sm font-medium text-gray-800">
                    </div>

                    <!-- Jenis Kelamin -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jenis Kelamin</label>
                        <select name="gender" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition bg-white text-sm font-medium text-gray-800">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki" {{ $user->gender == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Perempuan" {{ $user->gender == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- BOX 3: KEAMANAN AKUN (GANTI PASSWORD) -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
                <div class="mb-6">
                    <h2 class="text-lg font-extrabold text-gray-900">Keamanan Akun</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Kosongkan seluruh kolom kata sandi di bawah jika Anda tidak ingin merubah password saat ini.</p>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password Saat Ini</label>
                        <input type="password" name="current_password" placeholder="••••••••" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-sm">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password Baru</label>
                            <input type="password" name="new_password" placeholder="Minimal 8 karakter" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Konfirmasi Password Baru</label>
                            <input type="password" name="new_password_confirmation" placeholder="Ulangi password baru" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOMBOL SIMPAN (Persegi dengan ujung halus 'rounded-lg', proporsional) -->
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-8 rounded-lg shadow-sm transition transform active:scale-95 text-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
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

    <script>
        function submitOtpRequest() {
            const phoneValue = document.getElementById('phone_input').value;
            if (!phoneValue) {
                alert('Mohon ketik nomor WhatsApp Anda terlebih dahulu!');
                return;
            }
            document.getElementById('hidden_phone_number').value = phoneValue;
            document.getElementById('form-send-otp').submit();
        }
    </script>
</body>
</html>