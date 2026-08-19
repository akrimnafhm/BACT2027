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
    @include('partials.navbar', [
        'navbarMenuBerita' => true,
        'navbarProfileStyle' => true,
    ])

    <!-- FORM TERSEMBUNYI UNTUK VERIFIKASI EMAIL -->
    <form id="form-send-email-otp" action="{{ route('email.sendOtp') }}" method="POST" class="hidden">@csrf</form>
    <form id="form-verify-email-otp" action="{{ route('email.verifyOtp') }}" method="POST" class="hidden">@csrf</form>

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
                <h2 class="text-lg font-extrabold text-[#234661] mb-6">Informasi Akun</h2>
                
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
                                @if(!empty($user->email_otp_code) || session('email_otp_sent'))
                                    <div class="flex flex-col items-end gap-2">
                                        <div class="flex items-center gap-2">
                                            <input type="text" name="email_otp_code" form="form-verify-email-otp" placeholder="6 Digit" required maxlength="6" class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center tracking-widest text-sm focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none">
                                            <button type="submit" form="form-verify-email-otp" class="text-xs font-bold px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition shadow-sm">Cek</button>
                                        </div>
                                        <div class="text-xs">
                                            @if($emailOtpRemaining > 0)
                                                <span class="text-gray-400 font-semibold" data-countdown="{{ $emailOtpRemaining }}" data-target="email">Kirim ulang dalam <b class="text-gray-600" data-countdown-display>00:00</b></span>
                                            @else
                                                <button type="submit" form="form-send-email-otp" class="font-bold text-[#E19404] hover:text-orange-600 transition cursor-pointer">Kirim Ulang OTP</button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <button type="submit" form="form-send-email-otp" class="text-xs font-bold px-4 py-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-full transition whitespace-nowrap shadow-sm">
                                        Verifikasi
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Nomor WhatsApp (tanpa verifikasi OTP — cukup dicatat untuk kebutuhan panitia) -->
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nomor WhatsApp Aktif</label>
                        <input type="text" id="phone_input" name="phone_number" value="{{ $user->phone_number }}" placeholder="Contoh: 081234567890" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-sm font-semibold text-gray-800">
                        <p class="text-xs text-gray-400 mt-2">Nomor ini digunakan panitia untuk menghubungi Anda.</p>
                    </div>
                </div>
            </div>

            <!-- BOX 2: DATA PRIBADI (Gelar Sudah Dihilangkan) -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
                <!-- Judul tanpa garis bawah dan warna profesional -->
                <h2 class="text-lg font-extrabold text-[#234661] mb-6">Data Pribadi</h2>
                
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
                    <h2 class="text-lg font-extrabold text-[#234661]">Keamanan Akun</h2>
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
    @include('partials.footer')

    <script>
        // COUNTDOWN KIRIM ULANG OTP EMAIL (bertahan saat halaman di-refresh karena sisa waktu dari server)
        function formatCountdown(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        function initOtpCountdowns() {
            document.querySelectorAll('[data-countdown]').forEach(el => {
                let remaining = parseInt(el.dataset.countdown, 10);
                const display = el.querySelector('[data-countdown-display]');
                if (display) display.textContent = formatCountdown(remaining);

                const timer = setInterval(() => {
                    remaining -= 1;
                    if (display) display.textContent = formatCountdown(Math.max(0, remaining));

                    if (remaining <= 0) {
                        clearInterval(timer);
                        const button = document.createElement('button');
                        button.type = 'submit';
                        button.setAttribute('form', 'form-send-email-otp');
                        button.textContent = 'Kirim Ulang OTP';
                        button.className = 'font-bold text-[#E19404] hover:text-orange-600 transition cursor-pointer';
                        el.replaceWith(button);
                    }
                }, 1000);
            });
        }

        document.addEventListener('DOMContentLoaded', initOtpCountdowns);
    </script>
</body>
</html>