{{-- ============================================================
     SLIDER FOTO KAMAR (dipakai di katalog & form booking)
     Param: $photos (array path), $alt, $heightClass, $uid, $badge?
     ============================================================ --}}
@php($galleryPhotos = is_array($photos) ? array_values($photos) : [])

<div class="relative group/gallery overflow-hidden {{ $heightClass ?? 'h-64' }} w-full bg-gray-100"
     data-gallery data-gallery-id="{{ $uid }}">
    @if(count($galleryPhotos))
        <div class="gallery-track flex h-full w-full overflow-x-auto snap-x snap-mandatory no-scrollbar">
            @foreach($galleryPhotos as $photo)
                <div class="gallery-slide snap-center shrink-0 grow-0 basis-full h-full cursor-zoom-in"
                     data-gallery-slide data-full="{{ asset('storage/' . $photo) }}">
                    <img src="{{ asset('storage/' . $photo) }}" alt="{{ $alt }} - Foto {{ $loop->iteration }}"
                         class="w-full h-full object-cover select-none pointer-events-none"
                         draggable="false" loading="lazy">
                </div>
            @endforeach
        </div>

        @if(count($galleryPhotos) > 1)
            {{-- Tombol prev/next (selalu tampak di mobile, muncul saat hover di desktop) --}}
            <button type="button" aria-label="Foto sebelumnya" data-gallery-prev
                    class="absolute left-3 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center w-9 h-9 rounded-full bg-white/90 hover:bg-white text-gray-800 shadow-md transition sm:opacity-0 sm:group-hover/gallery:opacity-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button type="button" aria-label="Foto berikutnya" data-gallery-next
                    class="absolute right-3 top-1/2 -translate-y-1/2 z-10 flex items-center justify-center w-9 h-9 rounded-full bg-white/90 hover:bg-white text-gray-800 shadow-md transition sm:opacity-0 sm:group-hover/gallery:opacity-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
            </button>

            {{-- Indikator posisi foto --}}
            <span data-gallery-counter
                  class="absolute top-4 right-4 z-10 bg-black/55 text-white text-[11px] font-bold px-2.5 py-1 rounded-full pointer-events-none">
                1/{{ count($galleryPhotos) }}
            </span>

            {{-- Dots --}}
            <div data-gallery-dots class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 flex items-center gap-1.5">
                @foreach($galleryPhotos as $photo)
                    <button type="button" aria-label="Ke foto {{ $loop->iteration }}" data-gallery-dot="{{ $loop->index - 1 }}"
                            class="gallery-dot w-2 h-2 rounded-full bg-white/50 transition-all"></button>
                @endforeach
            </div>
        @endif
    @else
        <div class="w-full h-full flex items-center justify-center text-gray-400 font-bold text-sm">Foto Tidak Tersedia</div>
    @endif

    @if(!empty($badge))
        <span class="absolute top-4 left-4 bg-white/95 backdrop-blur-md px-3.5 py-1.5 rounded-full text-xs font-extrabold text-gray-800 shadow-sm">
            {{ $badge }}
        </span>
    @endif
</div>
