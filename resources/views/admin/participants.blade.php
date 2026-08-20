<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tiket Peserta - Admin BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans">

    @include('partials.admin-navbar', ['active' => 'participants'])

    @php
        $tab            = $tab ?? 'peserta';
        $categoryOptions = ['Basic', 'Advance', 'Basic-Advance', 'Online', 'Workshop', 'Basic-Advance + Workshop'];
        $baseQuery      = request()->query();
        unset($baseQuery['tab']);
    @endphp

    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow w-full space-y-6">

        <!-- Notifikasi -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>{{ session('error') }}</span>
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
                <p class="text-sm text-gray-500 mt-0.5">Pantau seluruh status pendaftaran, kelola data, serta unduh rekapitulasi peserta.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Tombol Unduh Excel (mengikuti tab & filter aktif) -->
                <a href="{{ route('admin.participants.export', request()->query()) }}" class="bg-green-600 hover:bg-green-700 text-white text-xs font-extrabold px-4 py-2 rounded-xl shadow-sm transition transform active:scale-95 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                    Unduh Excel (.CSV)
                </a>

                <!-- Tombol Tambah Manual (HANYA di tab Data All) -->
                @if($tab === 'all')
                    <button type="button" onclick="openManualModal()" class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-4 py-2 rounded-xl shadow-sm transition transform active:scale-95 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Manual
                    </button>
                @endif
            </div>
        </div>

        <!-- TAB: Data Peserta (lunas) vs Data All -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-1.5 inline-flex gap-1">
            <a href="{{ route('admin.participants', array_merge($baseQuery, ['tab' => 'all'])) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition whitespace-nowrap {{ $tab === 'all' ? 'bg-[#234661] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                Data All
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full {{ $tab === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $allCount }}</span>
            </a>
            <a href="{{ route('admin.participants', array_merge($baseQuery, ['tab' => 'peserta'])) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition whitespace-nowrap {{ $tab === 'peserta' ? 'bg-[#234661] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                Data Peserta
                <span class="text-[10px] font-black px-2 py-0.5 rounded-full {{ $tab === 'peserta' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $paidCount }}</span>
            </a>
        </div>

        <p class="text-xs text-gray-500 -mt-2">
            @if($tab === 'peserta')
                Tab <b>Data Peserta</b> hanya menampilkan peserta yang sudah <b>Lunas / Fix</b>. Hapus tersedia di sini — data yang dihapus berpindah ke tab Data All dengan status <b>Dihapus</b>.
            @else
                Tab <b>Data All</b> menampilkan semua peserta — <b>Lunas</b>, <b>Tertunda</b>, <b>Dibatalkan</b>, maupun <b>Dihapus</b>. Edit dan tambah manual hanya tersedia di tab ini. Peserta <b>manual</b> perlu dikonfirmasi agar tercatat sebagai peserta (LUNAS).
            @endif
        </p>

        <!-- BOX FILTER 1 BARIS (COMPACT) -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
            <form action="{{ route('admin.participants') }}" method="GET" class="flex flex-col md:flex-row items-start md:items-center gap-3 flex-wrap">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <!-- Search -->
                <div class="w-54 md:w-54 relative flex-shrink-0">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, email, NIK..."
                        class="w-full pl-9 pr-3 py-2 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filter Kategori (MULTIPLE via checkbox dropdown) -->
                <div class="relative w-full md:w-64 flex-shrink-0">
                    <button type="button" onclick="toggleCategoryDropdown(event)"
                        class="w-full px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white flex items-center justify-between outline-none hover:border-gray-400 transition">
                        <span id="categoryLabel" class="truncate">
                            {{ count($categories) > 0 ? count($categories) . ' Kategori dipilih' : 'Semua Kategori' }}
                        </span>
                        <svg class="w-4 h-4 text-gray-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="categoryDropdown" class="hidden absolute z-40 mt-1.5 w-full bg-white border border-gray-200 rounded-xl shadow-lg p-2 space-y-0.5 max-h-72 overflow-y-auto ">
                        @foreach($categoryOptions as $cat)
                            <label class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg hover:bg-gray-50 cursor-pointer text-xs font-medium">
                                <input type="checkbox" name="categories[]" value="{{ $cat }}"
                                       class="h-4 w-4 rounded border-gray-300 text-[#E19404] focus:ring-[#FBE39D]"
                                       {{ in_array($cat, $categories) ? 'checked' : '' }}
                                       onchange="updateCategoryLabel()">
                                <span>{{ $cat }}</span>
                            </label>
                        @endforeach
                        <div class="border-t border-gray-100 pt-1.5 mt-1 flex justify-between">
                            <button type="button" onclick="clearCategories()" class="text-[10px] font-extrabold text-gray-500 hover:text-red-600 uppercase">Bersihkan</button>
                            <button type="button" onclick="toggleCategoryDropdown(event)" class="text-[10px] font-extrabold text-[#E19404] uppercase">Selesai</button>
                        </div>
                    </div>
                </div>

                <!-- Filter Gelombang -->
                <select name="wave" class="w-full md:w-40 px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none cursor-pointer">
                    <option value="">Semua Gelombang</option>
                    @foreach($waves as $waveItem)
                        <option value="{{ $waveItem }}" {{ $wave === $waveItem ? 'selected' : '' }}>{{ $waveItem }}</option>
                    @endforeach
                </select>

                <!-- Filter Status (hanya di tab Data All) -->
                @if($tab === 'all')
                    <select name="status" class="w-full md:w-40 px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        <option value="deleted" {{ $status === 'deleted' ? 'selected' : '' }}>Dihapus</option>
                    </select>
                @endif

                <!-- Filter Tanggal Dibayar (rentang) -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-[11px] font-extrabold text-gray-400 uppercase">Bayar:</span>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none">
                    <span class="text-xs text-gray-400">s/d</span>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="px-3 py-2 text-xs border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none">
                </div>

                <!-- Tombol Action -->
                <div class="flex items-center gap-2 w-full md:w-auto md:ml-auto justify-end">
                    @if(!empty($search) || count($categories) > 0 || !empty($wave) || !empty($status) || !empty($dateFrom) || !empty($dateTo))
                        <a href="{{ route('admin.participants', ['tab' => $tab]) }}" class="px-3.5 py-2 text-xs font-bold text-gray-500 hover:text-red-600 bg-gray-100 hover:bg-red-50 rounded-xl transition whitespace-nowrap">
                            Reset
                        </a>
                    @endif
                    <button type="submit" class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-5 py-2 rounded-xl shadow-sm transition whitespace-nowrap">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Gelombang</th>
                            <th class="py-3.5 px-5">Kategori</th>
                            <th class="py-3.5 px-5">Sumber</th>
                            <th class="py-3.5 px-5">Nama Peserta & Gelar</th>
                            <th class="py-3.5 px-5">Instansi</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($participants as $item)
                            <tr class="hover:bg-gray-50/80 transition">

                                <!-- Gelombang -->
                                <td class="py-3.5 px-5 font-bold text-gray-800 whitespace-nowrap">
                                    {{ $item->ticket->ticket_name ?? 'Tiket BACT' }}
                                </td>

                                <!-- Kategori -->
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 bg-[#FBE39D] text-[#E19404] font-extrabold text-xs rounded-lg inline-block whitespace-nowrap">
                                        {{ $item->ticket->ticket_category ?? 'Umum' }}
                                    </span>
                                </td>

                                <!-- Sumber (Website / Manual) -->
                                <td class="py-3.5 px-5">
                                    @if($item->source === 'manual')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-700 font-extrabold text-[10px] rounded-full whitespace-nowrap uppercase">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                            Manual
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 border border-blue-200 text-blue-700 font-extrabold text-[10px] rounded-full whitespace-nowrap uppercase">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Website
                                        </span>
                                    @endif
                                </td>

                                <!-- Nama -->
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-1.5">
                                        <div class="font-extrabold text-gray-900">{{ $item->name_with_title ?: $item->full_name }}</div>
                                        @if($tab === 'all' && $item->notes)
                                            <button type="button" onclick="openViewModal({{ json_encode($item) }})"
                                                title="Lihat catatan" class="flex-shrink-0 p-1 text-[#E19404] hover:bg-[#FBE39D] rounded-md transition cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $item->gmail_account }}</div>
                                </td>

                                <!-- Instansi -->
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-gray-800">{{ $item->institution_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $item->institution_city }}</div>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-5 text-center">
                                    @if($item->status === 'paid')
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">LUNAS</span>
                                    @elseif($item->status === 'pending')
                                        <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 text-xs font-bold rounded-full">PENDING</span>
                                    @elseif($item->status === 'cancelled')
                                        <span class="inline-block px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full">DIBATALKAN</span>
                                    @elseif($item->status === 'deleted')
                                        <span class="inline-block px-3 py-1 bg-gray-200 text-gray-600 text-xs font-bold rounded-full">DIHAPUS</span>
                                    @else
                                        <span class="inline-block px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-full">{{ strtoupper($item->status) }}</span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    @if($tab === 'all')
                                        <!-- DATA ALL: 2 baris (atas: View + Konfirmasi, bawah: Edit + Hapus) -->
                                        <div class="flex flex-col items-center gap-1.5">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button type="button" onclick="openViewModal({{ json_encode($item) }})"
                                                    class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold rounded-lg transition">View</button>

                                                @if($item->status === 'paid')
                                                    <a href="{{ route('admin.participants.invoice.preview', $item->id) }}"
                                                        class="px-3 py-1.5 bg-[#234661] hover:bg-[#1c3b54] text-white text-xs font-bold rounded-lg transition inline-flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                        Invoice
                                                    </a>
                                                @endif

                                                @if($item->status === 'deleted')
                                                    <span class="px-3 py-1.5 bg-gray-100 text-gray-500 text-xs font-bold rounded-lg whitespace-nowrap" title="{{ $item->deleted_at ? 'Dihapus ' . $item->deleted_at->format('d M Y H:i') : '' }}">Dihapus</span>
                                                    <form method="POST" action="{{ route('admin.participants.restore', $item->id) }}" onsubmit="return confirm('Kembalikan peserta {{ addslashes($item->name_with_title ?: $item->full_name) }} ke status TERTUNDA? Kuota tiket akan di-reserve ulang.')">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 text-xs font-bold rounded-lg transition whitespace-nowrap">Kembalikan</button>
                                                    </form>
                                                @else
                                                    @if($item->source === 'manual' && !$item->confirmed_at)
                                                        <form method="POST" action="{{ route('admin.participants.confirm', $item->id) }}" onsubmit="return confirm('Konfirmasi {{ addslashes($item->name_with_title ?: $item->full_name) }} agar tercatat sebagai peserta LUNAS?')">
                                                            @csrf
                                                            <button type="submit" class="px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-600 text-xs font-bold rounded-lg transition">Konfirmasi</button>
                                                        </form>
                                                    @elseif($item->source === 'manual' && $item->confirmed_at)
                                                        <span class="px-3 py-1.5 bg-green-100 text-green-700 text-xs font-bold rounded-lg transition whitespace-nowrap" title="Dikonfirmasi {{ $item->confirmed_at->format('d M Y H:i') }}">✓ Terkonfirmasi</span>
                                                    @endif
                                                @endif
                                            </div>
                                            @if($item->status !== 'deleted')
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button type="button" onclick="openEditModal({{ json_encode($item) }})"
                                                        class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition">Edit</button>
                                                    <button type="button" onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->name_with_title ?: $item->full_name) }}')"
                                                        class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg transition">Hapus</button>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <!-- DATA PESERTA: 1 baris (View + Hapus) -->
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button" onclick="openViewModal({{ json_encode($item) }})"
                                                class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold rounded-lg transition">View</button>

                                            @if($item->status === 'paid')
                                                <a href="{{ route('admin.participants.invoice.preview', $item->id) }}"
                                                    class="px-3 py-1.5 bg-[#234661] hover:bg-[#1c3b54] text-white text-xs font-bold rounded-lg transition inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    Invoice
                                                </a>
                                            @endif

                                            @if($item->status === 'deleted')
                                                <span class="px-3 py-1.5 bg-gray-100 text-gray-500 text-xs font-bold rounded-lg whitespace-nowrap" title="{{ $item->deleted_at ? 'Dihapus ' . $item->deleted_at->format('d M Y H:i') : '' }}">Dihapus</span>
                                                <form method="POST" action="{{ route('admin.participants.restore', $item->id) }}" onsubmit="return confirm('Kembalikan peserta {{ addslashes($item->name_with_title ?: $item->full_name) }} ke status TERTUNDA? Kuota tiket akan di-reserve ulang.')">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 text-xs font-bold rounded-lg transition whitespace-nowrap">Kembalikan</button>
                                                </form>
                                            @else
                                                <button type="button" onclick="openDeleteModal({{ $item->id }}, '{{ addslashes($item->name_with_title ?: $item->full_name) }}')"
                                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg transition">Hapus</button>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-6 text-center text-gray-500">
                                    Belum ada data peserta yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($participants->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $participants->links() }}
                </div>
            @endif
        </div>

    </main>

    <!-- =========================================================
         MODAL VIEW DETAIL PESERTA
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
         MODAL EDIT STATUS, DATA & CATATAN (HANYA DI DATA ALL)
         ========================================================= -->
    <div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Edit Status & Data Peserta</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="editForm" method="POST" class="p-6 space-y-4">
                @csrf
                <div id="edit_ticket_row" class="hidden">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tiket (Kategori & Gelombang)</label>
                    <select name="ticket_id" id="edit_ticket_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                        <option value="">-- Pilih Jenis & Gelombang Tiket --</option>
                        @foreach($allTickets as $tItem)
                            <option value="{{ $tItem->id }}">
                                {{ $tItem->ticket_category ?? 'Umum' }} — {{ $tItem->ticket_name ?? 'Tiket' }} (Rp{{ number_format($tItem->price, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-gray-400 mt-1">Khusus peserta manual yang belum dikonfirmasi. Nominal pembayaran ikut menyesuaikan harga tiket terpilih.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Status Pembayaran</label>
                    <select name="status" id="edit_status" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl">
                        <option value="paid">Lunas (Paid)</option>
                        <option value="pending">Tertunda (Pending)</option>
                        <option value="cancelled">Dibatalkan</option>
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
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan <span class="text-gray-400 normal-case font-medium">(alasan ganti nama / pembatalan)</span></label>
                    <textarea name="notes" id="edit_notes" rows="3" placeholder="cth: Diganti karena diwakilkan dr. Andi, dsb." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl"></textarea>
                    <p class="text-[10px] text-gray-400 mt-1" id="edit_notes_stamp"></p>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL KONFIRMASI HAPUS (HANYA DI DATA ALL)
         ========================================================= -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 overflow-hidden">
            <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex justify-between items-center">
                <h3 class="font-extrabold text-red-700 text-base">Hapus Data Peserta</h3>
                <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>
            <form id="deleteForm" method="POST" class="space-y-6">
                @csrf
                <div class="p-6 text-sm text-gray-700 space-y-3">
                    <p>Yakin ingin menghapus data peserta <b id="deleteName"></b>?</p>
                    <p class="text-xs text-red-500">Data tidak dihapus permanen — status akan berubah menjadi <b>Dihapus</b> dan tetap terlihat di tab Data All.</p>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alasan Penghapusan <span class="text-red-500">*</span></label>
                        <textarea name="reason" id="delete_reason" rows="3" required placeholder="cth: Data ganda / salah input / peserta mengundurkan diri" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-100 focus:border-red-300 outline-none"></textarea>
                        <p class="text-[10px] text-gray-400 mt-1">Alasan akan dicatat pada kolom catatan peserta.</p>
                    </div>
                </div>
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 text-xs font-extrabold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL TAMBAH PESERTA MANUAL (HANYA DI DATA ALL)
         ========================================================= -->
    <div id="manualModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-gray-200 overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-lg">Tambah Peserta Manual</h3>
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
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 flex gap-2 items-start">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs text-amber-800 leading-relaxed">Peserta manual disimpan dengan status <b>TERTUNDA</b> dan belum masuk tab
                        <b>Data Peserta</b>. Setelah ditambahkan, klik tombol <b>Konfirmasi</b> pada barisnya untuk
                        mencatatnya sebagai peserta <b>LUNAS</b>.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Provinsi Instansi <span class="text-red-500">*</span></label>
                        <select id="m_provinsi" name="institution_province" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl bg-white cursor-pointer">
                            <option value="">-- Pilih Provinsi --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kabupaten / Kota Instansi <span class="text-red-500">*</span></label>
                        <select id="m_kabupaten" name="institution_city" required disabled class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl bg-white cursor-pointer disabled:bg-gray-100 disabled:cursor-not-allowed">
                            <option value="">-- Pilih Kabupaten --</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kecamatan Instansi <span class="text-red-500">*</span></label>
                    <select id="m_kecamatan" name="institution_district" required disabled class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl bg-white cursor-pointer disabled:bg-gray-100 disabled:cursor-not-allowed">
                        <option value="">-- Pilih Kecamatan --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Awal <span class="text-gray-400 normal-case font-medium">(opsional)</span></label>
                    <textarea name="notes" rows="2" placeholder="cth: Data titipan dari panitia" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-xl"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeManualModal()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan Manual</button>
                </div>
            </form>
        </div>
    </div>

    @include('partials.admin-footer')

    <!-- SCRIPT KONTROL MODAL -->
    <script>
        // ---------- DROPDOWN FILTER KATEGORI MULTI ----------
        function toggleCategoryDropdown(e) {
            if (e) e.stopPropagation();
            document.getElementById('categoryDropdown').classList.toggle('hidden');
        }
        function updateCategoryLabel() {
            const boxes = document.querySelectorAll('input[name="categories[]"]');
            const checked = Array.from(boxes).filter(b => b.checked).length;
            document.getElementById('categoryLabel').textContent = checked ? checked + ' Kategori dipilih' : 'Semua Kategori';
        }
        function clearCategories() {
            document.querySelectorAll('input[name="categories[]"]').forEach(b => b.checked = false);
            updateCategoryLabel();
        }
        document.addEventListener('click', function (e) {
            const dd = document.getElementById('categoryDropdown');
            if (dd && !dd.classList.contains('hidden') && !e.target.closest('#categoryDropdown') && !e.target.closest('button[onclick="toggleCategoryDropdown(event)"]')) {
                dd.classList.add('hidden');
            }
        });

        // ---------- MODAL VIEW ----------
        function openViewModal(item) {
            const sourceChip = item.source === 'manual'
                ? '<span class="inline-block px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-700 font-extrabold text-[10px] rounded-full uppercase">Manual</span>'
                : '<span class="inline-block px-2.5 py-1 bg-blue-50 border border-blue-200 text-blue-700 font-extrabold text-[10px] rounded-full uppercase">Website</span>';
            const statusChip = item.status === 'paid'
                ? '<span class="font-extrabold uppercase text-green-600">Lunas</span>'
                : item.status === 'pending'
                    ? '<span class="font-extrabold uppercase text-orange-500">Tertunda</span>'
                    : '<span class="font-extrabold uppercase text-red-600">Dibatalkan</span>';
            const notesBlock = item.notes
                ? `<div class="border-b border-gray-100 pb-3">
                        <p class="text-xs text-gray-400 uppercase font-bold">Catatan</p>
                        <p class="whitespace-pre-line text-gray-700 mt-1">${item.notes.replace(/</g, '&lt;')}</p>
                        ${item.notes_updated_at ? `<p class="text-[10px] text-gray-400 mt-1">diubah ${item.notes_updated_at}</p>` : ''}
                   </div>` : '';

            document.getElementById('viewModalContent').innerHTML = `
                <div class="border-b border-gray-100 pb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Sumber Pendaftaran</p>
                        ${sourceChip}
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase font-bold">Status Pesanan</p>
                        ${statusChip}
                    </div>
                </div>
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
                <div class="grid grid-cols-2 gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Nominal Pembayaran</p>
                        <p class="text-base font-black text-gray-900">Rp${new Intl.NumberFormat('id-ID').format(item.amount)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold">Dibatalkan Pada</p>
                        <p class="font-semibold text-gray-800">${item.cancelled_at || '-'}</p>
                    </div>
                </div>
                ${item.confirmed_at ? `
                    <div class="border-b border-gray-100 pb-3">
                        <p class="text-xs text-gray-400 uppercase font-bold">Dikonfirmasi Pada (Peserta Manual)</p>
                        <p class="font-bold text-green-700">${item.confirmed_at}</p>
                    </div>` : ''}
                ${item.deleted_at ? `
                    <div class="border-b border-gray-100 pb-3">
                        <p class="text-xs text-gray-400 uppercase font-bold">Dihapus Pada</p>
                        <p class="font-bold text-gray-700">${item.deleted_at}</p>
                    </div>` : ''}
                ${notesBlock}
            `;
            document.getElementById('viewModal').classList.remove('hidden');
        }
        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        // ---------- MODAL EDIT ----------
        function openEditModal(item) {
            const canChangeTicket = item.source === 'manual' && !item.confirmed_at;
            const ticketRow = document.getElementById('edit_ticket_row');
            const ticketSelect = document.getElementById('edit_ticket_id');
            if (canChangeTicket) {
                ticketRow.classList.remove('hidden');
                ticketSelect.value = item.ticket_id || '';
            } else {
                ticketRow.classList.add('hidden');
                ticketSelect.value = '';
            }
            document.getElementById('edit_status').value = item.status;
            document.getElementById('edit_full_name').value = item.full_name;
            document.getElementById('edit_name_with_title').value = item.name_with_title || item.full_name;
            document.getElementById('edit_whatsapp_number').value = item.whatsapp_number;
            document.getElementById('edit_notes').value = item.notes || '';
            document.getElementById('edit_notes_stamp').textContent = item.notes_updated_at ? 'Catatan terakhir diubah: ' + item.notes_updated_at : '';
            document.getElementById('editForm').action = `/admin/participants/${item.id}/update-status`;
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // ---------- MODAL DELETE ----------
        function openDeleteModal(id, name) {
            document.getElementById('deleteName').textContent = name;
            document.getElementById('delete_reason').value = '';
            document.getElementById('deleteForm').action = `/admin/participants/${id}/destroy`;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // ---------- MODAL MANUAL ----------
        function openManualModal() {
            document.getElementById('manualModal').classList.remove('hidden');
        }
        function closeManualModal() {
            document.getElementById('manualModal').classList.add('hidden');
        }

        // ---------- DROPDOWN WILAYAH BERJENJANG (EMSIFA) UNTUK MODAL PESERTA MANUAL ----------
        // Mirip dengan halaman peserta: Provinsi -> Kabupaten/Kota -> Kecamatan.
        (function initWilayahManual() {
            const apiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';
            const selProv = document.getElementById('m_provinsi');
            const selKab  = document.getElementById('m_kabupaten');
            const selKec  = document.getElementById('m_kecamatan');
            if (!selProv || !selKab || !selKec) return;

            fetch(apiBase + '/provinces.json')
                .then(response => response.json())
                .then(provinces => {
                    provinces.forEach(prov => {
                        selProv.innerHTML += `<option value="${prov.name}" data-id="${prov.id}">${prov.name}</option>`;
                    });
                })
                .catch(error => console.error('Error fetching provinces:', error));

            selProv.addEventListener('change', (e) => {
                const provId = e.target.selectedOptions[0] ? e.target.selectedOptions[0].getAttribute('data-id') : null;
                selKab.innerHTML = '<option value="">-- Pilih Kabupaten --</option>';
                selKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                selKab.disabled = true;
                selKec.disabled = true;
                if (provId) {
                    fetch(apiBase + '/regencies/' + provId + '.json')
                        .then(response => response.json())
                        .then(regencies => {
                            regencies.forEach(kab => {
                                selKab.innerHTML += `<option value="${kab.name}" data-id="${kab.id}">${kab.name}</option>`;
                            });
                            selKab.disabled = false;
                        });
                }
            });

            selKab.addEventListener('change', (e) => {
                const kabId = e.target.selectedOptions[0] ? e.target.selectedOptions[0].getAttribute('data-id') : null;
                selKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                selKec.disabled = true;
                if (kabId) {
                    fetch(apiBase + '/districts/' + kabId + '.json')
                        .then(response => response.json())
                        .then(districts => {
                            districts.forEach(kec => {
                                selKec.innerHTML += `<option value="${kec.name}">${kec.name}</option>`;
                            });
                            selKec.disabled = false;
                        });
                }
            });
        })();
    </script>

</body>
</html>