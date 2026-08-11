<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Terlalu Besar - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F5F7] min-h-screen flex items-center justify-center px-6 font-sans">
    <div class="max-w-md w-full bg-white rounded-2xl border border-gray-200 shadow-sm p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-red-100 flex items-center justify-center mb-5">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h1 class="text-xl font-black text-gray-900 mb-2">File Terlalu Besar</h1>
        <p class="text-sm text-gray-500 mb-6">
            Ukuran data yang diunggah melebihi batas maksimal yang diizinkan server ({{ $max }}).
            Silakan perkecil file atau kompres gambarnya sebelum mengunggah ulang.
        </p>
        <div class="flex gap-3 justify-center">
            <button onclick="window.history.back()"
                class="px-5 py-2.5 rounded-xl bg-[#FBE39D] text-[#E19404] text-sm font-extrabold hover:bg-orange-100 transition">
                Kembali
            </button>
            <a href="{{ url('/admin') }}"
                class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 text-sm font-bold hover:bg-gray-50 transition">
                Ke Panel Admin
            </a>
        </div>
    </div>
</body>
</html>
