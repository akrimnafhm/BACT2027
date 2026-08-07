<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFFFFF] flex items-center justify-center min-h-screen relative">

    <a href="{{ route('login') }}" class="absolute top-6 left-6 text-gray-500 hover:text-[#E19404] transition font-medium text-sm flex items-center">&larr; Kembali ke Login</a>

    <div class="w-full max-w-md p-8 border border-gray-100 rounded-2xl shadow-xl bg-white">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-14 mx-auto mb-4">
            <h2 class="text-xl font-bold text-gray-800">Reset Password</h2>
            <p class="text-sm text-gray-500 mt-1">Masukkan email Anda untuk menerima kode reset.</p>
        </div>

        <!-- Notifikasi -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm text-center font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm text-center font-medium">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Indikator Step -->
        <div class="flex items-center justify-center gap-2 mb-8">
            @php($step = session('reset_verified') ? 3 : (session('reset_email') ? 2 : 1))
            @foreach([1 => 'Email', 2 => 'Kode', 3 => 'Password'] as $num => $label)
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $step >= $num ? 'bg-[#E19404] text-white' : 'bg-gray-200 text-gray-500' }}">
                        {{ $num }}
                    </div>
                    <span class="text-xs font-semibold {{ $step >= $num ? 'text-gray-900' : 'text-gray-400' }} hidden sm:inline">{{ $label }}</span>
                    @if(!$loop->last)
                        <div class="w-6 h-0.5 {{ $step > $num ? 'bg-[#E19404]' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- ==========================================
             STEP 1: INPUT EMAIL
             ========================================== -->
        @if($step === 1)
            <form action="{{ route('forgot-password.send') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email Terdaftar</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="contoh@email.com"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
                </div>

                <button type="submit" class="w-full bg-[#FFC32D] hover:bg-[#E19404] text-[#FFFFFF] font-bold py-3 px-4 rounded-full transition shadow-md">
                    Kirim Kode
                </button>
            </form>
        @endif

        <!-- ==========================================
             STEP 2: INPUT KODE 6 DIGIT
             ========================================== -->
        @if($step === 2)
            <p class="text-xs text-gray-500 text-center mb-5">
                Kode reset telah dikirim ke <strong class="text-gray-800">{{ session('reset_email') }}</strong>.<br>
                Kode berlaku selama <strong>10 menit</strong>.
            </p>

            <form action="{{ route('forgot-password.verify') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kode 6 Digit</label>
                    <input type="text" name="code" value="{{ old('code') }}" required maxlength="6" inputmode="numeric" placeholder="123456"
                           class="placeholder:text-gray-200 placeholder:font-medium w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition text-center text-lg tracking-[0.4em] font-bold">
                </div>

                <button type="submit" class="w-full bg-[#FFC32D] hover:bg-[#E19404] text-[#FFFFFF] font-bold py-3 px-4 rounded-full transition shadow-md">
                    Verifikasi
                </button>
            </form>

            <!-- Kirim Ulang Kode -->
            <form action="{{ route('forgot-password.send') }}" method="POST" class="mt-4 text-center m-0">
                @csrf
                <input type="hidden" name="email" value="{{ session('reset_email') }}">
                <button type="submit" class="text-sm text-[#E19404] font-bold hover:underline">Kirim Ulang Kode</button>
            </form>
        @endif

        <!-- ==========================================
             STEP 3: PASSWORD BARU
             ========================================== -->
        @if($step === 3)
            <form action="{{ route('forgot-password.reset') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password Baru</label>
                    <input type="password" name="password" required placeholder="Minimal 8 karakter"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" required placeholder="Ulangi password baru"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
                </div>

                <button type="submit" class="w-full bg-[#FFC32D] hover:bg-[#E19404] text-[#FFFFFF] font-bold py-3 px-4 rounded-full transition shadow-md">
                    Simpan Password Baru
                </button>
            </form>
        @endif
    </div>
</body>
</html>
