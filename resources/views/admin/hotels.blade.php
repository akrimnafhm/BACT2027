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

    <!-- 1-2. NAVBAR UTAMA + SUB-NAVBAR -->
    @include('partials.admin-navbar', ['active' => 'hotels'])

    <!-- 3. KONTEN UTAMA DATA RESERVASI -->
    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow w-full space-y-6">

        <!-- Notifikasi -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium shadow-sm">
                <p class="font-bold mb-1">Gagal memproses aksi Anda:</p>
                @foreach ($errors->all() as $error)
                    <p class="text-xs">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Header & Action -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900">Data Reservasi Hotel Peserta</h1>
                <p class="text-sm text-gray-500 mt-0.5">Pantau status pemesanan kamar, konfirmasi pembayaran, serta unduh rekapitulasi data.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Tombol Unduh Excel -->
                <a href="{{ route('admin.hotels.export', request()->query()) }}" class="bg-green-600 hover:bg-green-700 text-white text-xs font-extrabold px-4 py-2 rounded-xl shadow-sm transition transform active:scale-95 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                    Unduh Excel (.CSV)
                </a>

                <!-- Total Badge -->
                <span class="text-xs font-bold bg-white border border-gray-200 px-3 py-2 rounded-xl text-gray-600 shadow-sm">
                    Total: <strong class="text-[#E19404]">{{ $reservations->total() }}</strong> Reservasi
                </span>
            </div>
        </div>

        <!-- 4. BOX FILTER 1 BARIS (COMPACT) -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
            <form action="{{ url('/admin/hotels') }}" method="GET" class="flex flex-col md:flex-row items-center gap-3">
                
                <!-- Search -->
                <div class="w-full md:w-64 relative flex-shrink-0">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kode booking, nama..." 
                        class="w-full pl-9 pr-3 py-2 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filter Tipe Kamar -->
                <select name="room_type" class="w-full md:w-auto px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none cursor-pointer">
                    <option value="">Semua Tipe Kamar</option>
                    @if(isset($roomTypes))
                        @foreach($roomTypes as $type)
                            <option value="{{ $type }}" {{ (isset($roomType) && $roomType === $type) ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    @endif
                </select>

                <!-- Filter Status -->
                <select name="status" class="w-full md:w-auto px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="paid" {{ (isset($status) && $status === 'paid') ? 'selected' : '' }}>Lunas (Paid)</option>
                    <option value="pending" {{ (isset($status) && $status === 'pending') ? 'selected' : '' }}>Menunggu Bayar</option>
                    <option value="cancelled" {{ (isset($status) && $status === 'cancelled') ? 'selected' : '' }}>Dibatalkan</option>
                </select>

                <!-- Tombol Action 1 Baris -->
                <div class="flex items-center gap-2 w-full md:w-auto md:ml-auto justify-end">
                    @if(!empty($search) || !empty($roomType) || !empty($status))
                        <a href="{{ url('/admin/hotels') }}" class="px-3.5 py-2 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 hover:bg-red-50 rounded-xl transition whitespace-nowrap">
                            Reset
                        </a>
                    @endif
                    <button type="submit" class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-5 py-2 rounded-xl shadow-sm transition whitespace-nowrap">
                        Terapkan
                    </button>
                </div>

            </form>
        </div>

        <!-- 5. TABEL DATA RESERVASI -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Kode & Tanggal</th>
                            <th class="py-3.5 px-5">Nama Pemesan</th>
                            <th class="py-3.5 px-5">Tipe Kamar</th>
                            <th class="py-3.5 px-5">Check-In / Out</th>
                            <th class="py-3.5 px-5">Tagihan</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($reservations as $res)
                            <tr class="hover:bg-gray-50/80 transition">
                                
                                <td class="py-3.5 px-5 whitespace-nowrap">
                                    <div class="font-extrabold text-gray-900">{{ $res->booking_code }}</div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $res->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-gray-900">{{ $res->user->name ?? 'User Terhapus' }}</div>
                                    <div class="text-xs text-gray-500">{{ $res->user->email ?? '-' }}</div>
                                    @if($res->special_request)
                                        <div class="mt-1 text-[11px] bg-amber-50 text-amber-800 px-2 py-1 rounded border border-amber-100 max-w-xs truncate" title="{{ $res->special_request }}">
                                            Catatan: {{ $res->special_request }}
                                        </div>
                                    @endif
                                </td>
                                
                                <td class="py-3.5 px-5 font-bold text-gray-800">
                                    {{ $res->hotelRoom->room_type ?? 'Kamar Dihapus' }}
                                </td>
                                
                                <td class="py-3.5 px-5 whitespace-nowrap">
                                    <div class="text-xs font-bold text-gray-800">{{ \Carbon\Carbon::parse($res->check_in)->format('d M Y') }} - {{ \Carbon\Carbon::parse($res->check_out)->format('d M Y') }}</div>
                                    <div class="text-[11px] text-[#E19404] font-bold">{{ $res->total_nights }} Malam</div>
                                </td>
                                
                                <td class="py-3.5 px-5 font-black text-gray-900 whitespace-nowrap">
                                    Rp {{ number_format($res->total_price, 0, ',', '.') }}
                                </td>
                                
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    @if($res->status === 'paid')
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">LUNAS</span>
                                    @elseif($res->status === 'cancelled')
                                        <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">BATAL</span>
                                    @else
                                        <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 text-xs font-bold rounded-full">PENDING</span>
                                    @endif
                                </td>
                                
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tombol View -->
                                        <button type="button" onclick="openViewHotelModal({{ json_encode($res) }})" 
                                            class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold rounded-lg transition">
                                            View
                                        </button>
                                        <!-- Tombol Edit -->
                                        <button type="button" onclick="openEditHotelModal({{ json_encode($res) }})" 
                                            class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition">
                                            Edit
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-6 text-center text-gray-500">
                                    Belum ada data reservasi hotel yang ditemukan sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION BAR -->
            @if(isset($reservations) && $reservations->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $reservations->links() }}
                </div>
            @endif
        </div>

    </main>

    <!-- =========================================================
         6. MODAL VIEW DETAIL RESERVASI HOTEL
         ========================================================= -->
    <div id="viewHotelModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Detail Reservasi Kamar</h3>
                <button type="button" onclick="closeViewHotelModal()" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>
            <div class="p-6 space-y-3 text-sm" id="viewHotelModalContent">
                <!-- Diisi otomatis melalui Javascript -->
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 text-right">
                <button type="button" onclick="closeViewHotelModal()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl">Tutup</button>
            </div>
        </div>
    </div>

    <!-- =========================================================
         7. MODAL EDIT STATUS RESERVASI
         ========================================================= -->
    <div id="editHotelModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Ubah Status Reservasi</h3>
                <button type="button" onclick="closeEditHotelModal()" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="editHotelForm" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Status Pembayaran</label>
                    <select name="status" id="edit_hotel_status" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl cursor-pointer">
                        <option value="pending">Tertunda (Pending)</option>
                        <option value="paid">Lunas (Paid)</option>
                        <option value="cancelled">Dibatalkan (Cancelled)</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditHotelModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    @include('partials.admin-footer')

    <!-- SCRIPT KONTROL MODAL -->
    <script>
        function openViewHotelModal(item) {
            const content = document.getElementById('viewHotelModalContent');
            const userName = item.user ? item.user.name : 'User Terhapus';
            const userEmail = item.user ? item.user.email : '-';
            const roomType = item.hotel_room ? item.hotel_room.room_type : 'Kamar Dihapus';
            const notes = item.special_request ? item.special_request : 'Tidak ada catatan khusus';
            
            // Format format tanggal untuk tampilan
            const checkInDate = new Date(item.check_in).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const checkOutDate = new Date(item.check_out).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

            content.innerHTML = `
                <div class="border-b border-gray-100 pb-3">
                    <p class="text-xs text-gray-400 uppercase font-bold">Kode Reservasi</p>
                    <p class="text-base font-extrabold text-gray-900">${item.booking_code}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Nama Pemesan</p>
                        <p class="font-semibold text-gray-800">${userName}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Email</p>
                        <p class="font-semibold text-gray-800">${userEmail}</p>
                    </div>
                </div>
                <div class="border-b border-gray-100 pb-3">
                    <p class="text-xs text-gray-400 uppercase font-bold">Tipe Kamar</p>
                    <p class="font-bold text-gray-900">${roomType}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Check-In</p>
                        <p class="font-semibold text-gray-800">${checkInDate}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Check-Out</p>
                        <p class="font-semibold text-gray-800">${checkOutDate}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Total Malam</p>
                        <p class="text-base font-black text-gray-900">${item.total_nights} Malam</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Total Tagihan</p>
                        <p class="text-base font-black text-[#E19404]">Rp${new Intl.NumberFormat('id-ID').format(item.total_price)}</p>
                    </div>
                </div>
                <div class="pb-1">
                    <p class="text-xs text-gray-400 uppercase font-bold">Catatan Khusus</p>
                    <p class="font-semibold text-gray-700 italic">${notes}</p>
                </div>
            `;
            document.getElementById('viewHotelModal').classList.remove('hidden');
        }

        function closeViewHotelModal() {
            document.getElementById('viewHotelModal').classList.add('hidden');
        }

        function openEditHotelModal(item) {
            document.getElementById('edit_hotel_status').value = item.status;
            // Gunakan route update status yang sudah ada di HotelController
            document.getElementById('editHotelForm').action = `/admin/hotels/reservations/${item.id}/status`;
            document.getElementById('editHotelModal').classList.remove('hidden');
        }

        function closeEditHotelModal() {
            document.getElementById('editHotelModal').classList.add('hidden');
        }
    </script>

</body>
</html>