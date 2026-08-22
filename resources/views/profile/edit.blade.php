<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - BACT 2027</title>
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
    <div class="max-w-4xl mx-auto px-4 flex-grow w-full mb-16 mt-4">

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

        <!-- KARTU HEADER PROFIL -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="bg-gradient-to-b from-[#234661] via-[#1d394d] to-[#142531] px-8 py-6 flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-[#E19404] text-white flex items-center justify-center text-2xl font-bold shadow-md flex-shrink-0">
                    {{ strtoupper(substr($user->name ?: 'U', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl md:text-2xl font-extrabold text-white truncate">{{ $user->name }}</h1>
                    <p class="text-sm text-white/80 truncate">{{ $user->email }}</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        @if($user->email_verified_at)
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 bg-green-500/20 text-green-300 rounded-full">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Email Terverifikasi
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 bg-red-500/20 text-red-300 rounded-full">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Email Belum Terverifikasi
                            </span>
                        @endif
                        @if(isset($paidTickets) && $paidTickets->isNotEmpty())
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 bg-[#E19404]/20 text-[#FBE39D] rounded-full">
                                {{ $paidTickets->count() }} Tiket Dibeli
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM EDIT DATA PROFIL -->
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- BOX 1: INFORMASI AKUN -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
                <h2 class="text-lg font-extrabold text-[#234661] mb-6">Informasi Akun</h2>

                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Email -->
                    <div class="bg-gray-50 w-full p-4 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Email (Akun Login)</label>
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <input type="email" value="{{ $user->email }}" readonly class="w-full sm:w-auto flex-1 bg-transparent border-none text-gray-800 font-bold focus:ring-0 p-0 outline-none text-sm">

                            @if($user->email_verified_at)
                                <span class="inline-flex items-center text-xs font-bold px-3 py-1 bg-green-100 text-green-700 rounded-full whitespace-nowrap">
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

                    <!-- Nomor WhatsApp -->
                    <div class="bg-gray-50 w-full p-4 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nomor WhatsApp Aktif</label>
                        <input type="text" id="phone_input" name="phone_number" value="{{ $user->phone_number }}" placeholder="Contoh: 081234567890" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-sm font-semibold text-gray-800">
                        <!-- <p class="text-xs text-gray-400 mt-2">Nomor ini digunakan panitia untuk menghubungi Anda.</p> -->
                    </div>
                </div>
            </div>

            <!-- BOX 2: DATA PRIBADI -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
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

            <!-- BOX 3: KEAMANAN AKUN -->
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

            <!-- TOMBOL SIMPAN -->
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-8 rounded-lg shadow-sm transition transform active:scale-95 text-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>

        <!-- BOX 4: TIKET SAYA -->
        <div class="mt-6 bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-extrabold text-[#234661]">Tiket Saya</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Daftar tiket acara yang sudah Anda beli.</p>
                </div>
            </div>

            @if(isset($paidTickets) && $paidTickets->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 uppercase tracking-wider text-xs border-b border-gray-200">
                                <th class="px-4 py-3 font-bold">#</th>
                                <th class="px-4 py-3 font-bold">Tiket</th>
                                <th class="px-4 py-3 font-bold">Kode Tiket</th>
                                <th class="px-4 py-3 font-bold">Invoice</th>
                                <th class="px-4 py-3 font-bold">Tanggal</th>
                                <th class="px-4 py-3 font-bold">Status</th>
                                <th class="px-4 py-3 font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($paidTickets as $index => $ticket)
                                <tr class="hover:bg-gray-50/70 transition">
                                    <td class="px-4 py-3.5 text-gray-400 font-semibold">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3.5">
                                        <span class="font-bold text-gray-900">{{ $ticket->ticket_name }} - {{ $ticket->ticket_category }}</span>
                                        <span class="block text-xs text-gray-400 mt-0.5">{{ $ticket->institution_name }} ({{ $ticket->institution_city }})</span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-block bg-white border-2 border-dashed border-[#E19404] text-[#E19404] font-extrabold tracking-widest px-3 py-1.5 rounded-lg text-xs select-all">{{ $ticket->checkin_token }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-gray-600">{{ $ticket->invoice_number ?: '-' }}</td>
                                    <td class="px-4 py-3.5 text-gray-600">{{ ($ticket->paid_at ?? $ticket->created_at)->format('d M Y') }}</td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-block bg-green-100 text-green-800 px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide">Lunas</span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex flex-wrap gap-2">
                                            @if(!empty($ticket->wa_group_url))
                                                <a href="{{ $ticket->wa_group_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-1.5 px-3 rounded-lg transition shadow-sm">
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm.01 18.2c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.26 8.26 0 01-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 4.54 0 8.24 3.7 8.24 8.24 0 4.54-3.7 8.24-8.23 8.24zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.17.24-.64.8-.78.97-.14.16-.29.18-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z"/></svg>
                                                    Join Grup {{ $ticket->wa_group_label }}
                                                </a>
                                            @endif
                                            <a href="{{ route('invoice.ticket.preview', $ticket->id) }}" class="inline-flex items-center gap-1 bg-[#234661] hover:bg-[#1c3b54] text-white text-xs font-bold py-1.5 px-3 rounded-lg transition shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                Invoice
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-gray-50 border border-dashed border-gray-200 rounded-xl py-10 text-center">
                    <p class="text-sm font-bold text-gray-700">Belum Ada Tiket yang Dibeli</p>
                    <p class="text-xs text-gray-400 mt-1">Silakan beli tiket acara melalui halaman pemesanan tiket.</p>
                    <a href="{{ route('booking.form') }}" class="mt-4 inline-flex items-center gap-1.5 bg-[#E19404] hover:bg-orange-600 text-white text-sm font-bold py-2.5 px-6 rounded-xl transition shadow-md">
                        Beli Tiket Sekarang
                    </a>
                </div>
            @endif
        </div>
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