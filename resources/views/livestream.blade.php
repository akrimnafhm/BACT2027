<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Streaming BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="antialiased font-sans bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    @include('partials.navbar', [
        'navbarBg' => 'bg-[#FFFFFF] shadow-sm',
        'navbarLogoHref' => '/',
        'navbarShowGuestLogin' => false,
    ])

    <main class="flex-grow w-full pt-28 pb-16 px-4">
        <div class="max-w-6xl mx-auto w-full">

            <!-- Header Live Streaming -->
            <div class="text-center mb-8">
                <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">LIVE STREAMING</span>
                <h1 class="text-3xl md:text-4xl font-extrabold text-[#234661] mt-2">Live Streaming BACT 2027</h1>
                <p class="text-sm text-gray-500 mt-3 max-w-2xl mx-auto">
                    Tonton simposium secara real-time. Halaman ini hanya dapat diakses oleh peserta tiket <strong>Online</strong> yang telah <strong>Lunas</strong>.
                </p>
            </div>

            <!-- Video Player -->
            <div class="bg-black rounded-2xl overflow-hidden shadow-2xl aspect-video max-w-5xl mx-auto mb-8">
                <iframe
                    src="{{ $livestreamEmbedUrl }}"
                    title="BACT 2027 Live Streaming"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    class="w-full h-full"
                    referrerpolicy="strict-origin-when-cross-origin"
                    loading="lazy">
                </iframe>
            </div>

            <!-- Fallback Link (visible if iframe blocked) -->
            <div class="text-center mb-6">
                <a href="https://www.youtube.com/watch?v={{ $livestreamVideoId }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 bg-[#E19404] hover:bg-orange-600 text-white text-sm font-bold py-3 px-6 rounded-xl transition shadow-md">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 7.685 0 12 0 12s0 4.315.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 16.315 24 12 24 12s0-4.315-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"></path></svg>
                    <span>Tonton di YouTube (jika video di atas tidak tampil)</span>
                </a>
                <!-- <p class="text-xs text-gray-500 mt-2">Beberapa live stream YouTube tidak mengizinkan embed. Gunakan tombol di atas untuk menonton langsung di YouTube.</p> -->
            </div>

            <!-- Info Akses -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm max-w-5xl mx-auto">
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#E19404]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Info Akses
                    </h4>
                    <ul class="flex flex-col md:flex-row md:items-start md:justify-start gap-2 md:gap-8 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg>
                            Khusus tiket Online dengan status lunas
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg>
                            Live chat hanya tersedia di YouTube
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg>
                            Mendukung tampilan fullscreen
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </main>

    @include('partials.footer')

</body>

</html>