<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Grup WhatsApp - Admin BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F8F9FA] min-h-screen font-sans flex flex-col">

    @include('partials.admin-navbar', ['active' => 'groups'])

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 flex-grow w-full">

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

        <div>
            <div class="mb-1">
                <h2 class="text-xl font-extrabold text-gray-900">Grup WhatsApp Peserta</h2>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                Link grup dibedakan hanya berdasarkan <b>kategori tiket</b>. Semua gelombang
                (Early Bird &amp; Regular) dengan kategori yang sama akan masuk ke grup yang sama,
                misalnya <i>Early Bird: Basic</i> dan <i>Regular: Basic</i> berbagi satu link.
                Link ini otomatis disisipkan di notifikasi WhatsApp untuk pembeli tiket lunas.
                Biarkan kosong jika grup belum dibuat.
            </p>

            <form action="{{ route('admin.groups.update') }}" method="POST">
                @csrf

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500 uppercase tracking-wider text-xs border-b border-gray-200">
                                    <th class="px-6 py-4 font-bold">Kategori Tiket</th>
                                    <th class="px-6 py-4 font-bold">Link Grup WhatsApp</th>
                                    <th class="px-6 py-4 font-bold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                                @forelse($groups as $group)
                                <tr class="hover:bg-gray-50/70 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold text-gray-900">{{ $group['name'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 w-1/2">
                                        <input type="text"
                                               name="links[{{ $group['category'] }}]"
                                               value="{{ $group['link'] }}"
                                               placeholder="https://chat.whatsapp.com/..."
                                               class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E19404]/40 focus:border-[#E19404] transition">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($group['link'])
                                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-green-50 text-green-700">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Terisi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">
                                                Belum diisi
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center text-gray-400">
                                        Belum ada kategori tiket. Tambahkan tiket terlebih dahulu di halaman <a href="{{ route('admin.tickets.index') }}" class="text-[#E19404] font-bold underline">Kuota & Harga</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="bg-[#E19404] hover:bg-orange-600 text-white font-extrabold px-6 py-2.5 rounded-xl transition shadow-sm text-sm">
                        Simpan Semua Link
                    </button>
                </div>
            </form>
        </div>

    </main>

</body>
</html>