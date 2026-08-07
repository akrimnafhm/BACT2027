<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Check-In - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        #reader video { border-radius: 1rem; }
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
                <a href="/admin/checkin" class="border-b-2 border-[#E19404] text-[#E19404] py-3.5 px-1 text-sm font-bold transition whitespace-nowrap">QR Check-In</a>
            </div>
        </div>
    </div>

    <!-- 3. KONTEN UTAMA -->
    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow w-full space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-black text-gray-900">QR Check-In Peserta</h1>
            <p class="text-sm text-gray-500 mt-0.5">Pindai QR tiket peserta dengan kamera atau masukkan kode QR secara manual, lalu konfirmasi kehadiran.</p>
        </div>

        <!-- Notifikasi Global -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- TAB BAR -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

            <div class="flex border-b border-gray-200">
                <button type="button"
                        onclick="switchTab('scan')"
                        id="tab_btn_scan"
                        class="tab-btn cursor-pointer flex-1 sm:flex-none px-6 py-4 text-sm font-bold transition border-b-[3px] {{ $activeTab === 'scan' ? 'border-[#E19404] text-[#E19404] bg-[#FFFDF5]' : 'border-transparent text-gray-500 hover:text-[#E19404]' }}">
                    Scan / Input Check-in
                </button>
                <button type="button"
                        onclick="switchTab('checked')"
                        id="tab_btn_checked"
                        class="tab-btn cursor-pointer flex-1 sm:flex-none px-6 py-4 text-sm font-bold transition border-b-[3px] {{ $activeTab === 'checked' ? 'border-[#E19404] text-[#E19404] bg-[#FFFDF5]' : 'border-transparent text-gray-500 hover:text-[#E19404]' }}">
                    Peserta Sudah Check-in
                    <span class="ml-1.5 inline-block bg-green-100 text-green-700 text-[11px] font-extrabold px-2 py-0.5 rounded-full">{{ $checkedInParticipants->total() }}</span>
                </button>
            </div>

            <div class="p-6 sm:p-8">

                <!-- ==========================================
                     TAB 1: SCAN / INPUT CHECK-IN
                     ========================================== -->
                <div id="tab_scan" class="tab-pane space-y-6" @if($activeTab !== 'scan') style="display:none;" @endif>

                    @if(isset($error))
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>{{ $error }}</span>
                        </div>
                    @endif

                    <!-- HASIL SCAN / DATA PESERTA -->
                    @if(isset($booking))
                        @php($alreadyChecked = !empty($booking->checked_in_at))
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

                            <div class="px-8 py-6 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 {{ $alreadyChecked ? 'bg-green-50 border-green-200' : 'bg-[#FBE39D] border-[#E19404]/20' }}">
                                <div>
                                    <h2 class="text-xl font-extrabold text-gray-900">Data Peserta</h2>
                                    <p class="text-gray-600 text-sm mt-0.5">{{ $booking->checkin_token }}</p>
                                </div>
                                @if($alreadyChecked)
                                    <span class="inline-block bg-green-500 text-white px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide">Sudah Check-In</span>
                                @else
                                    <span class="inline-block bg-white border border-[#E19404]/30 text-[#E19404] px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide">Belum Check-In</span>
                                @endif
                            </div>

                            <div class="p-8 space-y-6">
                                <div class="bg-gray-50 rounded-xl border border-gray-200 divide-y divide-gray-200/70 text-sm">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                                        <span class="text-gray-500 font-medium">Nama Lengkap (KTP)</span>
                                        <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->full_name }}</span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                                        <span class="text-gray-500 font-medium">Nama & Gelar (Sertifikat)</span>
                                        <span class="font-bold text-[#E19404] mt-0.5 sm:mt-0">{{ $booking->name_with_title }}</span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                                        <span class="text-gray-500 font-medium">E-mail (Gmail)</span>
                                        <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->gmail_account }}</span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                                        <span class="text-gray-500 font-medium">Nomor WhatsApp</span>
                                        <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->whatsapp_number }}</span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                                        <span class="text-gray-500 font-medium">NIK</span>
                                        <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->nik }}</span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start py-3 px-5">
                                        <span class="text-gray-500 font-medium sm:pt-0.5">Instansi</span>
                                        <span class="font-bold text-gray-900 mt-0.5 sm:mt-0 sm:text-right">
                                            {{ $booking->institution_name }}<br>
                                            <span class="text-xs font-semibold text-gray-500">{{ $booking->institution_city }}, {{ $booking->institution_province }}</span>
                                        </span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                                        <span class="text-gray-500 font-medium">Tiket</span>
                                        <span class="font-bold text-gray-900 mt-0.5 sm:mt-0">{{ $booking->ticket_name }} - {{ $booking->ticket_category }}</span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                                        <span class="text-gray-500 font-medium">Status Pembayaran</span>
                                        <span class="font-bold text-green-600 uppercase mt-0.5 sm:mt-0">{{ $booking->status }}</span>
                                    </div>
                                    @if($alreadyChecked)
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-3 px-5">
                                            <span class="text-gray-500 font-medium">Waktu Check-In</span>
                                            <span class="font-bold text-green-700 mt-0.5 sm:mt-0">{{ $booking->checked_in_at->format('d M Y H:i:s') }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    @if($alreadyChecked)
                                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3.5 rounded-xl text-sm font-semibold text-center">
                                            Peserta ini sudah check-in sebelumnya pada {{ $booking->checked_in_at->format('d M Y H:i:s') }}.
                                        </div>
                                    @else
                                        <form action="{{ route('admin.checkin.confirm', $booking->id) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-extrabold py-4 px-6 rounded-xl shadow-md transition transform active:scale-95 text-center">
                                                Konfirmasi Check-in &rarr;
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- SCANNER KAMERA + INPUT MANUAL -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                        <!-- Kolom Kamera -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider">Scan dengan Kamera</h3>
                                <span class="text-[11px] font-bold text-gray-400 bg-gray-100 px-3 py-1 rounded-full">Otomatis</span>
                            </div>

                            <div id="reader" class="w-full bg-gray-900 rounded-xl overflow-hidden"></div>

                            <div class="flex gap-3">
                                <button type="button" id="start_btn" onclick="startScanner()" class="flex-1 cursor-pointer bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold py-2.5 rounded-xl transition shadow-sm">
                                    Mulai Kamera
                                </button>
                                <button type="button" id="stop_btn" onclick="stopScanner()" disabled class="flex-1 bg-gray-200 text-gray-500 text-xs font-extrabold py-2.5 rounded-xl transition cursor-not-allowed">
                                    Hentikan
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-400">Izinkan akses kamera untuk memindai QR peserta secara otomatis.</p>
                        </div>

                        <!-- Kolom Input Manual -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider">Input Manual</h3>
                                <span class="text-[11px] font-bold text-gray-400 bg-gray-100 px-3 py-1 rounded-full">Alternatif</span>
                            </div>

                            <form id="scan_form" action="{{ route('admin.checkin.scan') }}" method="POST" class="space-y-4 m-0">
                                @csrf
                                <label for="scan_token" class="block text-xs font-bold text-gray-700 uppercase mb-1.5">
                                    Kode QR / Token Peserta
                                </label>
                                <input type="text"
                                       name="token"
                                       id="scan_token"
                                       required
                                       value="{{ old('token') }}"
                                       placeholder="Tempel kode dari QR di sini..."
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm font-bold text-gray-800 outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] transition bg-white">
                                <button type="submit" class="w-full cursor-pointer bg-[#E19404] hover:bg-orange-600 text-white font-extrabold py-3 px-6 rounded-xl transition shadow-md">
                                    Cari Peserta
                                </button>
                            </form>

                            <p class="text-[11px] text-gray-400 leading-relaxed">
                                Jika kamera tidak berfungsi, silakan ketik atau tempel kode QR (token) peserta ke kolom di atas.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- ==========================================
                     TAB 2: PESERTA SUDAH CHECK-IN
                     ========================================== -->
                <div id="tab_checked" class="tab-pane space-y-6" @if($activeTab !== 'checked') style="display:none;" @endif>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900">Daftar Peserta Sudah Check-in</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Total <strong class="text-green-700">{{ $checkedInParticipants->total() }}</strong> peserta telah melakukan check-in.</p>
                        </div>
                    </div>

                    @if($checkedInParticipants->isEmpty())
                        <div class="bg-white rounded-2xl border border-dashed border-gray-300 text-center py-16 text-gray-500 space-y-2">
                            <p class="text-base font-bold text-gray-700">Belum Ada Peserta yang Check-in</p>
                            <p class="text-xs text-gray-400">Peserta akan muncul di tab ini setelah admin mengonfirmasi check-in.</p>
                        </div>
                    @else
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-500">
                                            <th class="px-5 py-3.5 font-bold">Nama</th>
                                            <th class="px-5 py-3.5 font-bold">Nama & Gelar</th>
                                            <th class="px-5 py-3.5 font-bold">Email</th>
                                            <th class="px-5 py-3.5 font-bold">WhatsApp</th>
                                            <th class="px-5 py-3.5 font-bold">NIK</th>
                                            <th class="px-5 py-3.5 font-bold">Instansi</th>
                                            <th class="px-5 py-3.5 font-bold">Tiket</th>
                                            <th class="px-5 py-3.5 font-bold">Waktu Check-in</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($checkedInParticipants as $p)
                                            <tr class="hover:bg-[#FFFDF5] transition">
                                                <td class="px-5 py-3.5 font-bold text-gray-900 whitespace-nowrap">{{ $p->full_name }}</td>
                                                <td class="px-5 py-3.5 text-gray-700 whitespace-nowrap">{{ $p->name_with_title }}</td>
                                                <td class="px-5 py-3.5 text-gray-700 whitespace-nowrap">{{ $p->gmail_account }}</td>
                                                <td class="px-5 py-3.5 text-gray-700 whitespace-nowrap">{{ $p->whatsapp_number }}</td>
                                                <td class="px-5 py-3.5 text-gray-700 whitespace-nowrap">{{ $p->nik }}</td>
                                                <td class="px-5 py-3.5 text-gray-700">
                                                    {{ $p->institution_name }}<br>
                                                    <span class="text-[11px] text-gray-400">{{ $p->institution_city }}, {{ $p->institution_province }}</span>
                                                </td>
                                                <td class="px-5 py-3.5 text-gray-700 whitespace-nowrap">{{ $p->ticket_name }} - {{ $p->ticket_category }}</td>
                                                <td class="px-5 py-3.5 whitespace-nowrap">
                                                    <span class="inline-block bg-green-100 text-green-700 text-[11px] font-extrabold px-2.5 py-1 rounded-full">
                                                        {{ $p->checked_in_at->format('d M Y H:i') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-center">
                            {{ $checkedInParticipants->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>

    </main>

    <!-- Library Scanner QR -->
    <script src="https://unpkg.com/html5-qrcode" defer></script>
    <script>
        let html5QrCode = null;

        function switchTab(name) {
            document.querySelectorAll('.tab-pane').forEach(function (el) {
                el.style.display = 'none';
            });
            document.getElementById('tab_' + name).style.display = '';

            document.querySelectorAll('.tab-btn').forEach(function (btn) {
                btn.classList.remove('border-[#E19404]', 'text-[#E19404]', 'bg-[#FFFDF5]');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            const activeBtn = document.getElementById('tab_btn_' + name);
            activeBtn.classList.remove('border-transparent', 'text-gray-500');
            activeBtn.classList.add('border-[#E19404]', 'text-[#E19404]', 'bg-[#FFFDF5]');

            // Hentikan kamera jika pindah dari tab scan
            if (name !== 'scan') {
                stopScanner();
            }
        }

        async function startScanner() {
            if (typeof Html5Qrcode === 'undefined') return;

            try {
                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode("reader");
                }

                await html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    function (decodedText) {
                        document.getElementById('scan_token').value = decodedText;
                        document.getElementById('scan_form').submit();
                    },
                    function () { /* scan progress callback, abaikan */ }
                );

                document.getElementById('start_btn').disabled = true;
                document.getElementById('stop_btn').disabled = false;
            } catch (err) {
                alert('Tidak dapat mengakses kamera. Gunakan input manual atau periksa izin kamera.\n\n' + err);
            }
        }

        async function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                await html5QrCode.stop();
            }
            const startBtn = document.getElementById('start_btn');
            const stopBtn = document.getElementById('stop_btn');
            if (startBtn) startBtn.disabled = false;
            if (stopBtn) stopBtn.disabled = true;
        }
    </script>

</body>
</html>
