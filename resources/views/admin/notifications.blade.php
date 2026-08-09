<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Notifikasi - Admin BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans">

        <!-- 1-2. NAVBAR UTAMA + SUB-NAVBAR -->
    @include('partials.admin-navbar', ['active' => 'notifications'])


    <!-- 3. KONTEN UTAMA -->
    <main class="max-w-4xl mx-auto px-6 py-10 flex-grow w-full space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-black text-gray-900">Template Notifikasi Tiket Lunas</h1>
            <p class="text-sm text-gray-500 mt-1">Ubah isi pesan notifikasi yang dikirim otomatis ke peserta ketika pembayaran tiket berhasil (via WhatsApp & Email).</p>
        </div>

        <!-- Daftar Placeholder -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs font-extrabold text-[#E19404] uppercase tracking-wider mb-2">Placeholder yang didukung:</p>
            <div class="flex flex-wrap gap-2">
                @foreach(['{nama}', '{tiket}', '{id_pesanan}', '{kode_tiket}', '{invoice}', '{harga}', '{email}', '{qr}'] as $ph)
                    <code class="px-2 py-1 bg-[#FFF8E7] border border-[#E19404]/30 rounded-lg text-xs font-bold text-gray-800">{{ $ph }}</code>
                @endforeach
            </div>
            <p class="text-[11px] text-gray-400 mt-2">Placeholder <code class="font-bold">{qr}</code> menentukan posisi gambar QR. Jika tidak dipakai, QR akan ditambahkan otomatis di akhir pesan (saat opsi QR aktif).</p>
        </div>

        <!-- Form WhatsApp -->
        @php($wa = $templates->firstWhere('key', 'ticket_paid_wa'))
        @if($wa)
            <form action="{{ route('admin.notifications.update') }}" method="POST" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                @csrf
                <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-900">{{ $wa->label }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Dikirim via Fonnte ke WhatsApp peserta.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                        <input type="checkbox" name="ticket_paid_wa_is_active" value="1" {{ $wa->is_active ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                        Aktif
                    </label>
                </div>

                <div class="p-6 sm:p-8 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Isi Pesan WhatsApp</label>
                        <textarea name="ticket_paid_wa_body" rows="10" required
                            class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] font-mono leading-relaxed">{{ $wa->body }}</textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Formatting WA: <code class="font-bold">*teks tebal*</code>, <code class="font-bold italic">_teks miring_</code>. Tulis {qr} untuk posisi gambar QR.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                            <input type="checkbox" name="ticket_paid_wa_include_qr" value="1" {{ $wa->include_qr ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                            Sertakan QR tiket di pesan WhatsApp
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-8 py-3 bg-[#E19404] hover:bg-orange-600 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md transition">
                            Simpan Template WA
                        </button>
                    </div>
                </div>
            </form>
        @endif

        <!-- Form Email -->
        @php($email = $templates->firstWhere('key', 'ticket_paid_email'))
        @if($email)
            <form action="{{ route('admin.notifications.update') }}" method="POST" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                @csrf
                <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-900">{{ $email->label }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Dikirim ke email peserta (saat ini mode log).</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                        <input type="checkbox" name="ticket_paid_email_is_active" value="1" {{ $email->is_active ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                        Aktif
                    </label>
                </div>

                <div class="p-6 sm:p-8 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Subject Email</label>
                        <input type="text" name="ticket_paid_email_subject" value="{{ $email->subject }}"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Isi Email (HTML)</label>
                        <textarea name="ticket_paid_email_body" rows="12" required
                            class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] font-mono leading-relaxed">{{ $email->body }}</textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Mendukung tag HTML (contoh <code class="font-bold">&lt;p&gt;</code>, <code class="font-bold">&lt;strong&gt;</code>, <code class="font-bold">&lt;ul&gt;</code>). Tulis {qr} untuk posisi gambar QR.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                            <input type="checkbox" name="ticket_paid_email_include_qr" value="1" {{ $email->include_qr ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                            Sertakan QR tiket di email
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-8 py-3 bg-[#E19404] hover:bg-orange-600 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md transition">
                            Simpan Template Email
                        </button>
                    </div>
                </div>
            </form>
        @endif

        <!-- ============================================================ -->
        <!-- TEMPLATE NOTIFIKASI HOTEL LUNAS -->
        <!-- ============================================================ -->
        <div class="border-t-4 border-[#E19404]/20 pt-10">
            <div class="mb-4">
                <h2 class="text-xl font-black text-gray-900">Template Notifikasi Hotel Lunas</h2>
                <p class="text-sm text-gray-500 mt-1">Ubah isi pesan notifikasi yang dikirim otomatis ke peserta ketika pembayaran reservasi hotel berhasil (via WhatsApp & Email).</p>
            </div>

            <!-- Daftar Placeholder Hotel -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
                <p class="text-xs font-extrabold text-[#E19404] uppercase tracking-wider mb-2">Placeholder yang didukung:</p>
                <div class="flex flex-wrap gap-2">
                    @foreach(['{nama}', '{hotel}', '{kode_booking}', '{id_pesanan}', '{invoice}', '{check_in}', '{check_out}', '{malam}', '{harga}'] as $ph)
                        <code class="px-2 py-1 bg-[#FFF8E7] border border-[#E19404]/30 rounded-lg text-xs font-bold text-gray-800">{{ $ph }}</code>
                    @endforeach
                </div>
            </div>

            <!-- Form WhatsApp Hotel -->
            @php($waHotel = $templates->firstWhere('key', 'hotel_paid_wa'))
            @if($waHotel)
                <form action="{{ route('admin.notifications.update') }}" method="POST" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
                    @csrf
                    <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-900">{{ $waHotel->label }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Dikirim via Fonnte ke WhatsApp pemesan hotel.</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                            <input type="checkbox" name="hotel_paid_wa_is_active" value="1" {{ $waHotel->is_active ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                            Aktif
                        </label>
                    </div>

                    <div class="p-6 sm:p-8 space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Isi Pesan WhatsApp</label>
                            <textarea name="hotel_paid_wa_body" rows="10" required
                                class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] font-mono leading-relaxed">{{ $waHotel->body }}</textarea>
                            <p class="text-[11px] text-gray-400 mt-1">Formatting WA: <code class="font-bold">*teks tebal*</code>, <code class="font-bold italic">_teks miring_</code>.</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-8 py-3 bg-[#E19404] hover:bg-orange-600 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md transition">
                                Simpan Template WA Hotel
                            </button>
                        </div>
                    </div>
                </form>
            @endif

            <!-- Form Email Hotel -->
            @php($emailHotel = $templates->firstWhere('key', 'hotel_paid_email'))
            @if($emailHotel)
                <form action="{{ route('admin.notifications.update') }}" method="POST" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    @csrf
                    <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-900">{{ $emailHotel->label }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Dikirim ke email pemesan hotel.</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                            <input type="checkbox" name="hotel_paid_email_is_active" value="1" {{ $emailHotel->is_active ? 'checked' : '' }} class="w-4 h-4 text-[#E19404] focus:ring-[#FBE39D]">
                            Aktif
                        </label>
                    </div>

                    <div class="p-6 sm:p-8 space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Subject Email</label>
                            <input type="text" name="hotel_paid_email_subject" value="{{ $emailHotel->subject }}"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Isi Email (HTML)</label>
                            <textarea name="hotel_paid_email_body" rows="12" required
                                class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] font-mono leading-relaxed">{{ $emailHotel->body }}</textarea>
                            <p class="text-[11px] text-gray-400 mt-1">Mendukung tag HTML (contoh <code class="font-bold">&lt;p&gt;</code>, <code class="font-bold">&lt;strong&gt;</code>, <code class="font-bold">&lt;ul&gt;</code>).</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-8 py-3 bg-[#E19404] hover:bg-orange-600 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md transition">
                                Simpan Template Email Hotel
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>

    </main>

        <!-- FOOTER -->
    @include('partials.admin-footer')


</body>
</html>
