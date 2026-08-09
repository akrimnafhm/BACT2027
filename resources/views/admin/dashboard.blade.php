<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - BACT 2026</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Sembunyikan scrollbar pada menu sub-navbar */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans">

    <!-- 1-2. NAVBAR UTAMA + SUB-NAVBAR -->
    @include('partials.admin-navbar', ['active' => 'dashboard'])

    <!-- 3. KONTEN UTAMA DASHBOARD -->
    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow w-full space-y-10">

        <!-- Header Seksi -->
        <div>
            <h1 class="text-2xl font-black text-gray-900">Ringkasan Sistem Simposium BACT 2026</h1>
            <p class="text-sm text-gray-500 mt-1">Pantau perkembangan pendaftaran peserta dan aliran pendapatan tiket secara real-time.</p>
        </div>

        <!-- A. 3 CARD RINGKASAN UTAMA -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Card 1: Total Uang Masuk -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Uang Masuk</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-gray-900 mt-1">
                            Rp{{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="p-3 bg-green-50 rounded-xl text-green-600 border border-green-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs font-medium text-green-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Dari pesanan berstatus LUNAS</span>
                </p>
            </div>

            <!-- Card 2: Total Peserta Lunas -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Peserta Lunas (Paid)</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-gray-900 mt-1">
                            {{ number_format($totalPaidParticipants ?? 0, 0, ',', '.') }} <span class="text-sm font-semibold text-gray-500">Orang</span>
                        </h3>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl text-blue-600 border border-blue-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs font-medium text-gray-500">
                    Tiket siap digunakan check-in
                </p>
            </div>

            <!-- Card 3: Tertunda (Pending) -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pemesanan Tertunda</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-gray-900 mt-1">
                            {{ number_format($totalPendingBookings ?? 0, 0, ',', '.') }} <span class="text-sm font-semibold text-gray-500">Tiket</span>
                        </h3>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-xl text-orange-600 border border-orange-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs font-medium text-orange-600">
                    Menunggu konfirmasi pembayaran DOKU
                </p>
            </div>

        </div>

        <!-- B. REKAPITULASI 6 JENIS TIKET UTAMA -->
        <div class="space-y-6">
            <div class="flex justify-between items-center border-b border-gray-200 pb-4">
                <div>
                    <h2 class="text-lg font-black text-gray-900">Rekapitulasi per Jenis Tiket</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Total akumulasi peserta lunas (paid) dan uang masuk dari seluruh gelombang penjualan</p>
                </div>
                <a href="/admin/tickets" class="text-xs font-bold text-[#E19404] hover:underline">
                    Kelola Kuota &rarr;
                </a>
            </div>

            <!-- Grid 6 Card Tiket -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($categories as $categoryName => $data)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex flex-col justify-between hover:border-gray-300 transition">
                        
                        <!-- Header Nama Tiket -->
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                            <h3 class="font-black text-gray-900 text-base tracking-wide uppercase">
                                {{ $categoryName }}
                            </h3>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#E19404]"></span>
                        </div>

                        <!-- Isi Data: Uang Masuk & Peserta Lunas -->
                        <div class="space-y-4">
                            <!-- Uang Masuk -->
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Uang Masuk</p>
                                <p class="text-2xl font-black text-[#E19404] mt-0.5">
                                    Rp{{ number_format($data['revenue'], 0, ',', '.') }}
                                </p>
                            </div>

                            <!-- Jumlah Peserta -->
                            <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100 flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-600 uppercase">Jumlah Peserta</span>
                                <span class="text-base font-black text-gray-900">
                                    {{ number_format($data['paid'], 0, ',', '.') }} <span class="text-xs font-semibold text-gray-500">Orang</span>
                                </span>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>

    </main>

    <!-- 4. FOOTER ADMIN -->
    @include('partials.admin-footer')

</body>
</html>