<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kelola Kuota & Harga - Admin BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F8F9FA] min-h-screen font-sans flex flex-col">

    <!-- 1-2. NAVBAR UTAMA + SUB-NAVBAR -->
    @include('partials.admin-navbar', ['active' => 'tickets'])

    <!-- 3. KONTEN UTAMA -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12 flex-grow w-full">
        
        <!-- Notifikasi Sukses / Error -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl text-sm">
                <div class="font-bold mb-1">Terjadi kesalahan pada input data:</div>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- SECTION 1: TIKET SEMINAR & WORKSHOP -->
        <!-- ========================================== -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-extrabold text-gray-900">
                    Tiket Seminar & Workshop
                </h2>
                <!-- Tombol Buka Modal Tambah Tiket -->
                <button type="button" onclick="openAddTicketModal()" class="bg-[#E19404] hover:bg-orange-600 text-white font-extrabold px-5 py-2.5 rounded-xl transition shadow-sm text-xs flex items-center gap-1.5 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Tiket
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 uppercase tracking-wider text-xs border-b border-gray-200">
                                <th class="px-6 py-4 font-bold">Nama Tiket</th>
                                <th class="px-6 py-4 font-bold">Harga Satuan</th>
                                <th class="px-6 py-4 font-bold">Mulai Berlaku</th>
                                <th class="px-6 py-4 font-bold">Berakhir</th>
                                <th class="px-6 py-4 font-bold text-center">Tersedia</th>
                                <th class="px-6 py-4 font-bold text-center">Status</th>
                                <th class="px-6 py-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                            @forelse($tickets as $ticket)
                            @php
                                $now = \Carbon\Carbon::now();
                                $isStarted = is_null($ticket->start_date) || $ticket->start_date->lte($now);
                                $isNotEnded = is_null($ticket->end_date) || $ticket->end_date->gte($now);
                                $isDateValid = $isStarted && $isNotEnded;
                            @endphp

                            <tr class="hover:bg-gray-50/70 transition {{ $isDateValid ? '' : 'hidden non-active-ticket bg-gray-50/50 text-gray-400' }}">
                                <td class="px-6 py-4 font-semibold text-gray-900">
                                    {{ $ticket->ticket_name }}: {{ $ticket->ticket_category }}
                                    @if(!$isDateValid)
                                        <span class="ml-2 inline-block bg-gray-200 text-gray-600 px-2 py-0.5 rounded text-[10px] font-bold uppercase">Di Luar Tanggal</span>
                                    @elseif(!$ticket->is_active)
                                        <span class="ml-2 inline-block bg-red-100 text-red-600 px-2 py-0.5 rounded text-[10px] font-bold uppercase">Non-Aktif Manual</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-green-600 font-bold">
                                    Rp {{ number_format($ticket->price, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    {{ $ticket->start_date ? $ticket->start_date->format('d M Y, H:i') : 'Langsung Aktif' }}
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    {{ $ticket->end_date ? $ticket->end_date->format('d M Y, H:i') : 'Tanpa Batas' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-extrabold text-xs">
                                        {{ $ticket->quota }} Slot
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4 text-center">
                                    <label class="relative inline-flex items-center {{ $isDateValid ? 'cursor-pointer' : 'cursor-not-allowed opacity-40' }}">
                                        <input type="checkbox" 
                                               class="sr-only peer ticket-toggle" 
                                               data-id="{{ $ticket->id }}" 
                                               data-url="/admin/tickets/{{ $ticket->id }}/toggle-status"
                                               {{ $ticket->is_active && $isDateValid ? 'checked' : '' }} 
                                               {{ !$isDateValid ? 'disabled' : '' }}>
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                                    </label>
                                </td>

                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Tombol Edit Pop-up -->
                                        <button type="button" onclick="openEditTicketModal({{ $ticket->id }})" 
                                            class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition">
                                            Edit
                                        </button>
                                        <!-- Tombol Hapus -->
                                        <form action="{{ route('admin.tickets.destroy', $ticket->id) }}" method="POST" class="m-0" onsubmit="return confirm('Yakin ingin menghapus tiket ini secara permanen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg transition">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-12 px-6 text-center text-gray-400 text-xs">
                                    Belum ada data tiket seminar.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Baris Unhide Tiket -->
                <div class="bg-gray-50 border-t border-gray-100 px-6 py-3 text-center">
                    <button id="toggle-hidden-tickets" class="text-xs font-bold text-gray-500 hover:text-[#E19404] flex items-center justify-center gap-1.5 mx-auto transition">
                        <span id="toggle-ticket-text">Tampilkan tiket yang tidak berjalan (non-aktif)</span>
                        <svg id="toggle-ticket-icon" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- =========================================================
             SECTION 2: KELOLA TIPE & HARGA KAMAR HOTEL
             ========================================================= -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-extrabold text-gray-900">
                    Tipe & Harga Kamar Hotel
                </h2>
                <!-- Tombol Buka Modal Tambah Kamar -->
                <button type="button" onclick="openAddHotelModal()" class="bg-[#E19404] hover:bg-orange-600 text-white font-extrabold px-5 py-2.5 rounded-xl transition shadow-sm text-xs flex items-center gap-1.5 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Kamar
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6 w-24">Foto</th>
                                <th class="py-3.5 px-6">Tipe & Spesifikasi Kamar</th>
                                <th class="py-3.5 px-6">Harga per Malam</th>
                                <th class="py-3.5 px-6 text-center w-28">Kuota</th>
                                <th class="py-3.5 px-6 text-center w-32">Status</th>
                                <th class="py-3.5 px-6 text-center w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($hotels as $hotel)
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-6">
                                        @if(is_array($hotel->photos) && count($hotel->photos) > 0)
                                            <img src="{{ asset('storage/' . $hotel->photos[0]) }}" alt="{{ $hotel->room_type }}" class="w-16 h-12 rounded-xl object-cover border border-gray-200 shadow-sm">
                                        @else
                                            <div class="w-16 h-12 rounded-xl bg-gray-100 flex items-center justify-center text-[10px] text-gray-400 font-bold border border-gray-200">No Image</div>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-extrabold text-gray-900 text-base">{{ $hotel->room_type }}</div>
                                        <div class="text-xs text-gray-500 mt-1 leading-relaxed line-clamp-2">{{ $hotel->description ?: 'Belum ada deskripsi kamar.' }}</div>
                                    </td>
                                    <td class="py-4 px-6 font-extrabold text-[#E19404] whitespace-nowrap">
                                        Rp {{ number_format($hotel->price_per_night, 0, ',', '.') }}
                                    </td>
                                    <td class="py-4 px-6 text-center font-extrabold text-gray-800">
                                        {{ $hotel->quota }} Kamar
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   class="sr-only peer hotel-toggle" 
                                                   data-id="{{ $hotel->id }}" 
                                                   data-url="{{ route('admin.hotels.toggle', $hotel->id) }}"
                                                   {{ $hotel->is_active ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                                        </label>
                                    </td>
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Tombol Edit Pop-up -->
                                            <button type="button" onclick="openEditHotelModal({{ $hotel->id }})" 
                                                class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition">
                                                Edit
                                            </button>
                                            <form action="{{ route('admin.hotels.destroy', $hotel->id) }}" method="POST" class="m-0" onsubmit="return confirm('Yakin ingin menghapus tipe kamar ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 px-6 text-center text-gray-400 text-xs">
                                        Belum ada data kamar hotel yang ditambahkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <!-- =========================================================
         1. MODAL TAMBAH TIKET SEMINAR
         ========================================================= -->
    <div id="addTicketModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Tambah Tiket Seminar / Workshop</h3>
                <button type="button" onclick="closeAddTicketModal()" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form action="{{ route('admin.tickets.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Gelombang / Tiket</label>
                    <input type="text" name="ticket_name" required placeholder="Contoh: Early Bird / Regular" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kategori Course / Paket</label>
                    <select name="ticket_category" required class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] bg-white">
                        <option value="" disabled selected>-- Pilih Kategori --</option>
                        <option value="Basic">Basic</option>
                        <option value="Advanced">Advanced</option>
                        <option value="Basic-Advanced">Basic-Advanced</option>
                        <option value="Online">Online</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Basic-Advanced + Workshop">Basic-Advanced + Workshop</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Harga Satuan (Rp)</label>
                        <input type="number" name="price" required min="0" placeholder="1000000" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kuota Tersedia (Slot)</label>
                        <input type="number" name="quota" required min="1" value="50" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Mulai Berlaku (Opsional)</label>
                        <input type="datetime-local" name="start_date" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Berakhir Pada (Opsional)</label>
                        <input type="datetime-local" name="end_date" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeAddTicketModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan Tiket</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         2. MODAL EDIT TIKET SEMINAR
         ========================================================= -->
    <div id="editTicketModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Edit Tiket Seminar / Workshop</h3>
                <button type="button" onclick="closeEditTicketModal()" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="editTicketForm" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Gelombang / Tiket</label>
                    <input type="text" name="ticket_name" id="edit_ticket_name" required class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kategori Course / Paket</label>
                    <select name="ticket_category" id="edit_ticket_category" required class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] bg-white">
                        <option value="Basic">Basic</option>
                        <option value="Advanced">Advanced</option>
                        <option value="Basic-Advanced">Basic-Advanced</option>
                        <option value="Online">Online</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Basic-Advanced + Workshop">Basic-Advanced + Workshop</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Harga Satuan (Rp)</label>
                        <input type="number" name="price" id="edit_ticket_price" required min="0" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kuota Tersedia (Slot)</label>
                        <input type="number" name="quota" id="edit_ticket_quota" required min="0" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Mulai Berlaku (Opsional)</label>
                        <input type="datetime-local" name="start_date" id="edit_ticket_start_date" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Berakhir Pada (Opsional)</label>
                        <input type="datetime-local" name="end_date" id="edit_ticket_end_date" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditTicketModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         3. MODAL TAMBAH TIPE KAMAR HOTEL
         ========================================================= -->
    <div id="addHotelModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Tambah Tipe Kamar Baru</h3>
                <button type="button" onclick="closeAddHotelModal()" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="addHotelForm" action="{{ route('admin.hotels.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipe / Nama Kamar</label>
                    <input type="text" name="room_type" required placeholder="Contoh: Deluxe King / Deluxe Twin" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Harga per Malam (Rp)</label>
                        <input type="number" name="price_per_night" required min="0" placeholder="1250000" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Stok Kuota Kamar</label>
                        <input type="number" name="quota" required min="0" value="10" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Deskripsi & Spesifikasi Kamar</label>
                    <textarea name="description" rows="3" placeholder="Contoh: Luas kamar 32m² dengan pemandangan kota..." class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Foto Kamar</label>
                    <input type="file" id="add_photos_picker" accept="image/*" multiple class="hidden">
                    <div id="add_photos_dropzone" onclick="document.getElementById('add_photos_picker').click()"
                        class="cursor-pointer border-2 border-dashed border-gray-300 hover:border-[#E19404] hover:bg-[#FFF8E7]/40 rounded-2xl py-7 text-center transition">
                        <svg class="w-8 h-8 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0-12l-4 4m4-4l4 4"></path></svg>
                        <p class="text-sm font-bold text-gray-700 mt-2">Klik untuk memilih foto</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Anda dapat memilih beberapa foto sekaligus</p>
                    </div>
                    <div id="add_photos_list" class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-3"></div>
                    <button type="button" id="add_photos_addbtn" onclick="document.getElementById('add_photos_picker').click()"
                        class="hidden mt-3 w-full py-2.5 border border-dashed border-[#E19404]/50 text-[#E19404] hover:bg-[#FFF8E7] rounded-xl text-xs font-extrabold transition">
                        + Tambah Foto
                    </button>
                    <p class="text-[11px] text-gray-400 mt-2">Maksimal 5 foto, masing-masing maksimal 2 MB (JPG/PNG/WEBP).</p>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeAddHotelModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan Kamar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         4. MODAL EDIT TIPE KAMAR HOTEL
         ========================================================= -->
    <div id="editHotelModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Edit Data Kamar Hotel</h3>
                <button type="button" onclick="closeEditHotelModal()" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="editHotelForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipe / Nama Kamar</label>
                    <input type="text" name="room_type" id="edit_room_type" required class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Harga per Malam (Rp)</label>
                        <input type="number" name="price_per_night" id="edit_price_per_night" required min="0" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Stok Kuota Kamar</label>
                        <input type="number" name="quota" id="edit_quota" required min="0" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Deskripsi & Spesifikasi Kamar</label>
                    <textarea name="description" id="edit_description" rows="3" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Foto Kamar</label>

                    <!-- Foto lama (bisa dihapus satu per satu) -->
                    <div id="edit_photos_existing" class="grid grid-cols-3 gap-3"></div>

                    <input type="file" id="edit_photos_picker" accept="image/*" multiple class="hidden">
                    <div id="edit_photos_dropzone" onclick="document.getElementById('edit_photos_picker').click()"
                        class="mt-3 cursor-pointer border-2 border-dashed border-gray-300 hover:border-[#E19404] hover:bg-[#FFF8E7]/40 rounded-2xl py-7 text-center transition">
                        <svg class="w-8 h-8 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0-12l-4 4m4-4l4 4"></path></svg>
                        <p class="text-sm font-bold text-gray-700 mt-2">Klik untuk menambah foto baru</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Anda dapat memilih beberapa foto sekaligus</p>
                    </div>
                    <div id="edit_photos_list" class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-3"></div>
                    <button type="button" id="edit_photos_addbtn" onclick="document.getElementById('edit_photos_picker').click()"
                        class="hidden mt-3 w-full py-2.5 border border-dashed border-[#E19404]/50 text-[#E19404] hover:bg-[#FFF8E7] rounded-xl text-xs font-extrabold transition">
                        + Tambah Foto
                    </button>
                    <p class="text-[11px] text-gray-400 mt-2">Foto baru akan ditambahkan setelah foto lama. Total maksimal 5 foto, masing-masing maksimal 2 MB (JPG/PNG/WEBP).</p>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditHotelModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    @include('partials.admin-footer')

    <!-- SCRIPT MODAL, UNHIDE TIKET, & AJAX TOGGLE -->
    <script>
        // Simpan data master dari server ke dalam array JavaScript global
        const ticketsData = @json($tickets);
        const hotelsData = @json($hotels);

        // --- 1. SCRIPT KELOLA MODAL TIKET SEMINAR ---
        function openAddTicketModal() {
            document.getElementById('addTicketModal').classList.remove('hidden');
        }
        function closeAddTicketModal() {
            document.getElementById('addTicketModal').classList.add('hidden');
        }

        function openEditTicketModal(id) {
            const ticket = ticketsData.find(t => t.id === id);
            if (!ticket) return;

            document.getElementById('edit_ticket_name').value = ticket.ticket_name;
            document.getElementById('edit_ticket_category').value = ticket.ticket_category;
            document.getElementById('edit_ticket_price').value = ticket.price;
            document.getElementById('edit_ticket_quota').value = ticket.quota;
            
            if (ticket.start_date) {
                document.getElementById('edit_ticket_start_date').value = ticket.start_date.substring(0, 16);
            } else {
                document.getElementById('edit_ticket_start_date').value = '';
            }
            if (ticket.end_date) {
                document.getElementById('edit_ticket_end_date').value = ticket.end_date.substring(0, 16);
            } else {
                document.getElementById('edit_ticket_end_date').value = '';
            }

            document.getElementById('editTicketForm').action = `/admin/tickets/${ticket.id}`;
            document.getElementById('editTicketModal').classList.remove('hidden');
        }
        function closeEditTicketModal() {
            document.getElementById('editTicketModal').classList.add('hidden');
        }

        // --- 2. SCRIPT KELOLA MODAL KAMAR HOTEL + MANAJER FOTO ---
        const HOTEL_PHOTO_MAX = 5;
        const HOTEL_PHOTO_MAX_BYTES = 2 * 1024 * 1024;
        const HOTEL_STORAGE_BASE = "{{ asset('storage') }}";

        // Hitung total foto aktif pada satu modal (baru + lama yang tidak dihapus)
        function countManagedPhotos(prefix) {
            let total = document.getElementById(prefix + '_photos_list').children.length;
            if (prefix === 'edit') {
                document.querySelectorAll('#edit_photos_existing .js-photo-existing').forEach(function (el) {
                    if (!el.dataset.removed) total++;
                });
            }
            return total;
        }

        function updateHotelPhotoUI(prefix) {
            const dropzone = document.getElementById(prefix + '_photos_dropzone');
            const addBtn = document.getElementById(prefix + '_photos_addbtn');
            const listEl = document.getElementById(prefix + '_photos_list');

            const hasNewPhotos = listEl.children.length > 0 ||
                (prefix === 'edit' && document.querySelectorAll('#edit_photos_existing .js-photo-existing').length > 0);

            // Dropzone besar hanya tampil saat belum ada thumbnail sama sekali
            dropzone.classList.toggle('hidden', hasNewPhotos);
            addBtn.classList.toggle('hidden', !hasNewPhotos);

            const total = countManagedPhotos(prefix);
            const full = total >= HOTEL_PHOTO_MAX;

            addBtn.disabled = full;
            addBtn.classList.toggle('opacity-50', full);
            addBtn.classList.toggle('cursor-not-allowed', full);
            addBtn.innerHTML = full
                ? 'Maksimal ' + HOTEL_PHOTO_MAX + ' foto tercapai'
                : '+ Tambah Foto';
        }

        // Kerangka thumbnail: gambar + badge opsional + tombol hapus
        function buildPhotoThumb(options) {
            const el = document.createElement('div');
            el.className = 'relative aspect-[4/3] rounded-xl overflow-hidden border border-gray-200 bg-gray-100';
            el.title = options.title || '';

            const img = document.createElement('img');
            img.src = options.src;
            img.alt = 'Foto kamar';
            img.className = 'w-full h-full object-cover transition';
            el.appendChild(img);

            if (options.badge) {
                const badge = document.createElement('span');
                badge.className = 'absolute top-1 left-1 text-[9px] font-black px-1.5 py-0.5 rounded-md bg-[#234661]/80 text-white uppercase tracking-wide';
                badge.textContent = options.badge;
                el.appendChild(badge);
            }

            if (options.onDelete) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('aria-label', 'Hapus foto');
                btn.className = 'absolute top-1 right-1 w-6 h-6 flex items-center justify-center rounded-lg bg-red-500 hover:bg-red-600 text-white shadow-md transition js-delete-btn';
                btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>';
                btn.addEventListener('click', options.onDelete);
                el.appendChild(btn);
            }

            return el;
        }

        function toggleExistingPhoto(thumbEl) {
            const form = document.getElementById('editHotelForm');
            const path = thumbEl.dataset.path;
            const overlay = thumbEl.querySelector('.js-delete-overlay');
            const btn = thumbEl.querySelector('.js-delete-btn');
            const img = thumbEl.querySelector('img');

            const willRemove = !thumbEl.dataset.removed;
            thumbEl.dataset.removed = willRemove ? '1' : '';

            img.classList.toggle('opacity-40', willRemove);
            img.classList.toggle('grayscale', willRemove);
            thumbEl.classList.toggle('ring-2', willRemove);
            thumbEl.classList.toggle('ring-red-400', willRemove);
            overlay.classList.toggle('hidden', !willRemove);
            overlay.classList.toggle('flex', willRemove);
            btn.innerHTML = willRemove
                ? '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10M8 15l-5-5 5-5m13 9v4a2 2 0 01-2 2H8"></path></svg>'
                : '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>';
            btn.classList.toggle('bg-red-500', !willRemove);
            btn.classList.toggle('bg-gray-700', willRemove);

            // Sinkronkan input hidden removed_photos[] untuk backend
            let hiddenInput = form.querySelector('.js-removed-photo[value="' + path + '"]');
            if (willRemove && !hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'removed_photos[]';
                hiddenInput.value = path;
                hiddenInput.className = 'js-removed-photo';
                form.appendChild(hiddenInput);
            } else if (!willRemove && hiddenInput) {
                hiddenInput.remove();
            }
        }

        // Render foto lama milik kamar yang sedang diedit
        function renderExistingPhotos(photos) {
            const wrap = document.getElementById('edit_photos_existing');
            wrap.innerHTML = '';

            (photos || []).forEach(function (path) {
                const thumbEl = buildPhotoThumb({
                    src: HOTEL_STORAGE_BASE + '/' + path,
                    title: path,
                    onDelete: function () { toggleExistingPhoto(thumbEl); },
                });
                thumbEl.className += ' js-photo-existing cursor-pointer';
                thumbEl.dataset.path = path;
                thumbEl.dataset.removed = '';

                const overlay = document.createElement('span');
                overlay.className = 'js-delete-overlay absolute inset-0 bg-red-500/70 text-white text-[10px] font-black uppercase tracking-wider items-center justify-center hidden';
                overlay.textContent = 'Akan Dihapus';
                thumbEl.appendChild(overlay);

                wrap.appendChild(thumbEl);
            });
        }

        // Pemilihan file: setiap file dibungkus input tersembunyi sendiri
        function handleHotelPhotoPicked(prefix) {
            const picker = document.getElementById(prefix + '_photos_picker');
            const files = Array.from(picker.files || []);
            picker.value = ''; // reset agar file yang sama bisa dipilih lagi

            if (!files.length) return;

            const form = document.getElementById(prefix === 'add' ? 'addHotelForm' : 'editHotelForm');
            const listEl = document.getElementById(prefix + '_photos_list');
            const skipped = [];
            const oversized = [];

            files.forEach(function (file) {
                if (!file.type.startsWith('image/')) { skipped.push(file.name); return; }
                if (file.size > HOTEL_PHOTO_MAX_BYTES) { oversized.push(file.name); return; }
                if (countManagedPhotos(prefix) >= HOTEL_PHOTO_MAX) { skipped.push(file.name); return; }

                // Bungkus satu file ke dalam satu input file tersembunyi
                const dt = new DataTransfer();
                dt.items.add(file);
                const holder = document.createElement('input');
                holder.type = 'file';
                holder.name = 'photos[]';
                holder.className = 'hidden js-photo-holder';
                holder.files = dt.files;
                form.appendChild(holder);

                const objectUrl = URL.createObjectURL(file);
                const thumbEl = buildPhotoThumb({
                    src: objectUrl,
                    title: file.name,
                    badge: 'Baru',
                    onDelete: function () {
                        URL.revokeObjectURL(objectUrl);
                        holder.remove();
                        thumbEl.remove();
                        updateHotelPhotoUI(prefix);
                    },
                });
                listEl.appendChild(thumbEl);
            });

            if (oversized.length) alert('Foto berikut melebihi batas 2 MB dan tidak ditambahkan:\n- ' + oversized.join('\n- '));
            if (skipped.length) alert('Foto berikut dilewati karena bukan gambar atau melebihi batas ' + HOTEL_PHOTO_MAX + ' foto:\n- ' + skipped.join('\n- '));

            updateHotelPhotoUI(prefix);
        }

        function resetHotelPhotoManager(prefix) {
            const formId = prefix === 'add' ? 'addHotelForm' : 'editHotelForm';
            const form = document.getElementById(formId);

            form.querySelectorAll('.js-photo-holder').forEach(function (el) { el.remove(); });
            form.querySelectorAll('.js-removed-photo').forEach(function (el) { el.remove(); });

            document.getElementById(prefix + '_photos_list').innerHTML = '';
            if (prefix === 'edit') renderExistingPhotos([]);
            document.getElementById(prefix + '_photos_picker').value = '';

            updateHotelPhotoUI(prefix);
        }

        function openAddHotelModal() {
            resetHotelPhotoManager('add');
            document.getElementById('addHotelModal').classList.remove('hidden');
        }
        function closeAddHotelModal() {
            document.getElementById('addHotelModal').classList.add('hidden');
        }

        function openEditHotelModal(id) {
            const item = hotelsData.find(h => h.id === id);
            if (!item) return;

            document.getElementById('edit_room_type').value = item.room_type;
            document.getElementById('edit_price_per_night').value = item.price_per_night;
            document.getElementById('edit_quota').value = item.quota;
            document.getElementById('edit_description').value = item.description || '';

            resetHotelPhotoManager('edit');
            renderExistingPhotos(item.photos);
            updateHotelPhotoUI('edit');

            document.getElementById('editHotelForm').action = `/admin/tickets/hotels/${item.id}`;
            document.getElementById('editHotelModal').classList.remove('hidden');
        }
        function closeEditHotelModal() {
            document.getElementById('editHotelModal').classList.add('hidden');
        }

        // Pasang listener picker & pengaman minimal 1 foto saat tambah kamar
        document.addEventListener('DOMContentLoaded', function () {
            ['add', 'edit'].forEach(function (prefix) {
                document.getElementById(prefix + '_photos_picker')
                    .addEventListener('change', function () { handleHotelPhotoPicked(prefix); });
            });

            document.getElementById('addHotelForm').addEventListener('submit', function (e) {
                if (countManagedPhotos('add') === 0) {
                    e.preventDefault();
                    alert('Minimal 1 foto kamar harus diunggah.');
                }
            });
        });

        // --- 3. SCRIPT UNHIDE TIKET NON-AKTIF & AJAX TOGGLE ---
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggle-hidden-tickets');
            const hiddenRows = document.querySelectorAll('.non-active-ticket');
            const toggleText = document.getElementById('toggle-ticket-text');
            const toggleIcon = document.getElementById('toggle-ticket-icon');
            let isHidden = true;

            if (hiddenRows.length === 0 && toggleBtn) {
                toggleBtn.parentElement.style.display = 'none';
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    hiddenRows.forEach(row => {
                        row.classList.toggle('hidden');
                    });
                    isHidden = !isHidden;
                    if (isHidden) {
                        toggleText.textContent = 'Tampilkan tiket yang tidak berjalan (non-aktif)';
                        toggleIcon.classList.remove('rotate-180');
                    } else {
                        toggleText.textContent = 'Sembunyikan tiket non-aktif';
                        toggleIcon.classList.add('rotate-180');
                    }
                });
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function handleToggle(checkbox) {
                const url = checkbox.getAttribute('data-url');
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Gagal merubah status!');
                        checkbox.checked = !checkbox.checked;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan pada server!');
                    checkbox.checked = !checkbox.checked;
                });
            }

            // Script untuk Toggle Status Tiket
            document.querySelectorAll('.ticket-toggle').forEach(toggle => {
                toggle.addEventListener('change', function() {
                    handleToggle(this);
                });
            });

            // Script untuk Toggle Status Hotel
            document.querySelectorAll('.hotel-toggle').forEach(toggle => {
                toggle.addEventListener('change', function() {
                    handleToggle(this);
                });
            });
        });
    </script>

    @include('partials.admin-upload-validation')

</body>
</html>