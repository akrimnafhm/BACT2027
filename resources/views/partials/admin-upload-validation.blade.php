<script>
(function () {
    // Validasi ukuran file di sisi klien sebelum form di-submit.
    // Setiap <input type="file"> bisa diberi atribut data-max-size (dalam bytes).
    // Jika ada file yang melebihi batas: tombol submit di-disable + pesan error tampil,
    // sehingga form tidak pernah dikirim ke server (mencegah error PHP warning / PostTooLarge).

    function fmtBytes(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(0).replace(/\.0$/, '') + ' MB';
        }
        return (bytes / 1024).toFixed(0).replace(/\.0$/, '') + ' KB';
    }

    function validateInput(input) {
        var maxBytes = parseInt(input.getAttribute('data-max-size'), 10);
        var form = input.closest('form');
        if (!form || !maxBytes) return;

        var submitBtn = form.querySelector('button[type="submit"]');
        var msgEl = input.nextElementSibling;
        if (!msgEl || !msgEl.classList.contains('upload-size-error')) {
            msgEl = document.createElement('p');
            msgEl.className = 'upload-size-error text-xs text-red-600 font-bold mt-1';
            input.insertAdjacentElement('afterend', msgEl);
        }

        var files = input.files || [];
        var oversized = [];
        for (var i = 0; i < files.length; i++) {
            if (files[i].size > maxBytes) {
                oversized.push(files[i].name + ' (' + fmtBytes(files[i].size) + ')');
            }
        }

        if (oversized.length > 0) {
            var label = input.getAttribute('data-max-label') || 'file';
            msgEl.textContent = 'Ukuran ' + label + ' melebihi batas maksimal ' + fmtBytes(maxBytes) + '. File terlalu besar: ' + oversized.join(', ');
            if (submitBtn) submitBtn.disabled = true;
        } else {
            msgEl.textContent = '';
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    document.addEventListener('change', function (e) {
        var target = e.target;
        if (target && target.matches && target.matches('input[type="file"][data-max-size]')) {
            validateInput(target);
        }
    });
})();
</script>
