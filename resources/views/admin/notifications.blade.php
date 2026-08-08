<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Notifikasi - Admin BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans">

    <!-- 1. NAVBAR UTAMA -->
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

    <!-- 2. SUB-NAVBAR -->
    <div class="bg-white border-b border-gray-200 shadow-sm sticky top-[73px] z-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-6 overflow-x-auto no-scrollbar py-1">
                <a href="/admin/dashboard" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Dashboard</a>
                <a href="/admin/tickets" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Kuota & Harga</a>
                <a href="/admin/participants" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Tiket Peserta</a>
                <a href="/admin/hotels" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Tiket Hotel</a>
                <a href="/admin/submissions" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Karya Lomba</a>
                <a href="/admin/content" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Kelola Konten</a>
                <a href="/admin/broadcast" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Broadcast WA</a>
                <a href="/admin/notifications" class="border-b-2 border-[#E19404] text-[#E19404] py-3.5 px-1 text-sm font-bold transition whitespace-nowrap">Template Notifikasi</a>
                <a href="/admin/checkin" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">QR Check-In</a>
            </div>
        </div>
    </div>

    <!-- 3. KONTEN UTAMA -->
    <main class="max-w-4xl mx-auto px-6 py-10 flex-grow w-full space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-black text-gray-900">Template Notifikasi Tiket Lunas</h1>
            <p class="text-sm text-gray-500 mt-1">Ubah isi pesan notifikasi yang dikirim otomatis ke peserta ketika pembayaran tiket berhasil (via WhatsApp & Email).</p>
        </div>

        <!-- Daftar Placeholder -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs font-extrabold text-[#E19404] uppercase tracking-wider mb-2">Placeholder yang didukung:</p>
            <div class="flex flex-wrap gap-2">
                @foreach(['{nama}', '{tiket}', '{id_pesanan}', '{kode_tiket}', '{invoice}', '{harga}', '{email}', '{qr}'] as $ph)
                    <code class="px-2 py-1 bg-[#FFF8E7] border border-[#E19404]/30 rounded-lg text-xs font-bold text-gray-800">{{ $ph }}</code>
                @endforeach
            </div>
            <p class="text-[11px] text-gray-400 mt-2">Placeholder <code class="font-bold">{qr}</code> menentukan posisi gambar QR. Jika tidak dipakai, QR akan ditambahkan otomatis di akhir pesan (saat opsi QR aktif).</p>
        </div>

        <!-- Form WhatsApp -->
        @php($wa = $templates->firstWhere('key', 'ticket_paid_wa'))
        @if($wa)
            <form action="{{ route('admin.notifications.update') }}" method="POST" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                @csrf
                <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-900">{{ $wa->label }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Dikirim via Fonnte ke WhatsApp peserta.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                        <input type="checkbox" name="ticket_paid_wa_is_active" value="1" {{ $wa->is_active ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                        Aktif
                    </label>
                </div>

                <div class="p-6 sm:p-8 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Isi Pesan WhatsApp</label>
                        <textarea name="ticket_paid_wa_body" rows="10" required
                            class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] font-mono leading-relaxed">{{ $wa->body }}</textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Formatting WA: <code class="font-bold">*teks tebal*</code>, <code class="font-bold italic">_teks miring_</code>. Tulis {qr} untuk posisi gambar QR.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                            <input type="checkbox" name="ticket_paid_wa_include_qr" value="1" {{ $wa->include_qr ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                            Sertakan QR tiket di pesan WhatsApp
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-8 py-3 bg-[#E19404] hover:bg-orange-600 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md transition">
                            Simpan Template WA
                        </button>
                    </div>
                </div>
            </form>
        @endif

        <!-- Form Email -->
        @php($email = $templates->firstWhere('key', 'ticket_paid_email'))
        @if($email)
            <form action="{{ route('admin.notifications.update') }}" method="POST" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                @csrf
                <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-900">{{ $email->label }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Dikirim ke email peserta (saat ini mode log).</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                        <input type="checkbox" name="ticket_paid_email_is_active" value="1" {{ $email->is_active ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                        Aktif
                    </label>
                </div>

                <div class="p-6 sm:p-8 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Subject Email</label>
                        <input type="text" name="ticket_paid_email_subject" value="{{ $email->subject }}"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Isi Email (HTML)</label>
                        <textarea name="ticket_paid_email_body" rows="12" required
                            class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] font-mono leading-relaxed">{{ $email->body }}</textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Mendukung tag HTML (contoh <code class="font-bold">&lt;p&gt;</code>, <code class="font-bold">&lt;strong&gt;</code>, <code class="font-bold">&lt;ul&gt;</code>). Tulis {qr} untuk posisi gambar QR.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                            <input type="checkbox" name="ticket_paid_email_include_qr" value="1" {{ $email->include_qr ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                            Sertakan QR tiket di email
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-8 py-3 bg-[#E19404] hover:bg-orange-600 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md transition">
                            Simpan Template Email
                        </button>
                    </div>
                </div>
            </form>
        @endif

    </main>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-gray-200 mt-auto py-6 w-full">
        <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-gray-400">
            <p>&copy; {{ date('Y') }} BACT Event System - Admin Portal.</p>
            <p class="font-semibold">Simposium Nasional Medis & Kesehatan 2027</p>
        </div>
    </footer>

</body>
</html>
