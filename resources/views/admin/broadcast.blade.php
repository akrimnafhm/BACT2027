<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast WA - Admin BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
                    <button type="submit"
                        class="border border-red-200 hover:bg-red-50 text-red-600 px-4 py-2 rounded-full text-xs font-bold transition shadow-sm">
                        Keluar Akun
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- 2. SUB-NAVBAR KONSISTEN -->
    <div class="bg-white border-b border-gray-200 shadow-sm sticky top-[73px] z-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-6 overflow-x-auto no-scrollbar py-1">
                <a href="/admin/dashboard" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Dashboard</a>
                <a href="/admin/tickets" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Kuota & Harga</a>
                <a href="/admin/participants" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Tiket Peserta</a>
                <a href="/admin/hotels" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Tiket Hotel</a>
                <a href="/admin/submissions" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Karya Lomba</a>
                <a href="/admin/content" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Kelola Konten</a>
                <a href="/admin/broadcast" class="border-b-2 border-[#E19404] text-[#E19404] py-3.5 px-1 text-sm font-bold transition whitespace-nowrap">Broadcast WA</a>
                <a href="/admin/notifications" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Template Notifikasi</a>
                <a href="/admin/checkin" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">QR Check-In</a>
            </div>
        </div>
    </div>

    <!-- 3. KONTEN UTAMA -->
    <main class="max-w-4xl mx-auto px-6 py-10 flex-grow w-full space-y-6" x-data="{ targetType: 'manual' }">

        <!-- Notifikasi Sukses / Error -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm">
                <p class="font-bold mb-1">Gagal mengirim broadcast:</p>
                @foreach ($errors->all() as $error)
                    <p class="text-xs">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Header Section -->
        <div>
            <h1 class="text-2xl font-black text-gray-900">Broadcast Pesan WhatsApp</h1>
            <p class="text-sm text-gray-500 mt-1">Kirim pengumuman penting atau pembaruan acara ke peserta BACT 2027 via Fonnte Gateway.</p>
        </div>

        <!-- Form Broadcast Card -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden p-6 sm:p-8">
            <form action="{{ route('admin.broadcast.send') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Pilihan Target -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">1. Pilih Target Penerima</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        
                        <!-- Opsi Input Manual -->
                        <label class="border rounded-xl p-4 flex items-start gap-3 cursor-pointer transition"
                            :class="targetType === 'manual' ? 'border-[#E19404] bg-[#FFF8E7]/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" name="target_type" value="manual" x-model="targetType" class="mt-0.5 text-[#E19404] focus:ring-[#FBE39D]">
                            <div>
                                <span class="block text-sm font-extrabold text-gray-900">Input Manual / Testing</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Masukkan beberapa nomor WA secara manual.</span>
                            </div>
                        </label>

                        <!-- Opsi Semua Peserta -->
                        <label class="border rounded-xl p-4 flex items-start gap-3 cursor-pointer transition"
                            :class="targetType === 'all' ? 'border-[#E19404] bg-[#FFF8E7]/50' : 'border-gray-200 hover:bg-gray-50'">
                            <input type="radio" name="target_type" value="all" x-model="targetType" class="mt-0.5 text-[#E19404] focus:ring-[#FBE39D]">
                            <div>
                                <span class="block text-sm font-extrabold text-gray-900">Semua Peserta Terdaftar</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Kirim ke total <strong class="text-[#E19404]">{{ $totalParticipants }}</strong> peserta di database.</span>
                            </div>
                        </label>

                    </div>
                </div>

                <!-- Area Input Manual (Mux Show) -->
                <div x-show="targetType === 'manual'" x-transition>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Daftar Nomor WhatsApp (Pisahkan dengan Koma atau Enter)
                    </label>
                    <textarea name="manual_numbers" rows="3" placeholder="Contoh:&#10;081234567890&#10;628198765432"
                        class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] font-mono"></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">Bisa menggunakan awalan 08xxx atau 628xxx.</p>
                </div>

                <!-- Input Jeda Waktu (Delay) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        2. Jeda Pengiriman Antar Pesan (Delay dalam Detik)
                    </label>
                    <input type="number" name="delay" min="2" max="30" value="3" required
                        class="w-32 px-4 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] font-bold text-center">
                    <p class="text-[11px] text-gray-500 mt-1">
                        <strong class="text-gray-700">Penting:</strong> Minimal jeda <strong>2-5 detik</strong> agar nomor WhatsApp tidak diblokir karena dianggap spam oleh WhatsApp.
                    </p>
                </div>

                <!-- Isi Pesan Broadcast -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        3. Isi Pesan Broadcast
                    </label>
                    <textarea name="message" rows="6" required
                        placeholder="Tulis pesan pengumuman di sini...&#10;&#10;Contoh:&#10;Halo *{nama}*,&#10;Mengingatkan bahwa simposium BACT 2027 akan dimulai besok pagi pukul 07.00 WIB.&#10;&#10;Salam,&#10;Panitia BACT 2027"
                        class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]"></textarea>
                    
                    <!-- Keterangan Placeholder Anti-Spam -->
                    <div class="mt-2.5 p-3 bg-[#FFF8E7] border border-[#E19404]/30 rounded-xl text-xs text-gray-700 space-y-1">
                        <p class="font-bold text-[#E19404]">Fitur Anti-Spam & Personalisasi:</p>
                        <p>Ketik <code class="bg-white px-1.5 py-0.5 rounded border font-bold text-gray-900">{nama}</code> di dalam teks pesan untuk memanggil nama asli peserta secara otomatis agar pesan tidak terdeteksi spam oleh WhatsApp.</p>
                        <p class="text-[11px] text-gray-500 pt-0.5">Formatting WA: <code class="bg-white px-1 py-0.5 rounded font-bold">*teks tebal*</code>, <code class="bg-white px-1 py-0.5 rounded italic">_teks miring_</code>.</p>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit"
                        class="px-8 py-3.5 bg-[#E19404] hover:bg-orange-600 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md hover:shadow-lg transition cursor-pointer flex items-center gap-2">
                        <span>Kirim Broadcast Sekarang</span>
                    </button>
                </div>

            </form>
        </div>

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