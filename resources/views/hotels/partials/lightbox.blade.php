{{-- ============================================================
     LIGHTBOX FOTO + PENGGERAK SEMUA SLIDER [data-gallery]
     Cukup include SEKALI per halaman (setelah semua gallery).
     ============================================================ --}}
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .gallery-dot.is-active { background-color: #fff; transform: scale(1.25); }
</style>

<div id="bact-lightbox" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/90 backdrop-blur-sm p-4" role="dialog" aria-modal="true">
    <button type="button" id="bact-lightbox-close" aria-label="Tutup"
            class="absolute top-4 right-4 z-10 flex items-center justify-center w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
    </button>
    <button type="button" id="bact-lightbox-prev" aria-label="Sebelumnya"
            class="absolute left-3 sm:left-6 z-10 flex items-center justify-center w-11 h-11 rounded-full bg-white/15 hover:bg-white/30 text-white transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
    </button>

    <img id="bact-lightbox-img" src="" alt="" class="max-h-[85vh] max-w-full rounded-xl shadow-2xl object-contain select-none">

    <button type="button" id="bact-lightbox-next" aria-label="Berikutnya"
            class="absolute right-3 sm:right-6 z-10 flex items-center justify-center w-11 h-11 rounded-full bg-white/15 hover:bg-white/30 text-white transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
    </button>

    <span id="bact-lightbox-counter" class="absolute bottom-5 left-1/2 -translate-x-1/2 bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full"></span>
</div>

<script>
(function () {
    'use strict';

    /* ---------------- LIGHTBOX ---------------- */
    var lightbox   = document.getElementById('bact-lightbox');
    var lbImg      = document.getElementById('bact-lightbox-img');
    var lbCounter  = document.getElementById('bact-lightbox-counter');
    var lbImages   = [];
    var lbIndex    = 0;

    function showLightboxImage() {
        if (!lbImages.length) return;
        lbImg.src = lbImages[lbIndex];
        lbCounter.textContent = (lbIndex + 1) + ' / ' + lbImages.length;
    }

    function openLightbox(images, index) {
        lbImages = images || [];
        lbIndex = Math.max(0, Math.min(index || 0, lbImages.length - 1));
        showLightboxImage();
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.getElementById('bact-lightbox-close').addEventListener('click', closeLightbox);
    document.getElementById('bact-lightbox-prev').addEventListener('click', function () {
        lbIndex = (lbIndex - 1 + lbImages.length) % lbImages.length;
        showLightboxImage();
    });
    document.getElementById('bact-lightbox-next').addEventListener('click', function () {
        lbIndex = (lbIndex + 1) % lbImages.length;
        showLightboxImage();
    });
    lightbox.addEventListener('click', function (e) { if (e.target === lightbox) closeLightbox(); });
    document.addEventListener('keydown', function (e) {
        if (lightbox.classList.contains('hidden')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') document.getElementById('bact-lightbox-prev').click();
        if (e.key === 'ArrowRight') document.getElementById('bact-lightbox-next').click();
    });

    /* ---------------- SLIDERS ---------------- */
    document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
        var track    = gallery.querySelector('.gallery-track');
        var slides   = gallery.querySelectorAll('[data-gallery-slide]');
        var prevBtn  = gallery.querySelector('[data-gallery-prev]');
        var nextBtn  = gallery.querySelector('[data-gallery-next]');
        var counter  = gallery.querySelector('[data-gallery-counter]');
        var dotsWrap = gallery.querySelector('[data-gallery-dots]');
        var dots     = gallery ? gallery.querySelectorAll('[data-gallery-dot]') : [];
        var total    = slides.length;

        if (!track || total === 0) return;

        function activeIndex() {
            return Math.round(track.scrollLeft / Math.max(1, gallery.clientWidth));
        }

        function updateUI() {
            var idx = Math.max(0, Math.min(activeIndex(), total - 1));
            if (counter) counter.textContent = (idx + 1) + '/' + total;
            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === idx);
                dot.classList.toggle('bg-white', i === idx);
            });
        }

        function goTo(index) {
            index = Math.max(0, Math.min(index, total - 1));
            track.scrollTo({ left: index * gallery.clientWidth, behavior: 'smooth' });
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(activeIndex() - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(activeIndex() + 1); });

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () { goTo(parseInt(dot.getAttribute('data-gallery-dot'), 10)); });
        });

        // Sinkronkan dots & counter saat digeser manual (swipe)
        var scrollTimer = null;
        track.addEventListener('scroll', function () {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(updateUI, 60);
        }, { passive: true });

        updateUI();

        // Klik foto -> buka lightbox dengan seluruh foto kamar ini
        slides.forEach(function (slide, i) {
            slide.addEventListener('click', function () {
                var images = Array.prototype.map.call(slides, function (s) {
                    return s.getAttribute('data-full');
                });
                openLightbox(images, i);
            });
        });

        window.addEventListener('resize', updateUI);
    });
})();
</script>
