<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tiket Peserta - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans">

        <!-- 1-2. NAVBAR UTAMA + SUB-NAVBAR -->
    @include('partials.admin-navbar', ['active' => 'participants'])


    <!-- 3. KONTEN UTAMA DATA PESERTA -->
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
                <h1 class="text-2xl font-black text-gray-900">Data Pemesanan Tiket Peserta</h1>
                <p class="text-sm text-gray-500 mt-0.5">Pantau status pendaftaran, konfirmasi pembayaran, serta unduh rekapitulasi data.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Tombol Unduh Excel -->
                <a href="{{ route('admin.participants.export', request()->query()) }}" class="bg-green-600 hover:bg-green-700 text-white text-xs font-extrabold px-4 py-2 rounded-xl shadow-sm transition transform active:scale-95 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                    Unduh Excel (.CSV)
                </a>

                <!-- Tombol Tambah Manual -->
                <button type="button" onclick="openManualModal()" class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-4 py-2 rounded-xl shadow-sm transition transform active:scale-95 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Manual
                </button>

                <!-- Total Badge -->
                <span class="text-xs font-bold bg-white border border-gray-200 px-3 py-2 rounded-xl text-gray-600 shadow-sm">
                    Total: <strong class="text-[#E19404]">{{ $participants->total() }}</strong> Data
                </span>
            </div>
        </div>

        <!-- 4. BOX FILTER 1 BARIS (COMPACT) -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
            <form action="{{ route('admin.participants') }}" method="GET" class="flex flex-col md:flex-row items-center gap-3">
                
                <!-- Search -->
                <div class="w-full md:w-64 relative flex-shrink-0">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, email, NIK..." 
                        class="w-full pl-9 pr-3 py-2 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filter Kategori -->
                <select name="category" class="w-full md:w-auto px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none cursor-pointer">
                    <option value="">Semua Kategori</option>
                    @foreach(['Basic', 'Advance', 'Basic-Advance', 'Online', 'Workshop', 'Basic-Advance + Workshop'] as $cat)
                        <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>

                <!-- Filter Gelombang -->
                <select name="wave" class="w-full md:w-auto px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none cursor-pointer">
                    <option value="">Semua Gelombang</option>
                    @foreach($waves as $waveItem)
                        <option value="{{ $waveItem }}" {{ $wave === $waveItem ? 'selected' : '' }}>{{ $waveItem }}</option>
                    @endforeach
                </select>

                <!-- Filter Status -->
                <select name="status" class="w-full md:w-auto px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Tertunda (Pending)</option>
                </select>

                <!-- Tombol Action 1 Baris -->
                <div class="flex items-center gap-2 w-full md:w-auto md:ml-auto justify-end">
                    @if(!empty($search) || !empty($category) || !empty($wave) || !empty($status))
                        <a href="{{ route('admin.participants') }}" class="px-3.5 py-2 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 hover:bg-red-50 rounded-xl transition whitespace-nowrap">
                            Reset
                        </a>
                    @endif
                    <button type="submit" class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-5 py-2 rounded-xl shadow-sm transition whitespace-nowrap">
                        Terapkan
                    </button>
                </div>

            </form>
        </div>

        <!-- 5. TABEL DATA PESERTA RINGKAS (6 KOLOM) -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Gelombang</th>
                            <th class="py-3.5 px-5">Kategori</th>
                            <th class="py-3.5 px-5">Nama Peserta & Gelar</th>
                            <th class="py-3.5 px-5">Instansi</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($participants as $item)
                            <tr class="hover:bg-gray-50/80 transition">
                                
                                <!-- Kolom 1: Gelombang -->
                                <td class="py-3.5 px-5 font-bold text-gray-800 whitespace-nowrap">
                                    {{ $item->ticket->ticket_name ?? 'Tiket BACT' }}
                                </td>

                                <!-- Kolom 2: Kategori -->
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 bg-[#FBE39D] text-[#E19404] font-extrabold text-xs rounded-lg inline-block whitespace-nowrap">
                                        {{ $item->ticket->ticket_category ?? 'Umum' }}
                                    </span>
                                </td>

                                <!-- Kolom 3: Nama -->
                                <td class="py-3.5 px-5">
                                    <div class="font-extrabold text-gray-900">{{ $item->name_with_title ?: $item->full_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->gmail_account }}</div>
                                </td>

                                <!-- Kolom 4: Instansi -->
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-gray-800">{{ $item->institution_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $item->institution_city }}</div>
                                </td>

                                <!-- Kolom 5: Status -->
                                <td class="py-3.5 px-5 text-center">
                                    @if($item->status === 'paid')
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">
                                            LUNAS
                                        </span>
                                    @elseif($item->status === 'pending')
                                        <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 text-xs font-bold rounded-full">
                                            PENDING
                                        </span>
                                    @else
                                        <span class="inline-block px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-full">
                                            {{ strtoupper($item->status) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Kolom 6: Aksi (View & Edit) -->
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tombol View -->
                                        <button type="button" onclick="openViewModal({{ json_encode($item) }})" 
                                            class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold rounded-lg transition">
                                            View
                                        </button>
                                        <!-- Tombol Edit -->
                                        <button type="button" onclick="openEditModal({{ json_encode($item) }})" 
                                            class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition">
                                            Edit
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 px-6 text-center text-gray-500">
                                    Belum ada data peserta yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION BAR DI BAWAH TABEL -->
            @if($participants->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $participants->links() }}
                </div>
            @endif
        </div>

    </main>

    <!-- =========================================================
         6. MODAL VIEW DETAIL PESERTA
         ========================================================= -->
    <div id="viewModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Detail Lengkap Peserta</h3>
                <button type="button" onclick="closeViewModal()" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>
            <div class="p-6 space-y-3 text-sm" id="viewModalContent">
                <!-- Diisi otomatis melalui Javascript -->
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 text-right">
                <button type="button" onclick="closeViewModal()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl">Tutup</button>
            </div>
        </div>
    </div>

    <!-- =========================================================
         7. MODAL EDIT STATUS & DATA PESERTA
         ========================================================= -->
    <div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Edit Status & Data Peserta</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="editForm" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Status Pembayaran</label>
                    <select name="status" id="edit_status" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                        <option value="paid">Lunas (Paid)</option>
                        <option value="pending">Tertunda (Pending)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Lengkap (KTP)</label>
                    <input type="text" name="full_name" id="edit_full_name" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama & Gelar Sertifikat</label>
                    <input type="text" name="name_with_title" id="edit_name_with_title" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nomor WhatsApp</label>
                    <input type="text" name="whatsapp_number" id="edit_whatsapp_number" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         8. MODAL TAMBAH PESERTA MANUAL (TITIPAN ADMIN)
         ========================================================= -->
    <div id="manualModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-lg">Tambah Peserta Manual (Titipan / Admin)</h3>
                <button type="button" onclick="closeManualModal()" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form action="{{ route('admin.participants.storeManual') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih Tiket <span class="text-red-500">*</span></label>
                    <select name="ticket_id" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                        <option value="">-- Pilih Jenis & Gelombang Tiket --</option>
                        @foreach($allTickets as $tItem)
                            <option value="{{ $tItem->id }}">
                                {{ $tItem->ticket_category ?? 'Umum' }} — {{ $tItem->ticket_name ?? 'Tiket' }} (Rp{{ number_format($tItem->price, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Lengkap (KTP) <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" required placeholder="Contoh: Budi Santoso" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama & Gelar <span class="text-red-500">*</span></label>
                        <input type="text" name="name_with_title" required placeholder="Contoh: dr. Budi Santoso, Sp.PK" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="gmail_account" required placeholder="email@gmail.com" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">No WhatsApp <span class="text-red-500">*</span></label>
                        <input type="text" name="whatsapp_number" required placeholder="08123456789" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">NIK (16 Digit) <span class="text-red-500">*</span></label>
                        <input type="text" name="nik" required maxlength="16" placeholder="34040..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Profesi Medis <span class="text-red-500">*</span></label>
                        <input type="text" name="profession" required value="Dokter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Instansi / RS <span class="text-red-500">*</span></label>
                        <input type="text" name="institution_name" required placeholder="RSUD Dr. Soetomo" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Status Pembayaran <span class="text-red-500">*</span></label>
                        <select name="status" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                            <option value="paid" selected>Lunas (Paid)</option>
                            <option value="pending">Tertunda (Pending)</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeManualModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan Manual</button>
                </div>
            </form>
        </div>
    </div>

        <!-- FOOTER -->
    @include('partials.admin-footer')


    <!-- SCRIPT KONTROL MODAL -->
    <script>
        function openViewModal(item) {
            const content = document.getElementById('viewModalContent');
            content.innerHTML = `
                <div class="border-b border-gray-100 pb-3">
                    <p class="text-xs text-gray-400 uppercase font-bold">Nama & Gelar Sertifikat</p>
                    <p class="text-base font-extrabold text-gray-900">${item.name_with_title || item.full_name}</p>
                    <p class="text-xs text-gray-500">KTP: ${item.full_name} | NIK: ${item.nik}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Email Gmail</p>
                        <p class="font-semibold text-gray-800">${item.gmail_account}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">No WhatsApp</p>
                        <p class="font-semibold text-[#E19404]">${item.whatsapp_number}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Email Plataran Sehat</p>
                        <p class="font-semibold text-gray-800">${item.plataran_sehat_email || '-'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Profesi Medis</p>
                        <p class="font-semibold text-gray-800">${item.profession || '-'}</p>
                    </div>
                </div>
                <div class="border-b border-gray-100 pb-3">
                    <p class="text-xs text-gray-400 uppercase font-bold">Instansi Asal</p>
                    <p class="font-bold text-gray-900">${item.institution_name}</p>
                    <p class="text-xs text-gray-500">${item.institution_city || '-'}, ${item.institution_province || '-'}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Nominal Pembayaran</p>
                        <p class="text-base font-black text-gray-900">Rp${new Intl.NumberFormat('id-ID').format(item.amount)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Status Pesanan</p>
                        <p class="font-extrabold uppercase ${item.status === 'paid' ? 'text-green-600' : 'text-orange-500'}">${item.status}</p>
                    </div>
                </div>
            `;
            document.getElementById('viewModal').classList.remove('hidden');
        }
        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        function openEditModal(item) {
            document.getElementById('edit_status').value = item.status;
            document.getElementById('edit_full_name').value = item.full_name;
            document.getElementById('edit_name_with_title').value = item.name_with_title || item.full_name;
            document.getElementById('edit_whatsapp_number').value = item.whatsapp_number;
            document.getElementById('editForm').action = `/admin/participants/${item.id}/update-status`;
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openManualModal() {
            document.getElementById('manualModal').classList.remove('hidden');
        }
        function closeManualModal() {
            document.getElementById('manualModal').classList.add('hidden');
        }
    </script>

</body>
</html>