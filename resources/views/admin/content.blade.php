<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Konten - Admin BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans">

        <!-- 1-2. NAVBAR UTAMA + SUB-NAVBAR -->
    @include('partials.admin-navbar', ['active' => 'content'])


    <!-- 3. KONTEN UTAMA DENGAN SYSTEM TAB FULL-WIDTH -->
    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow w-full space-y-6" x-data="{ activeTab: 'announcements' }">

        <!-- Notifikasi Sukses / Error -->
        @if(session('success'))
            <div
                class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm">
                <p class="font-bold mb-1">Gagal menyimpan perubahan:</p>
                @foreach ($errors->all() as $error)
                    <p class="text-xs">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-black text-gray-900">Kelola Konten Beranda</h1>
            <p class="text-sm text-gray-500 mt-1">Atur pengumuman informasi, daftar pembicara, jadwal acara, galeri
                dokumentasi, dan mitra sponsor.</p>
        </div>

        <!-- TAB NAVIGASI KONTEN (1 BARIS FULL-WIDTH, SAMA RATA) -->
        <div
            class="bg-white rounded-2xl border border-gray-200 shadow-sm p-1.5 grid grid-cols-2 md:grid-cols-5 gap-1.5 w-full">
            <button @click="activeTab = 'announcements'"
                :class="activeTab === 'announcements' ? 'bg-[#FBE39D] text-[#E19404] font-extrabold shadow-sm' : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                class="w-full py-3 px-3 rounded-xl text-xs transition text-center justify-center">
                Info & Pengumuman
            </button>

            <button @click="activeTab = 'speakers'"
                :class="activeTab === 'speakers' ? 'bg-[#FBE39D] text-[#E19404] font-extrabold shadow-sm' : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                class="w-full py-3 px-3 rounded-xl text-xs transition text-center justify-center">
                Pembicara Simposium
            </button>

            <button @click="activeTab = 'schedules'"
                :class="activeTab === 'schedules' ? 'bg-[#FBE39D] text-[#E19404] font-extrabold shadow-sm' : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                class="w-full py-3 px-3 rounded-xl text-xs transition text-center justify-center">
                Jadwal Acara
            </button>

            <button @click="activeTab = 'galleries'"
                :class="activeTab === 'galleries' ? 'bg-[#FBE39D] text-[#E19404] font-extrabold shadow-sm' : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                class="w-full py-3 px-3 rounded-xl text-xs transition text-center justify-center">
                Galeri Foto
            </button>

            <button @click="activeTab = 'sponsors'"
                :class="activeTab === 'sponsors' ? 'bg-[#FBE39D] text-[#E19404] font-extrabold shadow-sm' : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                class="w-full py-3 px-3 rounded-xl text-xs transition text-center justify-center col-span-2 md:col-span-1">
                Sponsor & Mitra
            </button>
        </div>

        <!-- =========================================================
             PANEL TAB 1: INFO & PENGUMUMAN
             ========================================================= -->
        <div x-show="activeTab === 'announcements'" class="space-y-4"
            x-transition:enter="transition ease-out duration-200">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="p-6 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-extrabold text-gray-900 text-base">Daftar Info & Pengumuman Beranda</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Informasi resmi yang akan ditampilkan di bawah banner
                            utama beranda.</p>
                    </div>
                    <button type="button" onclick="openAddAnnouncementModal()"
                        class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-5 py-2.5 rounded-xl shadow-sm transition whitespace-nowrap">
                        + Tambah Info Baru
                    </button>
                </div>

                <!-- Tabel Data Info -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6 w-24">Label / Badge</th>
                                <th class="py-3.5 px-6">Judul & Isi Pengumuman</th>
                                <th class="py-3.5 px-6 text-center w-32">Status Tayang</th>
                                <th class="py-3.5 px-6 text-center w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($announcements as $info)
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-1 bg-[#FBE39D] text-[#E19404] font-extrabold text-xs rounded-lg inline-block">
                                            {{ $info->badge }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-extrabold text-gray-900">{{ $info->title }}</div>
                                        <div class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $info->content }}</div>
                                        <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] font-bold uppercase tracking-wide">
                                            @if($info->image_path)
                                                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700">Ada Gambar</span>
                                            @endif
                                            @if($info->external_link)
                                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700">Ada Link</span>
                                            @endif
                                            @if($info->attachment_path)
                                                <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700">Ada Lampiran</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <form action="{{ route('admin.announcements.toggle', $info->id) }}" method="POST"
                                            class="m-0">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1 rounded-full text-xs font-bold transition {{ $info->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                                {{ $info->is_active ? 'Aktif Tayang' : 'Disembunyikan' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Tombol Edit -->
                                            <button type="button"
                                                onclick="openEditAnnouncementModal({{ json_encode($info) }})"
                                                class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition">
                                                Edit
                                            </button>
                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('admin.announcements.destroy', $info->id) }}"
                                                method="POST" class="m-0"
                                                onsubmit="return confirm('Yakin ingin menghapus pengumuman ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 px-6 text-center text-gray-400 text-xs">
                                        Belum ada pengumuman beranda yang ditambahkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =========================================================
             PANEL TAB 2: PEMBICARA (Sesuai Alur Baru & Up/Down)
             ========================================================= -->
        <div x-show="activeTab === 'speakers'" class="space-y-4" x-transition:enter="transition ease-out duration-200">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="p-6 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-extrabold text-gray-900 text-base">Daftar Pembicara & Instruktur</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Kelola nama, gelar spesialisasi, instansi asal, foto
                            profil, serta atur urutan tayang pembicara.</p>
                    </div>
                    <button type="button" onclick="openAddSpeakerModal()"
                        class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-5 py-2.5 rounded-xl shadow-sm transition whitespace-nowrap cursor-pointer">
                        + Tambah Pembicara
                    </button>
                </div>

                <!-- Tabel Data Pembicara -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6 w-28 text-center">Atur Posisi</th>
                                <th class="py-3.5 px-6 w-24">Foto</th>
                                <th class="py-3.5 px-6">Nama & Gelar</th>
                                <th class="py-3.5 px-6">Instansi Asal</th>
                                <th class="py-3.5 px-6 text-center w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($speakers as $speaker)
                                <tr class="hover:bg-gray-50/80 transition">

                                    <!-- Kolom 1: Tombol Geser Atas (Up) & Bawah (Down) -->
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1">
                                            <!-- Tombol NAIK -->
                                            <form action="{{ route('admin.speakers.move-up', $speaker->id) }}" method="POST"
                                                class="m-0">
                                                @csrf
                                                <button type="submit" title="Geser ke Atas"
                                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-[#FBE39D] text-gray-700 hover:text-[#E19404] flex items-center justify-center transition font-bold cursor-pointer shadow-2xs">
                                                    &#9650;
                                                </button>
                                            </form>

                                            <!-- Tombol TURUN -->
                                            <form action="{{ route('admin.speakers.move-down', $speaker->id) }}"
                                                method="POST" class="m-0">
                                                @csrf
                                                <button type="submit" title="Geser ke Bawah"
                                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-[#FBE39D] text-gray-700 hover:text-[#E19404] flex items-center justify-center transition font-bold cursor-pointer shadow-2xs">
                                                    &#9660;
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                    <!-- Kolom 2: Foto -->
                                    <td class="py-4 px-6">
                                        <img src="{{ asset('storage/' . $speaker->image) }}" alt="{{ $speaker->name }}"
                                            class="w-12 h-12 rounded-xl object-cover border border-gray-200 shadow-xs">
                                    </td>

                                    <!-- Kolom 3: Nama (Tanpa Role) -->
                                    <td class="py-4 px-6">
                                        <div class="font-extrabold text-gray-900">{{ $speaker->name }}</div>
                                    </td>

                                    <!-- Kolom 4: Instansi -->
                                    <td class="py-4 px-6 text-gray-600 font-medium">
                                        {{ $speaker->institution }}
                                    </td>

                                    <!-- Kolom 5: Aksi Edit & Hapus -->
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Tombol Edit -->
                                            <button type="button"
                                                onclick="openEditSpeakerModal({{ json_encode($speaker) }})"
                                                class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition cursor-pointer">
                                                Edit
                                            </button>
                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('admin.speakers.destroy', $speaker->id) }}" method="POST"
                                                class="m-0"
                                                onsubmit="return confirm('Yakin ingin menghapus data pembicara ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg transition cursor-pointer">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 px-6 text-center text-gray-400 text-xs">
                                        Belum ada data pembicara simposium yang ditambahkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =========================================================
             PANEL TAB 3: JADWAL ACARA
             ========================================================= -->
        <div x-show="activeTab === 'schedules'" class="space-y-4" x-transition:enter="transition ease-out duration-200">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="p-6 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-extrabold text-gray-900 text-base">Daftar Agenda & Jadwal Simposium</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Susun urutan kegiatan simposium berdasarkan hari, jam
                            pelaksanaan, dan pembicara.</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <form action="{{ route('admin.schedules.toggle-section') }}" method="POST" class="m-0 flex items-center gap-2">
                            @csrf
                            <span class="text-[11px] font-bold text-gray-400">
                                Status: <span class="{{ $scheduleVisible ? 'text-green-600' : 'text-gray-600' }}">{{ $scheduleVisible ? 'Tampil di beranda' : 'Disembunyikan' }}</span>
                            </span>
                            <button type="submit"
                                title="Satu tombol toggle untuk seluruh seksi Jadwal Acara"
                                class="px-4 py-2.5 rounded-xl text-xs font-extrabold shadow-sm transition whitespace-nowrap {{ $scheduleVisible ? 'bg-green-100 hover:bg-green-200 text-green-800' : 'bg-gray-700 hover:bg-gray-800 text-white' }}">
                                {{ $scheduleVisible ? 'Sembunyikan' : 'Tampilkan' }}
                            </button>
                        </form>
                        <button type="button" onclick="openAddScheduleModal()"
                            class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-5 py-2.5 rounded-xl shadow-sm transition whitespace-nowrap">
                            + Tambah Jadwal
                        </button>
                    </div>
                </div>

                <!-- Tabel Data Jadwal -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6 w-24 text-center">Hari Ke-</th>
                                <th class="py-3.5 px-6 w-44">Waktu (WIB)</th>
                                <th class="py-3.5 px-6">Judul Kegiatan & Sesi</th>
                                <th class="py-3.5 px-6">Pembicara / Instruktur</th>
                                <th class="py-3.5 px-6 text-center w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($schedules as $item)
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <span
                                            class="px-3 py-1 bg-[#FBE39D] text-[#E19404] font-extrabold text-xs rounded-lg inline-block">
                                            Day {{ $item->day }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 font-extrabold text-gray-800 whitespace-nowrap">
                                        {{ $item->start_time }} - {{ $item->end_time }}
                                    </td>
                                    <td class="py-4 px-6 font-bold text-gray-900">
                                        {{ $item->title }}
                                    </td>
                                    <td class="py-4 px-6 text-gray-600 font-medium">
                                        {{ $item->speaker ?: '-' }}
                                    </td>
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Tombol Edit -->
                                            <button type="button"
                                                onclick="openEditScheduleModal({{ json_encode($item) }})"
                                                class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition">
                                                Edit
                                            </button>
                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('admin.schedules.destroy', $item->id) }}"
                                                method="POST" class="m-0"
                                                onsubmit="return confirm('Yakin ingin menghapus jadwal ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <!-- ... -->
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =========================================================
             PANEL TAB 4: GALERI FOTO
             ========================================================= -->
        <div x-show="activeTab === 'galleries'" class="space-y-4" x-transition:enter="transition ease-out duration-200">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden p-6">
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-200">
                    <div>
                        <h3 class="font-extrabold text-gray-900 text-base">Foto Dokumentasi BACT</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Unggah foto kegiatan simposium periode sebelumnya untuk
                            ditampilkan di galeri beranda.</p>
                    </div>
                    <button type="button" onclick="openAddGalleryModal()"
                        class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-5 py-2.5 rounded-xl shadow-sm transition whitespace-nowrap">
                        + Upload Foto Baru
                    </button>
                </div>

                <!-- Grid Data Foto Galeri -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 pt-6">
                    @forelse($galleries as $gal)
                        <div
                            class="group relative aspect-square rounded-2xl overflow-hidden border border-gray-200 bg-gray-100 shadow-sm">
                            <img src="{{ asset('storage/' . $gal->image) }}" alt="Foto BACT"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-500">

                            <!-- Overlay Hapus Foto -->
                            <div
                                class="absolute inset-0 bg-gray-900/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center p-3">
                                <form action="{{ route('admin.galleries.destroy', $gal->id) }}" method="POST" class="w-full m-0"
                                    onsubmit="return confirm('Yakin ingin menghapus foto ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition shadow-md">
                                        Hapus Foto
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center text-gray-400 text-xs">
                            Belum ada foto dokumentasi kegiatan yang diunggah.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- =========================================================
             PANEL TAB 5: SPONSOR & PARTNER
             ========================================================= -->
        <div x-show="activeTab === 'sponsors'" class="space-y-4" x-transition:enter="transition ease-out duration-200">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-extrabold text-gray-900 text-base">Logo Sponsor & Mitra Pendukung</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Kelola logo perusahaan sponsor, rumah sakit mitra, serta tautan websitenya.</p>
                    </div>
                    <button type="button" onclick="openAddSponsorModal()"
                        class="bg-[#E19404] hover:bg-orange-600 text-white text-xs font-extrabold px-5 py-2.5 rounded-xl shadow-sm transition whitespace-nowrap">
                        + Tambah Sponsor
                    </button>
                </div>

                <!-- Tabel Data Sponsor -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6 w-28">Logo</th>
                                <th class="py-3.5 px-6">Nama Perusahaan / Mitra</th>
                                <th class="py-3.5 px-6">Tautan / Link Website</th>
                                <th class="py-3.5 px-6 text-center w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($sponsors as $sponsor)
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-6">
                                        <div class="w-24 h-12 bg-gray-50 border border-gray-200 rounded-lg p-1.5 flex items-center justify-center">
                                            <img src="{{ asset('storage/' . $sponsor->logo) }}" alt="{{ $sponsor->name }}"
                                                class="max-h-full max-w-full object-contain">
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 font-extrabold text-gray-900">
                                        {{ $sponsor->name }}
                                    </td>
                                    <td class="py-4 px-6 text-gray-600 font-medium text-xs">
                                        @if($sponsor->link)
                                            <a href="{{ $sponsor->link }}" target="_blank" class="text-[#E19404] hover:underline truncate inline-block max-w-xs">
                                                {{ $sponsor->link }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Tombol Edit -->
                                            <button type="button"
                                                onclick="openEditSponsorModal({{ json_encode($sponsor) }})"
                                                class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-[#E19404] text-xs font-bold rounded-lg transition">
                                                Edit
                                            </button>
                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('admin.sponsors.destroy', $sponsor->id) }}" method="POST"
                                                class="m-0" onsubmit="return confirm('Yakin ingin menghapus sponsor ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 px-6 text-center text-gray-400 text-xs">
                                        Belum ada data sponsor dan mitra yang ditambahkan.
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
         MODAL TAMBAH INFO / PENGUMUMAN BARU
         ========================================================= -->
    <div id="addAnnouncementModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Tambah Info / Pengumuman Baru</h3>
                <button type="button" onclick="closeAddAnnouncementModal()"
                    class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form action="{{ route('admin.announcements.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Label / Badge (Maks. 15
                        Karakter)</label>
                    <input type="text" name="badge" required placeholder="Contoh: INFO, PENTING, ACARA"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Judul Pengumuman</label>
                    <input type="text" name="title" required placeholder="Contoh: Pendaftaran Early Bird Resmi Dibuka!"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Isi Keterangan Lengkap</label>
                    <textarea name="content" rows="4" required
                        placeholder="Tuliskan keterangan detail informasi di sini..."
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Gambar Pengumuman (Opsional, JPG/PNG/WEBP, Maks 3MB)</label>
                    <input type="file" name="image" accept="image/*" data-max-size="3145728" data-max-label="gambar pengumuman"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Link Terkait (Opsional)</label>
                    <input type="url" name="link" placeholder="Contoh: https://example.com"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Lampiran (Opsional, PDF/DOC/XLS/PPT/ZIP, Maks 5MB)</label>
                    <input type="file" name="attachment" data-max-size="5242880" data-max-label="lampiran pengumuman"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeAddAnnouncementModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan
                        Info</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL EDIT INFO / PENGUMUMAN
         ========================================================= -->
    <div id="editAnnouncementModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Edit Info / Pengumuman</h3>
                <button type="button" onclick="closeEditAnnouncementModal()"
                    class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="editAnnouncementForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Label / Badge</label>
                    <input type="text" name="badge" id="edit_badge" required
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Judul Pengumuman</label>
                    <input type="text" name="title" id="edit_title" required
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Isi Keterangan Lengkap</label>
                    <textarea name="content" id="edit_content" rows="4" required
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ganti Gambar (Opsional)</label>
                    <input type="file" name="image" accept="image/*" data-max-size="3145728" data-max-label="gambar pengumuman"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl">
                    <div id="edit_image_preview_wrap" class="mt-2 hidden text-xs text-gray-500">
                        Gambar saat ini:
                        <a id="edit_image_preview_link" href="#" target="_blank" rel="noopener noreferrer"
                            class="text-[#E19404] hover:underline">Lihat gambar</a>
                    </div>
                    <label id="edit_remove_image_wrap" class="mt-2 hidden items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300">
                        Hapus gambar saat ini
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Link Terkait (Opsional)</label>
                    <input type="url" name="link" id="edit_link" placeholder="Contoh: https://example.com"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ganti Lampiran (Opsional)</label>
                    <input type="file" name="attachment" data-max-size="5242880" data-max-label="lampiran pengumuman"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl">
                    <div id="edit_attachment_preview_wrap" class="mt-2 hidden text-xs text-gray-500">
                        Lampiran saat ini:
                        <a id="edit_attachment_preview_link" href="#" target="_blank" rel="noopener noreferrer"
                            class="text-[#E19404] hover:underline"></a>
                    </div>
                    <label id="edit_remove_attachment_wrap" class="mt-2 hidden items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="remove_attachment" value="1" class="rounded border-gray-300">
                        Hapus lampiran saat ini
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditAnnouncementModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL TAMBAH PEMBICARA BARU (Tanpa Role & Nomor Urut)
         ========================================================= -->
    <div id="addSpeakerModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Tambah Pembicara Simposium</h3>
                <button type="button" onclick="closeAddSpeakerModal()"
                    class="text-gray-500 hover:text-gray-800 cursor-pointer">✕</button>
            </div>

            <form action="{{ route('admin.speakers.store') }}" method="POST" enctype="multipart/form-data"
                class="p-6 space-y-4">
                @csrf

                <!-- 1. Nama Lengkap & Gelar (Sekarang 1 baris penuh, tidak dibagi dengan nomor urut lagi) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Nama Lengkap & Gelar <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: dr. Budi Santoso, Sp.PK"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] transition">
                </div>

                <!-- 2. Instansi Asal / Rumah Sakit -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Instansi Asal / Rumah Sakit <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="institution" required placeholder="Contoh: RSUD Dr. Soetomo Surabaya"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] transition">
                </div>

                <!-- 3. Upload Foto Profil -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Foto Profil Pembicara (JPG/PNG, Maks 2MB) <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="image" required accept="image/*" data-max-size="2097152" data-max-label="foto pembicara"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl transition">
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeAddSpeakerModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm transition cursor-pointer">
                        Simpan Pembicara
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL EDIT PEMBICARA (Tanpa Role & Nomor Urut)
         ========================================================= -->
    <div id="editSpeakerModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Edit Data Pembicara</h3>
                <button type="button" onclick="closeEditSpeakerModal()"
                    class="text-gray-500 hover:text-gray-800 cursor-pointer">✕</button>
            </div>

            <form id="editSpeakerForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <!-- 1. Nama Lengkap & Gelar (1 Baris Penuh) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Nama Lengkap & Gelar <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="edit_speaker_name" required
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] transition">
                </div>

                <!-- 2. Instansi Asal / Rumah Sakit -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Instansi Asal / Rumah Sakit <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="institution" id="edit_speaker_institution" required
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] transition">
                </div>

                <!-- 3. Upload Foto Profil Baru (Opsional) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Ganti Foto Profil (Opsional - Kosongkan jika tidak diganti)
                    </label>
                    <input type="file" name="image" accept="image/*" data-max-size="2097152" data-max-label="foto pembicara"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl transition">
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeEditSpeakerModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm transition cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- =========================================================
     MODAL TAMBAH JADWAL BARU
     ========================================================= -->
    <div id="addScheduleModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Tambah Agenda / Jadwal Baru</h3>
                <button type="button" onclick="closeAddScheduleModal()"
                    class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form action="{{ route('admin.schedules.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hari Ke-</label>
                        <input type="number" name="day" required min="1" value="1" placeholder="1"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Mulai</label>
                        <input type="time" name="start_time" required
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Selesai</label>
                        <input type="time" name="end_time" required
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Judul Kegiatan / Sesi</label>
                    <input type="text" name="title" required
                        placeholder="Contoh: Plenary Session: Transfusion Clinical Practice"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Pembicara (Opsional -
                        Kosongkan jika ISHOMA/Registrasi)</label>
                    <input type="text" name="speaker" placeholder="Contoh: Prof. Dr. dr. Budi Santoso, Sp.PK"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeAddScheduleModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan
                        Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL EDIT JADWAL ACARA
         ========================================================= -->
    <div id="editScheduleModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Edit Agenda / Jadwal</h3>
                <button type="button" onclick="closeEditScheduleModal()"
                    class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="editScheduleForm" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hari Ke-</label>
                        <input type="number" name="day" id="edit_schedule_day" required min="1"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Mulai</label>
                        <input type="time" name="start_time" id="edit_schedule_start_time" required
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Selesai</label>
                        <input type="time" name="end_time" id="edit_schedule_end_time" required
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Judul Kegiatan / Sesi</label>
                    <input type="text" name="title" id="edit_schedule_title" required
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Pembicara
                        (Opsional)</label>
                    <input type="text" name="speaker" id="edit_schedule_speaker"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditScheduleModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL UPLOAD GALERI FOTO BARU
         ========================================================= -->
    <div id="addGalleryModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Upload Foto Galeri</h3>
                <button type="button" onclick="closeAddGalleryModal()"
                    class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form action="{{ route('admin.galleries.store') }}" method="POST" enctype="multipart/form-data"
                class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih File Foto (JPG/PNG/WEBP, Maks 3MB)</label>
                    <input type="file" name="image" required accept="image/*" data-max-size="3145728" data-max-label="foto galeri"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeAddGalleryModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Upload
                        Foto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL TAMBAH SPONSOR BARU
         ========================================================= -->
    <div id="addSponsorModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Tambah Sponsor & Mitra</h3>
                <button type="button" onclick="closeAddSponsorModal()"
                    class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form action="{{ route('admin.sponsors.store') }}" method="POST" enctype="multipart/form-data"
                class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Perusahaan / Mitra <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: PT Medika Nusantara"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Link Website (Opsional)</label>
                        <input type="text" name="link" placeholder="Contoh: https://medika.com"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">File Logo Sponsor (PNG/JPG/SVG, Maks 2MB) <span class="text-red-500">*</span></label>
                    <input type="file" name="logo" required accept="image/*" data-max-size="2097152" data-max-label="logo sponsor"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl">
                    <p class="text-[11px] text-gray-400 mt-1">Disarankan menggunakan format logo berlatar transparan (.PNG / .SVG).</p>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeAddSponsorModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan
                        Sponsor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         MODAL EDIT SPONSOR
         ========================================================= -->
    <div id="editSponsorModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 overflow-hidden">
            <div class="bg-[#FBE39D] px-6 py-4 border-b border-[#E19404]/20 flex justify-between items-center">
                <h3 class="font-extrabold text-gray-900 text-base">Edit Data Sponsor</h3>
                <button type="button" onclick="closeEditSponsorModal()"
                    class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <form id="editSponsorForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Perusahaan / Mitra <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit_sponsor_name" required
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Link Website (Opsional)</label>
                        <input type="text" name="link" id="edit_sponsor_link" placeholder="Contoh: https://medika.com"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ganti Logo (Opsional - Kosongkan
                        jika tidak diganti)</label>
                    <input type="file" name="logo" accept="image/*" data-max-size="2097152" data-max-label="logo sponsor"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#FBE39D] file:text-[#E19404] hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-xl">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditSponsorModal()"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-xs font-extrabold text-white bg-[#E19404] hover:bg-orange-600 rounded-xl shadow-sm">Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>

        <!-- FOOTER -->
    @include('partials.admin-footer')


    <!-- SCRIPT KONTROL MODAL -->
    <script>
        function openAddAnnouncementModal() {
            document.getElementById('addAnnouncementModal').classList.remove('hidden');
        }
        function closeAddAnnouncementModal() {
            document.getElementById('addAnnouncementModal').classList.add('hidden');
        }

        function openEditAnnouncementModal(item) {
            document.getElementById('edit_badge').value = item.badge;
            document.getElementById('edit_title').value = item.title;
            document.getElementById('edit_content').value = item.content;
            document.getElementById('edit_link').value = item.external_link || '';
            document.getElementById('editAnnouncementForm').action = `/admin/content/announcements/${item.id}`;

            const imageWrap = document.getElementById('edit_image_preview_wrap');
            const imageLink = document.getElementById('edit_image_preview_link');
            const removeImageWrap = document.getElementById('edit_remove_image_wrap');
            const removeImageCheckbox = removeImageWrap.querySelector('input[name="remove_image"]');

            if (item.image_path) {
                imageLink.href = `/storage/${item.image_path}`;
                imageWrap.classList.remove('hidden');
                removeImageWrap.classList.remove('hidden');
                removeImageWrap.classList.add('flex');
            } else {
                imageWrap.classList.add('hidden');
                removeImageWrap.classList.add('hidden');
                removeImageWrap.classList.remove('flex');
            }
            removeImageCheckbox.checked = false;

            const attachmentWrap = document.getElementById('edit_attachment_preview_wrap');
            const attachmentLink = document.getElementById('edit_attachment_preview_link');
            const removeAttachmentWrap = document.getElementById('edit_remove_attachment_wrap');
            const removeAttachmentCheckbox = removeAttachmentWrap.querySelector('input[name="remove_attachment"]');

            if (item.attachment_path) {
                attachmentLink.href = `/storage/${item.attachment_path}`;
                attachmentLink.textContent = item.attachment_name || 'Lihat lampiran';
                attachmentWrap.classList.remove('hidden');
                removeAttachmentWrap.classList.remove('hidden');
                removeAttachmentWrap.classList.add('flex');
            } else {
                attachmentWrap.classList.add('hidden');
                removeAttachmentWrap.classList.add('hidden');
                removeAttachmentWrap.classList.remove('flex');
            }
            removeAttachmentCheckbox.checked = false;

            document.getElementById('editAnnouncementModal').classList.remove('hidden');
        }
        function closeEditAnnouncementModal() {
            document.getElementById('editAnnouncementModal').classList.add('hidden');
        }

        function openAddSpeakerModal() {
            document.getElementById('addSpeakerModal').classList.remove('hidden');
        }
        function closeAddSpeakerModal() {
            document.getElementById('addSpeakerModal').classList.add('hidden');
        }

        function openEditSpeakerModal(item) {
            document.getElementById('edit_speaker_name').value = item.name;
            document.getElementById('edit_speaker_institution').value = item.institution;
            document.getElementById('editSpeakerForm').action = `/admin/content/speakers/${item.id}`;
            document.getElementById('editSpeakerModal').classList.remove('hidden');
        }
        function closeEditSpeakerModal() {
            document.getElementById('editSpeakerModal').classList.add('hidden');
        }

        function openAddScheduleModal() {
            document.getElementById('addScheduleModal').classList.remove('hidden');
        }
        function closeAddScheduleModal() {
            document.getElementById('addScheduleModal').classList.add('hidden');
        }

        function openEditScheduleModal(item) {
            document.getElementById('edit_schedule_day').value = item.day;
            document.getElementById('edit_schedule_start_time').value = item.start_time;
            document.getElementById('edit_schedule_end_time').value = item.end_time;
            document.getElementById('edit_schedule_title').value = item.title;
            document.getElementById('edit_schedule_speaker').value = item.speaker || '';
            document.getElementById('editScheduleForm').action = `/admin/content/schedules/${item.id}`;
            document.getElementById('editScheduleModal').classList.remove('hidden');
        }
        function closeEditScheduleModal() {
            document.getElementById('editScheduleModal').classList.add('hidden');
        }

        function openAddGalleryModal() {
            document.getElementById('addGalleryModal').classList.remove('hidden');
        }
        function closeAddGalleryModal() {
            document.getElementById('addGalleryModal').classList.add('hidden');
        }

        function openAddSponsorModal() {
            document.getElementById('addSponsorModal').classList.remove('hidden');
        }
        function closeAddSponsorModal() {
            document.getElementById('addSponsorModal').classList.add('hidden');
        }

        function openEditSponsorModal(item) {
            document.getElementById('edit_sponsor_name').value = item.name;
            document.getElementById('edit_sponsor_link').value = item.link || '';
            document.getElementById('editSponsorForm').action = `/admin/content/sponsors/${item.id}`;
            document.getElementById('editSponsorModal').classList.remove('hidden');
        }
        function closeEditSponsorModal() {
            document.getElementById('editSponsorModal').classList.add('hidden');
        }
    </script>

    @include('partials.admin-upload-validation')

</body>

</html>