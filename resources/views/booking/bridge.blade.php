<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Tiket - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F5F7] min-h-screen flex flex-col font-sans pt-28">

    @include('partials.navbar')

    <div class="text-center max-w-3xl mx-auto space-y-3 pt-4">
        <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">Pemesanan Tiket</span>
        <h1 class="text-3xl md:text-4xl font-black text-[#234661]">Pemesanan Tiket Acara</h1>
        <p class="text-sm md:text-base text-gray-600 leading-relaxed">
           Pesan tiket acara sekarang agar terdaftar di acara yang dipilih.
        </p>
    </div>

    <div class="max-w-4xl mx-auto px-4 pt-10 pb-16 grow w-full">
        @if(session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if($status === 'post_purchase')
            <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 bg-green-500 text-white text-xs font-bold px-4 py-2 rounded-bl-xl">Tiket Dimiliki</div>
                <h1 class="text-2xl font-extrabold text-[#234661] mb-2">Pesanan Tiket Anda</h1>
                <p class="text-sm text-gray-500 mb-6">Halaman ini menampilkan QR pesanan tiket Anda. Simpan halaman ini untuk registrasi.</p>

                <div class="flex flex-col md:flex-row gap-6 items-center bg-gray-50 p-6 rounded-xl border border-dashed border-gray-300">
                    <div class="w-48 h-48 bg-white p-2 border border-gray-200 rounded-lg flex items-center justify-center shadow-sm">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $booking->checkin_token }}" alt="QR Code" class="w-full">
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="font-extrabold text-[#E19404] text-xl mb-1">{{ $bookedTicket ? str_replace([' - ', ' -', '- '], ': ', $bookedTicket->display_name) : 'Tiket Anda' }}</h2>
                        <p class="font-bold text-gray-800 text-lg">{{ $booking->name_with_title ?: $user->name }}</p>
                        <p class="text-gray-500 text-sm mb-2">{{ $booking->institution_name }} ({{ $booking->institution_city }})</p>
                        <span class="inline-block bg-green-100 text-green-800 px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide">
                            Status: {{ $booking->status }}
                        </span>
                        <div class="mt-3">
                            <span class="text-xs text-gray-500 block mb-1">Kode Tiket (untuk check-in manual jika QR gagal dipindai):</span>
                            <span class="inline-block bg-white border-2 border-dashed border-[#E19404] text-[#E19404] font-extrabold tracking-widest px-4 py-2 rounded-lg text-sm select-all">{{ $booking->checkin_token }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($status === 'incomplete')
            <div class="rounded-2xl">
                <div class="max-w-3xl mx-auto bg-yellow-50 rounded-2xl shadow-sm p-8 border border-yellow-200 text-center">
                    <h3 class="text-lg font-bold text-yellow-800 mb-2">Profil Belum Lengkap</h3>
                    <p class="text-yellow-700 mb-6 text-sm">Mohon lengkapi NIK dan verifikasi kontak Anda terlebih dahulu pada halaman profil sebelum memesan tiket acara.</p>
                    <a href="{{ route('profile.edit') }}" class="inline-block bg-[#FFC32D] hover:bg-[#E19404] text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                        Lengkapi Profil Sekarang
                    </a>
                </div>
            </div>
        @elseif($status === 'pending')
            <div class="rounded-2xl">
                <div class="max-w-3xl mx-auto bg-yellow-50 rounded-2xl shadow-sm p-8 border border-yellow-200 text-center">
                    <h3 class="text-lg font-bold text-yellow-800 mb-2">Pembayaran Belum Selesai</h3>
                    <p class="text-yellow-700 mb-6 text-sm">Mohon selesaikan pembayaran Anda untuk mendapatkan QR tiket acara.</p>
                    <a href="{{ route('checkout', $existingBooking->id) }}" class="inline-flex justify-center items-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-xl transition shadow-md">
                        Lanjutkan Pembayaran
                    </a>
                </div>
            </div>
        @elseif($status === 'bridge')
            <script>
                window.location.href = "{{ route('booking.form') }}";
            </script>
        @else
            <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200 text-center">
                <h1 class="text-2xl font-extrabold text-gray-900 mb-3">Pesan Tiket</h1>
                <p class="text-sm text-gray-500 mb-6">Silakan login untuk melanjutkan pemesanan tiket dan menampilkan QR pesanan Anda di sini.</p>
                <a href="{{ route('login') }}" class="inline-block bg-[#E19404] hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-xl transition shadow-md">
                    Login untuk Melanjutkan
                </a>
            </div>
        @endif
    </div>

    @include('partials.footer')

</body>
</html>
