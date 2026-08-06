<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Hotel - Admin BACT 2027</title>
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

    <!-- 2. SUB-NAVBAR KONSISTEN -->
    <div class="bg-white border-b border-gray-200 shadow-sm sticky top-[73px] z-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-6 overflow-x-auto no-scrollbar py-1">
                <a href="/admin/dashboard" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Dashboard</a>
                <a href="/admin/tickets" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Kuota & Harga</a>
                <a href="/admin/participants" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Tiket Peserta</a>
                <a href="/admin/hotels" class="border-b-2 border-[#E19404] text-[#E19404] py-3.5 px-1 text-sm font-bold transition whitespace-nowrap">Tiket Hotel</a>
                <a href="/admin/submissions" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Karya Lomba</a>
                <a href="/admin/content" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Kelola Konten</a>
                <a href="/admin/broadcast" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">Broadcast WA</a>
                <a href="/admin/checkin" class="border-b-2 border-transparent text-gray-500 hover:text-[#E19404] hover:border-gray-300 py-3.5 px-1 text-sm font-semibold transition whitespace-nowrap">QR Check-In</a>
            </div>
        </div>
    </div>

    <!-- 3. KONTEN UTAMA - TABEL RESERVASI -->
    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow w-full space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900">Data Reservasi Hotel Peserta</h1>
                <p class="text-sm text-gray-500 mt-1">Pantau seluruh riwayat pemesanan kamar oleh peserta simposium dan verifikasi status pembayarannya.</p>
            </div>
            <a href="/admin/tickets" class="bg-[#FBE39D] hover:bg-[#E19404] text-[#E19404] hover:text-white text-xs font-extrabold px-5 py-3 rounded-xl shadow-sm transition whitespace-nowrap">
                &rarr; Atur Harga & Kuota Kamar
            </a>
        </div>

        <!-- Tabel Riwayat Reservasi Masuk -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-6">Kode & Tanggal Pesan</th>
                            <th class="py-3.5 px-6">Nama Pemesan</th>
                            <th class="py-3.5 px-6">Tipe Kamar</th>
                            <th class="py-3.5 px-6">Check-In / Out</th>
                            <th class="py-3.5 px-6">Total Tagihan</th>
                            <th class="py-3.5 px-6 text-center">Status</th>
                            <th class="py-3.5 px-6 text-center">Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($reservations as $res)
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="font-extrabold text-gray-900">{{ $res->booking_code }}</div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $res->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-bold text-gray-900">{{ $res->user->name ?? 'User Terhapus' }}</div>
                                    <div class="text-xs text-gray-500">{{ $res->user->email ?? '-' }}</div>
                                    @if($res->special_request)
                                        <div class="mt-1 text-[11px] bg-amber-50 text-amber-800 px-2 py-1 rounded border border-amber-100">
                                            Catatan: {{ $res->special_request }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 px-6 font-extrabold text-gray-800">
                                    {{ $res->hotelRoom->room_type ?? 'Kamar Dihapus' }}
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="text-xs font-bold text-gray-800">{{ \Carbon\Carbon::parse($res->check_in)->format('d M Y') }} - {{ \Carbon\Carbon::parse($res->check_out)->format('d M Y') }}</div>
                                    <div class="text-[11px] text-gray-400 font-semibold">{{ $res->total_nights }} Malam</div>
                                </td>
                                <td class="py-4 px-6 font-black text-[#E19404] whitespace-nowrap">
                                    Rp {{ number_format($res->total_price, 0, ',', '.') }}
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    @if($res->status === 'paid')
                                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">Lunas (Paid)</span>
                                    @elseif($res->status === 'cancelled')
                                        <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">Dibatalkan</span>
                                    @else
                                        <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full">Menunggu Bayar</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <form action="{{ route('admin.hotels.reservations.status', $res->id) }}" method="POST" class="inline-block m-0">
                                        @csrf
                                        <select name="status" onchange="this.form.submit()" class="text-xs font-bold border border-gray-300 rounded-xl px-3 py-1.5 bg-white outline-none focus:ring-2 focus:ring-[#FBE39D] cursor-pointer">
                                            <option value="pending" {{ $res->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="paid" {{ $res->status === 'paid' ? 'selected' : '' }}>Set Lunas</option>
                                            <option value="cancelled" {{ $res->status === 'cancelled' ? 'selected' : '' }}>Batalkan</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-6 text-center text-gray-400 text-xs">
                                    Belum ada reservasi hotel yang masuk dari peserta.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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