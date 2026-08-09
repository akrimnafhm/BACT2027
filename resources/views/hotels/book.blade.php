<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Kamar: {{ $hotel->room_type }} - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased font-sans bg-[#F4F5F7] text-gray-800 min-h-screen flex flex-col">

    <!-- 1. NAVBAR -->
    @include('partials.navbar', [
        'navbarShowGuestLogin' => false,
        'navbarShowAdmin' => false,
        'navbarShowGaleri' => false,
    ])

    <!-- 2. KONTEN UTAMA -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 pt-32 pb-16 flex-grow w-full space-y-8">
        
        <!-- Tombol Kembali -->
        <div>
            <a href="{{ route('hotels.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-[#E19404] transition">
                &larr; Kembali ke Daftar Kamar
            </a>
        </div>

        <!-- Notifikasi Error Validasi Laravel -->
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl text-sm shadow-sm">
                <div class="font-bold mb-1">Pemesanan Gagal Diproses:</div>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl text-sm font-medium shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Grid 2 Kolom: Detail Kamar (Kiri) & Form Reservasi (Kanan) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- ========================================== -->
            <!-- KOLOM KIRI (5 SPAN): DETAIL AKOMODASI -->
            <!-- ========================================== -->
            <div class="lg:col-span-5 bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden sticky top-28">
                <!-- Foto Kamar -->
                <div class="h-64 sm:h-72 w-full bg-gray-100 overflow-hidden relative">
                    @if($hotel->image)
                        <img src="{{ asset('storage/' . $hotel->image) }}" alt="{{ $hotel->room_type }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400 font-bold text-sm">Foto Tidak Tersedia</div>
                    @endif
                    <div class="absolute top-4 left-4 bg-white/95 backdrop-blur-md px-3.5 py-1.5 rounded-full text-xs font-extrabold text-gray-800 shadow-sm">
                        Akomodasi Resmi
                    </div>
                </div>

                <!-- Info Kamar -->
                <div class="p-6 space-y-5">
                    <div>
                        <span class="text-[11px] font-bold text-[#E19404] uppercase tracking-widest">Detail Akomodasi</span>
                        <h2 class="text-2xl font-black text-gray-900 mt-1">{{ $hotel->room_type }}</h2>
                    </div>

                    <div class="flex items-baseline justify-between py-3 border-y border-gray-100">
                        <span class="text-xs font-bold text-gray-400 uppercase">Harga Dasar</span>
                        <div class="text-right">
                            <span class="text-xl font-black text-[#E19404]">Rp {{ number_format($hotel->price_per_night, 0, ',', '.') }}</span>
                            <span class="text-xs text-gray-400 font-bold"> / malam</span>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <p class="text-sm text-gray-600 leading-relaxed">
                        {{ $hotel->description ?: 'Kamar bernuansa nyaman dan elegan untuk mendukung kelancaran aktivitasmu selama simposium berlangsung.' }}
                    </p>

                    <!-- Fasilitas -->
                    @if($hotel->facilities)
                        <div class="space-y-2 pt-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Fasilitas Termasuk:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(explode(',', $hotel->facilities) as $fac)
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">
                                        {{ trim($fac) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ========================================== -->
            <!-- KOLOM KANAN (7 SPAN): FORM RESERVASI -->
            <!-- ========================================== -->
            <div class="lg:col-span-7 bg-white rounded-3xl border border-gray-200 shadow-sm p-6 sm:p-8 space-y-8">
                
                <div>
                    <h1 class="text-2xl font-black text-gray-900">Form Reservasi Kamar</h1>
                    <p class="text-xs text-gray-500 mt-1">
                        Pemesanan kamar hotel terbuka untuk umum selama periode acara simposium berlangsung.
                    </p>
                </div>

                <!-- INFO CARD: DATA DIRI READ-ONLY (DARI PROFIL USER) -->
                <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                        <span class="text-xs font-extrabold text-gray-500 uppercase tracking-wider">Data Pemesan (Sesuai Profil)</span>
                        <a href="{{ route('profile.edit') }}" class="text-xs font-bold text-[#E19404] hover:underline">Ubah Profil</a>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="block text-xs font-semibold text-gray-400">Nama Lengkap</span>
                            <span class="block font-bold text-gray-900 mt-0.5">{{ Auth::user()->name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400">NIK (KTP)</span>
                            <span class="block font-bold text-gray-900 mt-0.5">{{ Auth::user()->nik }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400">E-mail (Gmail)</span>
                            <span class="block font-bold text-gray-900 mt-0.5">{{ Auth::user()->email }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400">No. WhatsApp</span>
                            <span class="block font-bold text-gray-900 mt-0.5">{{ Auth::user()->phone_number }}</span>
                        </div>
                    </div>
                </div>

                <!-- FORM PILIH TANGGAL CHECK-IN & CHECK-OUT -->
                <form action="{{ route('hotels.store', $hotel->id) }}" method="POST" class="space-y-8 m-0">
                    @csrf
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider">Tanggal Menginap</h3>
                            <span class="text-xs font-bold text-[#E19404] bg-[#FBE39D]/50 px-3 py-1 rounded-full">Periode: 18 - 21 Jan 2027</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Check-In -->
                            <div>
                                <label for="check_in" class="block text-xs font-bold text-gray-700 uppercase mb-1.5">
                                    Tanggal Check-in <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="check_in" 
                                       id="check_in" 
                                       required
                                       min="2027-01-18" 
                                       max="2027-01-20"
                                       value="{{ old('check_in') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm font-bold text-gray-800 outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] transition bg-white cursor-pointer">
                                <span class="block text-[11px] text-gray-400 mt-1">Min: 18 Jan 2027 | Max: 20 Jan 2027</span>
                            </div>

                            <!-- Check-Out -->
                            <div>
                                <label for="check_out" class="block text-xs font-bold text-gray-700 uppercase mb-1.5">
                                    Tanggal Check-out <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="check_out" 
                                       id="check_out" 
                                       required
                                       min="2027-01-19" 
                                       max="2027-01-21"
                                       value="{{ old('check_out') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm font-bold text-gray-800 outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] transition bg-white cursor-pointer">
                                <span class="block text-[11px] text-gray-400 mt-1">Min: 19 Jan 2027 | Max: 21 Jan 2027</span>
                            </div>
                        </div>
                    </div>

                    <!-- RINGKASAN BIAYA (REAL-TIME KALKULATOR) -->
                    <div class="bg-[#FFFCEF] border border-[#FBE39D] rounded-2xl p-6 space-y-4">
                        <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-wider block">Ringkasan Biaya</span>
                        
                        <div class="flex justify-between items-center text-sm font-bold text-gray-700">
                            <span>Durasi Menginap:</span>
                            <span id="nights_count" class="text-base text-gray-900 font-black">0 Malam</span>
                        </div>

                        <div class="flex justify-between items-center pt-3 border-t border-[#FBE39D]/60">
                            <span class="text-sm font-bold text-gray-700">Total Harga Reservasi:</span>
                            <span id="total_price" class="text-2xl font-black text-[#E19404]">Rp 0</span>
                        </div>
                    </div>

                    <!-- TOMBOL SUBMIT (DISABLED DEFAULT SEBELUM TANGGAL DIPILIH) -->
                    <div>
                        <button type="submit" 
                                id="submit_btn" 
                                disabled
                            
                                class="w-full py-4 px-6 rounded-2xl bg-gray-300 text-gray-500 font-extrabold text-base transition duration-300 cursor-not-allowed text-center">
                            Konfirmasi & Pesan Kamar &rarr;
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </main>

    <!-- 3. FOOTER -->
    @include('partials.footer')

    <!-- 4. SCRIPT KALKULATOR DURASI & BIAYA OTOMATIS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkInInput  = document.getElementById('check_in');
            const checkOutInput = document.getElementById('check_out');
            const nightsText    = document.getElementById('nights_count');
            const priceText     = document.getElementById('total_price');
            const submitBtn     = document.getElementById('submit_btn');
            
            // Ambil harga dari database hotel
            const pricePerNight = Number({{ $hotel->price_per_night }});

            function calculateCost() {
                const checkInVal  = checkInInput.value;
                const checkOutVal = checkOutInput.value;

                if (checkInVal && checkOutVal) {
                    const start = new Date(checkInVal);
                    const end   = new Date(checkOutVal);

                    // Hitung selisih dalam milidetik dan jadikan hari
                    const diffTime = end - start;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                    // Jika check-out > check-in (minimal 1 malam)
                    if (diffDays > 0) {
                        const totalPrice = diffDays * pricePerNight;

                        if (nightsText) nightsText.textContent = diffDays + ' Malam';
                        if (priceText)  priceText.textContent  = 'Rp ' + totalPrice.toLocaleString('id-ID');

                        // Aktifkan Tombol Submit
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                            submitBtn.classList.add('bg-[#E19404]', 'hover:bg-orange-600', 'text-white', 'cursor-pointer', 'shadow-md', 'hover:shadow-lg');
                        }
                        return;
                    }
                }

                // Default / Jika Tanggal Tidak Valid (Check-out lebih awal dari Check-in)
                if (nightsText) nightsText.textContent = '0 Malam';
                if (priceText)  priceText.textContent  = 'Rp 0';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                    submitBtn.classList.remove('bg-[#E19404]', 'hover:bg-orange-600', 'text-white', 'cursor-pointer', 'shadow-md', 'hover:shadow-lg');
                }
            }

            // Dengarkan perubahan input tanggal
            if (checkInInput && checkOutInput) {
                checkInInput.addEventListener('change', calculateCost);
                checkOutInput.addEventListener('change', calculateCost);
                checkInInput.addEventListener('input', calculateCost);
                checkOutInput.addEventListener('input', calculateCost);
                
                // Panggil sekali di awal untuk antisipasi browser autofill/old value
                calculateCost();
            }
        });
    </script>

</body>
</html>