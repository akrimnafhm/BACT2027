<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BACT 2027</title>
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

<body class="antialiased font-sans bg-[#FFFFFF] text-gray-800">

    <!-- =========================================================
         1. NAVBAR (Fixed & Konsisten)
         ========================================================= -->
    @include('partials.navbar', [
        'navbarBg' => 'bg-[#FFFFFF]/95 backdrop-blur-md shadow-sm',
        'navbarLogoHref' => '/#beranda',
    ])

    <!-- =========================================================
     2. HERO SECTION (#beranda) + SLIDER TEKS & GAMBAR INTERAKTIF
     ========================================================= -->
    <header id="beranda"
        class="relative min-h-screen flex flex-col justify-center items-center text-center pt-28 pb-16 px-4 overflow-hidden bg-[#234661]"
        x-data="{
            active: 0,
            timer: null,
            slides: [
                {
                    image: '{{ asset('images/home-1.png') }}',
                    text: 'Where Science Meets Heritage, and Knowledge Becomes a Lasting Journey. Join us in the heart of Yogyakarta for the Basic Advance Course on Transfusion 2027—a place where scientific excellence, meaningful collaboration, and the timeless warmth of Javanese hospitality come together to inspire the future of transfusion medicine.'
                },
                {
                    image: '{{ asset('images/home-2.png') }}',
                    text: 'Every Journey Begins with a Single Drop. Every Expert Begins with a Shared Passion. Experience an unforgettable learning journey at the Basic Advance Course on Transfusion 2027, surrounded by the cultural charm of Yogyakarta, where knowledge flows, friendships grow, and every encounter leaves a lasting impression.'
                },
                {
                    image: '{{ asset('images/home-3.png') }}',
                    text: 'Like the Lifeblood That Connects Us, Knowledge Is Meant to Be Shared. In the enchanting city of Yogyakarta, the Basic Advance Course on Transfusion 2027 invites you to discover new insights, cultivate meaningful collaborations, and create unforgettable moments where science, culture, and humanity meet.'
                }
            ],
            startTimer() {
                this.timer = setInterval(() => { 
                    this.active = (this.active + 1) % this.slides.length; 
                }, 6000);
            },
            resetTimer() {
                clearInterval(this.timer);
                this.startTimer();
            }
        }" x-init="startTimer()">

        <!-- 1. BACKGROUND SLIDER GAMBAR -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <template x-for="(slide, index) in slides" :key="index">
                <div x-show="active === index" x-transition:enter="transition transform ease-out duration-1000"
                    x-transition:enter-start="opacity-0 translate-x-full"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition transform ease-in duration-1000"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-full" class="absolute inset-0 bg-cover bg-center"
                    :style="`background-image: url('${slide.image}')`">
                </div>
            </template>
            <!-- Overlay navy agar teks kontras dan terbaca jelas -->
            <div class="absolute inset-0 bg-white/50"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-white/40 via-white/20 to-white/95"></div>
        </div>

        <!-- 2. KONTEN HERO (TEKS & COUNTDOWN) -->
        <div class="relative z-20 max-w-4xl mx-auto flex flex-col items-center">

            <h1 class="text-3xl md:text-5xl font-extrabold mb-6 leading-tight text-[#E19404]">
                17th Basic Advanced Course in Transfusion
            </h1>

            <!-- Wadah Teks Slide (1 Paragraf per Slide) yang Bergeser Dinamis -->
            <div
                class="relative min-h-[140px] sm:min-h-[110px] md:min-h-[90px] w-full flex items-center justify-center overflow-hidden mb-4 px-2">
                <template x-for="(slide, index) in slides" :key="index">
                    <div x-show="active === index"
                        x-transition:enter="transition transform ease-out duration-700 delay-100"
                        x-transition:enter-start="opacity-0 translate-x-12"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition transform ease-in duration-500 absolute"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-12" class="w-full">

                        <!-- 1 Paragraf Utuh -->
                        <p class="text-sm sm:text-base md:text-lg font-regular text-gray-800 leading-relaxed max-w-3xl mx-auto"
                            x-text="slide.text">
                        </p>
                    </div>
                </template>
            </div>

            <!-- Titik-Titik Navigasi Manual (Pagination Dots) -->
            <div class="flex items-center justify-center space-x-2.5 mb-10 z-30">
                <template x-for="(slide, index) in slides" :key="index">
                    <button type="button" @click="active = index; resetTimer();"
                        class="h-2.5 rounded-full transition-all duration-500 focus:outline-none cursor-pointer"
                        :class="active === index ? 'w-8 bg-[#E19404]' : 'w-2.5 bg-white/40 hover:bg-white/60'"
                        :aria-label="`Go to slide ${index + 1}`">
                    </button>
                </template>
            </div>

            <!-- 3. COUNTDOWN TIMER BULAT (TIDAK DIUBAH SAMA SEKALI) -->
            <div class="flex justify-center flex-wrap gap-3 md:gap-8" x-data="countdownTimer()" x-init="start()">
                <div
                    class="flex flex-col items-center justify-center w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 rounded-full border-4 border-[#E19404] bg-[#FBE39D] text-[#E19404] shadow-lg">
                    <span x-text="days" class="text-2xl sm:text-3xl md:text-4xl font-bold">00</span>
                    <span class="text-[10px] sm:text-xs md:text-sm font-bold uppercase tracking-widest mt-1">Days</span>
                </div>

                <div
                    class="flex flex-col items-center justify-center w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 rounded-full border-4 border-[#E19404] bg-[#FBE39D] text-[#E19404] shadow-lg">
                    <span x-text="hours" class="text-2xl sm:text-3xl md:text-4xl font-bold">00</span>
                    <span class="text-[10px] sm:text-xs md:text-sm font-bold uppercase tracking-widest mt-1">Hours</span>
                </div>

                <div
                    class="flex flex-col items-center justify-center w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 rounded-full border-4 border-[#E19404] bg-[#FBE39D] text-[#E19404] shadow-lg">
                    <span x-text="minutes" class="text-2xl sm:text-3xl md:text-4xl font-bold">00</span>
                    <span class="text-[10px] sm:text-xs md:text-sm font-bold uppercase tracking-widest mt-1">Mins</span>
                </div>

                <div
                    class="flex flex-col items-center justify-center w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 rounded-full border-4 border-[#E19404] bg-[#FBE39D] text-[#E19404] shadow-lg">
                    <span x-text="seconds" class="text-2xl sm:text-3xl md:text-4xl font-bold">00</span>
                    <span class="text-[10px] sm:text-xs md:text-sm font-bold uppercase tracking-widest mt-1">Secs</span>
                </div>
            </div>

        </div>
    </header>

    <!-- =========================================================
         3. INFO TERKINI / ANNOUNCEMENTS (Slider Rapi + Tombol Panah < >)
         ========================================================= -->
    @if(!empty($announcements) && count($announcements) > 0)
        <section class="max-w-7xl mx-auto px-6 py-12 relative z-20" x-data="{
                         scrollPrev() {
                             // Menggeser ke kiri seukuran lebar container
                             $refs.slider.scrollBy({ left: -$refs.slider.clientWidth, behavior: 'smooth' });
                         },
                         scrollNext() {
                             // Menggeser ke kanan seukuran lebar container
                             $refs.slider.scrollBy({ left: $refs.slider.clientWidth, behavior: 'smooth' });
                         }
                     }">

            <!-- Header Section & Tombol Panah Navigasi (< >) -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span class="text-xs font-semibold text-[#E19404] uppercase tracking-widest">Pengumuman</span>
                    <h2 class="text-xl md:text-2xl font-extrabold text-[#234661]">Info & Update Terkini</h2>
                </div>

                <!-- Tombol Geser Kiri (<) dan Kanan (>) -->
                <div class="flex items-center gap-2">
                    <button type="button" @click="scrollPrev()"
                        class="w-10 h-10 rounded-full bg-white border border-gray-200 shadow-sm hover:border-[#E19404] hover:bg-[#FFF8E7] text-gray-700 hover:text-[#E19404] flex items-center justify-center transition duration-300 focus:outline-none cursor-pointer"
                        aria-label="Geser Kiri">
                        <svg class="w-5 h-5 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                    </button>

                    <button type="button" @click="scrollNext()"
                        class="w-10 h-10 rounded-full bg-white border border-gray-200 shadow-sm hover:border-[#E19404] hover:bg-[#FFF8E7] text-gray-700 hover:text-[#E19404] flex items-center justify-center transition duration-300 focus:outline-none cursor-pointer"
                        aria-label="Geser Kanan">
                        <svg class="w-5 h-5 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Wadah Slider Kartu Info -->
            <div x-ref="slider"
                class="flex gap-6 overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory pb-4 pt-1">
                @foreach($announcements as $info)
                    <!-- 
                                RUMUS LEBAR AGAR TIDAK KEPOTONG:
                                - HP (w-full): 1 kartu penuh per layar
                                - Tablet (md:w-[calc(50%-12px)]): tepat 2 kartu per layar (12px = setengah dari gap-6)
                                - PC/Laptop (lg:w-[calc(33.333%-16px)]): tepat 3 kartu per layar
                            -->
                    <div
                        class="w-full md:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] shrink-0 snap-start bg-white rounded-xl p-6 sm:p-7 border border-gray-200/80 shadow-sm flex flex-col justify-between gap-4 hover:border-[#E19404] transition">
                        <div class="space-y-2">
                            <div
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#FBE39D] text-[#E19404] text-[11px] font-black rounded-lg uppercase tracking-wider">
                                <span>{{ $info->badge ?? 'INFO' }}</span>
                            </div>
                            <h3 class="text-lg font-black text-gray-900 leading-snug">{{ $info->title }}</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ $info->content }}</p>
                        </div>
                        <div
                            class="pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400 font-semibold">
                            <span>BACT 2027 Official</span>
                            <span>{{ $info->created_at ? $info->created_at->format('d M Y') : '' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

        </section>
    @endif

    <!-- =========================================================
         4. SEKSI PEMBICARA (#pembicara) - Kompak, Rapi, & Tombol < >
         ========================================================= -->
    <section id="pembicara" class="py-20 max-w-7xl mx-auto px-6" x-data="{
                 autoScrollTimer: null,
                 scrollPrev() {
                     $refs.speakerSlider.scrollBy({ left: -$refs.speakerSlider.clientWidth, behavior: 'smooth' });
                     this.resetAutoScroll();
                 },
                 scrollNext() {
                     const el = $refs.speakerSlider;
                     const maxScroll = el.scrollWidth - el.clientWidth;
                     if (el.scrollLeft >= maxScroll - 1) {
                         el.scrollTo({ left: 0, behavior: 'smooth' });
                     } else {
                         el.scrollBy({ left: el.clientWidth, behavior: 'smooth' });
                     }
                     this.resetAutoScroll();
                 },
                 autoScroll() {
                     const el = $refs.speakerSlider;
                     const maxScroll = el.scrollWidth - el.clientWidth;
                     if (maxScroll <= 1) return;
                     if (el.scrollLeft >= maxScroll - 1) {
                         el.scrollTo({ left: 0, behavior: 'smooth' });
                     } else {
                         el.scrollBy({ left: el.clientWidth, behavior: 'smooth' });
                     }
                 },
                 startAutoScroll() {
                     this.autoScrollTimer = setInterval(() => { this.autoScroll(); }, 3000);
                 },
                 resetAutoScroll() {
                     clearInterval(this.autoScrollTimer);
                     this.startAutoScroll();
                 }
             }" x-init="startAutoScroll()" @mouseenter="clearInterval(autoScrollTimer)" @mouseleave="resetAutoScroll()">

        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
            <div>
                <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">Narasumber Ahli</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-[#234661] mt-2">Pembicara BACT 2027</h2>
                <p class="text-sm text-gray-500 mt-2">
                    Daftar dokter spesialis dan instruktur terkemuka yang akan mengisi simposium.
                </p>
            </div>

            <!-- Tombol Geser < dan > -->
            <div class="flex items-center gap-2">
                <button type="button" @click="scrollPrev()"
                    class="w-10 h-10 rounded-full bg-white border border-gray-200 shadow-sm hover:border-[#E19404] hover:bg-[#FFF8E7] text-gray-700 hover:text-[#E19404] flex items-center justify-center transition duration-300 focus:outline-none cursor-pointer"
                    aria-label="Geser Kiri">
                    <svg class="w-5 h-5 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7">
                        </path>
                    </svg>
                </button>

                <button type="button" @click="scrollNext()"
                    class="w-10 h-10 rounded-full bg-white border border-gray-200 shadow-sm hover:border-[#E19404] hover:bg-[#FFF8E7] text-gray-700 hover:text-[#E19404] flex items-center justify-center transition duration-300 focus:outline-none cursor-pointer"
                    aria-label="Geser Kanan">
                    <svg class="w-5 h-5 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Container Slider Pembicara -->
        <div x-ref="speakerSlider"
            class="flex gap-5 overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory pb-4 pt-1">
            @foreach($speakers as $speaker)
                <!-- 
                        RUMUS LEBAR AGAR TIDAK KEPOTONG:
                        - HP (w-[calc(50%-10px)]): pas 2 kartu per layar
                        - Tablet (md:w-[calc(33.333%-14px)]): pas 3 kartu per layar
                        - PC/Laptop (lg:w-[calc(20%-16px)]): pas 5 kartu per layar
                    -->
                <div
                    class="w-[calc(50%-10px)] md:w-[calc(33.333%-14px)] lg:w-[calc(20%-16px)] shrink-0 snap-start bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden group hover:border-[#E19404] transition duration-300 flex flex-col justify-between">

                    <!-- Foto Pembicara (h-52 agar proporsional dengan kartu kompak) -->
                    <div class="h-52 overflow-hidden flex items-center justify-center relative bg-white">
                        @if($speaker->image)
                            <img src="{{ asset('storage/' . $speaker->image) }}" alt="{{ $speaker->name }}"
                                class="w-32 h-32 sm:w-40 sm:h-40 rounded-full border border-gray-200 object-cover object-top group-hover:scale-105 transition duration-500">
                        @else
                            <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-full flex flex-col items-center justify-center gap-2 text-gray-300">
                                <svg class="w-16 h-16 sm:w-20 sm:h-20" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Nama & Instansi -->
                    <div class="p-5 text-center flex flex-col justify-center flex-grow">
                        <h3 class="text-sm font-semibold text-gray-900 leading-snug group-hover:text-[#E19404] transition">
                            {{ $speaker->name }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-4 font-medium line-clamp-2">
                            {{ $speaker->institution }}
                        </p>
                    </div>

                </div>
            @endforeach

            <!-- Placeholder: lengkapi hingga 5 kartu jika pembicara belum penuh -->
            @for($i = $speakers->count(); $i < 5; $i++)
                <div
                    class="w-[calc(50%-10px)] md:w-[calc(33.333%-14px)] lg:w-[calc(20%-16px)] shrink-0 bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                    <div class="h-52 flex items-center justify-center bg-white">
                        <div class="w-40 h-40 rounded-full border border-gray-200 bg-gray-100 flex items-center justify-center text-gray-300">
                            <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="p-5 text-center">
                        <h3 class="text-base font-black text-gray-900">Pembicara</h3>
                        <p class="text-xs text-gray-500 mt-1.5 font-semibold">Instansi</p>
                    </div>
                </div>
            @endfor
        </div>
    </section>

    <!-- =========================================================
     5. SEKSI JADWAL SIMPOSIUM (#jadwal) - Interactive Day Tabs
     ========================================================= -->
    @php
        // Tambahkan ->values() agar indeks array di-reset dari 0 setelah di-sorting
        $groupedSchedules = $schedules->groupBy('day')->sortKeys()->map(function ($dayItems) {
            return $dayItems->sortBy('start_time')->values();
        });

        // Perbaikan di sini (hapus ->values()->first() yang keliru di ujung)
        $firstDay = $groupedSchedules->keys()->first() ?? 1;
    @endphp

    <section id="jadwal" class="py-20 bg-gray-50/60 border-y border-gray-100" x-data="{ activeDay: {{ $firstDay }} }">
        <div class="max-w-5xl mx-auto px-6">

            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">Rangkaian Acara</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-[#234661] mt-1">Jadwal & Agenda Kegiatan</h2>
                <p class="text-sm text-gray-500 mt-2">Pilih hari pelaksanaan untuk melihat urutan sesi dan pemateri
                    secara lengkap.</p>
            </div>

            @if($scheduleVisible && $groupedSchedules->isNotEmpty())
                <!-- TOMBOL NAVIGASI SLIDE/TAB HARI (Day 1, Day 2, dst.) -->
                <div class="flex items-center justify-center gap-2 overflow-x-auto no-scrollbar mb-10 pb-2">
                    @foreach($groupedSchedules->keys() as $dayNumber)
                        <button type="button" @click="activeDay = {{ $dayNumber }}"
                            :class="activeDay === {{ $dayNumber }} ? 'bg-[#E19404] text-white shadow-md scale-105' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-6 py-3 rounded-lg font-black text-sm transition-all duration-300 cursor-pointer whitespace-nowrap flex items-center gap-2">
                            <span>Day {{ $dayNumber }}</span>
                        </button>
                    @endforeach
                </div>

                <!-- KONTEN DAFTAR ACARA PER HARI -->
                <div>
                    @foreach($groupedSchedules as $dayNumber => $daySchedules)
                        <div x-show="activeDay === {{ $dayNumber }}" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-3"
                            x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">

                            @foreach($daySchedules as $index => $item)
                                <div
                                    class="bg-white rounded-xl p-6 border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm hover:border-[#E19404] transition">
                                    <div class="flex items-start gap-4">
                                        <!-- Nomor Urut Sesi di Hari Tersebut -->
                                        <div
                                            class="w-10 h-10 rounded-xl bg-[#FBE39D] text-[#E19404] font-black flex items-center justify-center flex-shrink-0 text-sm">
                                            {{ $index + 1 }}
                                        </div>
                                        <div>
                                            <span class="text-[11px] font-extrabold uppercase text-[#E19404]">
                                                Day {{ $item->day }}
                                            </span>
                                            <h3 class="text-base font-black text-gray-900 mt-0.5">{{ $item->title }}</h3>
                                            @if($item->speaker)
                                                <p class="text-xs text-gray-500 font-semibold mt-1">
                                                    Pembicara: <span class="text-gray-800">{{ $item->speaker }}</span>
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Waktu Mulai - Selesai -->
                                    <div class="sm:text-right flex-shrink-0">
                                        <span
                                            class="inline-block px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-extrabold text-gray-800">
                                            {{ $item->start_time }} - {{ $item->end_time }} WIB
                                        </span>
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    @endforeach
                </div>

            @else
                <!-- Fallback jika seksi jadwal disembunyikan atau belum ada data -->
                <div
                    class="text-center py-12 bg-white rounded-3xl border border-gray-200 text-gray-400 text-sm font-medium">
                    {{ $scheduleVisible
                        ? 'Jadwal kegiatan simposium belum diterbitkan.'
                        : 'Jadwal kegiatan masih dalam penyusunan — akan segera diumumkan.' }}
                </div>
            @endif

        </div>
    </section>

    <!-- =========================================================
         6. SEKSI LOKASI VENUE (#lokasi)
         ========================================================= -->
    <section id="lokasi" class="py-20 max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <div>
                    <span class="text-xs font-extrabold text-[#E19404] uppercase tracking-widest">Tempat
                        Pelaksanaan</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-[#234661] mt-1">Lokasi</h2>
                </div>
                <p class="text-sm text-gray-600 leading-relaxed">
                    BACT 2027 diselenggarakan di venue berfasilitas standar internasional yang terletak di jantung kota
                    Yogyakarta dan sangat mudah dijangkau.
                </p>
                <div class="space-y-4 pt-2">
                    <div class="flex items-start gap-3.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-[#FBE39D]/40 text-[#E19404] flex items-center justify-center flex-shrink-0 font-bold">
                            📍</div>
                        <div>
                            <h4 class="text-sm font-black text-gray-900">Grand Hotel De Djokja, Yogyakarta</h4>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                Jalan Malioboro Nomor 60, Kelurahan Suryatmajan, Kecamatan Danurejan, Kota Yogyakarta,
                                D.I. Yogyakarta 55213
                            </p>
                        </div>
                    </div>
                </div>
                <div class="pt-2">
                    <a href="/hotel"
                        class="px-6 py-3 bg-[#FFC32D] hover:bg-[#E19404] text-[#FFFFFF] font-extrabold text-xs rounded-full shadow-md transition">
                        Pesan Hotel
                    </a>
                </div>
            </div>

            <!-- Embed Google Maps sesuai link presisi terbaru -->
            <div class="h-80 sm:h-96 rounded-xl overflow-hidden border border-gray-200 shadow-md bg-gray-100">
                <iframe
                    src="https://maps.google.com/maps?q=Grand+Hotel+De+Djokja+Yogyakarta&t=&z=17&ie=UTF8&iwloc=&output=embed"
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

    <!-- =========================================================
         7. SEKSI GALERI FOTO (#galeri) 
         ========================================================= -->
    <section id="galeri" class="py-20 bg-gradient-to-b from-[#234661] via-[#1d394d] to-[#142531] text-white">
        <div class="max-w-4xl mx-auto px-6"> <!-- max-w diperkecil agar gambar tidak terlalu raksasa -->
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="text-xs font-extrabold text-[#FBE39D] uppercase tracking-widest">Arsip Acara</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold mt-1">Galeri BACT Sebelumnya</h2>
                <p class="text-sm text-gray-400 mt-2">Momen antusiasme peserta medis pada perhelatan BACT tahun-tahun sebelumnya.</p>
            </div>

            <!-- x-data untuk mengontrol Slideshow -->
            <div class="relative w-full rounded-2xl overflow-hidden bg-gray-800 shadow-xl group aspect-video sm:aspect-[16/9]"
                x-data="{
                    active: 0,
                    timer: null,
                    images: [
                        @forelse($galleries as $gal)
                            '{{ asset('storage/' . $gal->image) }}',
                        @empty
                            // Jika kosong, masukkan gambar dummy
                            'https://placehold.co/1200x800/374151/white?text=Galeri+1',
                            'https://placehold.co/1200x800/1f2937/white?text=Galeri+2',
                            'https://placehold.co/1200x800/111827/white?text=Galeri+3'
                        @endforelse
                    ],
                    startTimer() {
                        if(this.images.length > 1) {
                            this.timer = setInterval(() => { 
                                this.next(); 
                            }, 5000); // Ganti gambar tiap 5 detik
                        }
                    },
                    resetTimer() {
                        clearInterval(this.timer);
                        this.startTimer();
                    },
                    next() {
                        this.active = (this.active === this.images.length - 1) ? 0 : this.active + 1;
                    },
                    prev() {
                        this.active = (this.active === 0) ? this.images.length - 1 : this.active - 1;
                    }
                }" x-init="startTimer()">
                
                <!-- Tampilan Gambar -->
                <div class="relative w-full h-full">
                    <template x-for="(img, index) in images" :key="index">
                        <div x-show="active === index"
                             x-transition:enter="transition opacity duration-700 ease-out"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition opacity duration-500 ease-in"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="absolute inset-0 w-full h-full flex items-center justify-center">
                            <img :src="img" alt="Galeri BACT" class="w-full h-full object-cover">
                        </div>
                    </template>
                </div>

                <!-- Tombol Navigasi Kiri (<) -->
                <button type="button" @click="prev(); resetTimer();"
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 rounded-full bg-black/40 hover:bg-[#E19404] text-white flex items-center justify-center transition opacity-0 group-hover:opacity-100 focus:outline-none z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </button>

                <!-- Tombol Navigasi Kanan (>) -->
                <button type="button" @click="next(); resetTimer();"
                    class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 rounded-full bg-black/40 hover:bg-[#E19404] text-white flex items-center justify-center transition opacity-0 group-hover:opacity-100 focus:outline-none z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </button>

                <!-- Titik Navigasi Bawah (Dots) -->
                <div class="absolute bottom-4 left-0 right-0 flex justify-center space-x-2 z-10">
                    <template x-for="(img, index) in images" :key="index">
                        <button type="button" @click="active = index; resetTimer();"
                            class="h-2.5 rounded-full transition-all duration-300 focus:outline-none cursor-pointer"
                            :class="active === index ? 'w-8 bg-[#E19404]' : 'w-2.5 bg-white/50 hover:bg-white'"
                            :aria-label="`Go to gallery ${index + 1}`">
                        </button>
                    </template>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         8. SEKSI SPONSOR & PARTNER
         ========================================================= -->
    <section class="py-14 bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-8">Sponsored by
            </p>
            <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14 opacity-80">
                @forelse($sponsors as $sponsor)
                    @if($sponsor->link)
                        <a href="{{ $sponsor->link }}" target="_blank" rel="noopener noreferrer" 
                           class="inline-block transition transform hover:scale-105" title="Kunjungi {{ $sponsor->name }}">
                            <img src="{{ asset('storage/' . $sponsor->logo) }}" alt="{{ $sponsor->name }}"
                                class="h-10 sm:h-12 w-auto object-contain grayscale hover:grayscale-0 transition">
                        </a>
                    @else
                        <img src="{{ asset('storage/' . $sponsor->logo) }}" alt="{{ $sponsor->name }}"
                            class="h-10 sm:h-12 w-auto object-contain grayscale hover:grayscale-0 transition">
                    @endif
                @empty
                    <div class="text-xs font-medium text-gray-300">Sponsor Resmi</div>
                    <div class="text-xs font-medium text-gray-300">Sponsor Resmi</div>
                    <div class="text-xs font-medium text-gray-300">Sponsor Resmi</div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- =========================================================
         9. FOOTER & MEDIA SOSIAL
         ========================================================= -->
    @include('partials.footer')

    <!-- =========================================================
         SCRIPT INTERAKTIF (SLIDER & COUNTDOWN)
         ========================================================= -->
    <script>
        // A. SLIDER PEMBICARA HORIZONTAL
        function slideSpeakers(direction) {
            const container = document.getElementById('speaker-container');
            const scrollAmount = 320;
            container.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
        }

        // B. COUNTDOWN TIMER REAL-TIME
        function countdownTimer() {
            return {
                days: '0', hours: '0', minutes: '00', seconds: '00',
                start() {
                    const targetDate = new Date("January 18, 2027 08:00:00").getTime();

                    setInterval(() => {
                        const now = new Date().getTime();
                        const distance = targetDate - now;

                        if (distance > 0) {
                            this.days = String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, '0');
                            this.hours = String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                            this.minutes = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                            this.seconds = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0');
                        }
                    }, 1000);
                }
            }
        }

        // ==========================================
        // C. FITUR SCROLLSPY NAVBAR OTOMATIS
        // ==========================================
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Ambil semua seksi yang punya ID (beranda, pembicara, jadwal, lokasi, galeri)
            const sections = document.querySelectorAll('header[id], section[id]');

            // 2. Ambil semua link navigasi di navbar tengah
            const navLinks = document.querySelectorAll('nav div.hidden.md\\:flex a');

            // 3. Fungsi untuk mengecek posisi scroll
            function highlightNavigation() {
                let currentSectionId = '';
                const scrollPosition = window.scrollY + 150; // Offset 150px agar deteksi lebih responsif

                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.offsetHeight;

                    // Jika posisi scroll berada di dalam area seksi ini
                    if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                        currentSectionId = section.getAttribute('id');
                    }
                });

                // 4. Ubah warna menu navbar sesuai ID seksi yang sedang aktif
                navLinks.forEach(link => {
                    const href = link.getAttribute('href');

                    // Cek apakah link mengarah ke seksi yang aktif (#id atau /#id)
                    if (href === `#${currentSectionId}` || href === `/#${currentSectionId}`) {
                        link.classList.add('text-[#E19404]');
                        link.classList.remove('text-gray-700');
                    } else {
                        // Jangan hilangkan warna kuning untuk halaman terpisah seperti program-ilmiah & booking jika sedang di halaman tersebut
                        if (!href.includes('program-ilmiah') && !href.includes('booking') && !href.includes('hotel')) {
                            link.classList.remove('text-[#E19404]');
                            link.classList.add('text-gray-700');
                        }
                    }
                });
            }

            // Jalankan fungsi saat layar digulir (scroll) dan saat web pertama kali dimuat
            window.addEventListener('scroll', highlightNavigation);
            highlightNavigation();
        });
    </script>

</body>

</html>