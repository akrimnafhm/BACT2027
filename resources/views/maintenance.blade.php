<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedang Maintenance - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F5F7] min-h-screen flex items-center justify-center font-sans px-4">

    <div class="w-full max-w-md mx-auto text-center">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-10">
            <img src="{{ asset('images/logo.png') }}" alt="BACT 2027" class="h-14 mx-auto mb-8 object-contain">

            <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-black text-[#234661] mb-2">Sedang Maintenance</h1>
            <p class="text-sm text-gray-500 leading-relaxed">
                Website sedang dalam pemeliharaan sementara.
                Silakan kembali lagi nanti. Terima kasih atas pengertian Anda.
            </p>
        </div>

        <p class="text-xs text-gray-400 mt-6">BACT 2027 &middot; Simposium Bersama</p>
    </div>

</body>
</html>