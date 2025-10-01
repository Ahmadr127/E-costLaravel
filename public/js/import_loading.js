document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('layanan-upload-form');
    if (!form) return;

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
    overlay.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl border border-gray-200 w-full max-w-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-5 w-5 border-2 border-green-600 border-t-transparent rounded-full animate-spin"></div>
                <div id="import-phase-text" class="text-sm font-medium text-gray-800">Mengunggah berkas (1/2)...</div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                <div id="import-progress-bar" class="bg-green-600 h-2 rounded-full" style="width:0%"></div>
            </div>
            <div id="import-progress-text" class="mt-2 text-xs text-gray-600 text-right">0%</div>
            <div id="import-error-text" class="mt-2 text-xs text-red-600 hidden"></div>
        </div>
    `;
    document.body.appendChild(overlay);

    function showOverlay() {
        overlay.classList.remove('hidden');
    }
    function hideOverlay() {
        overlay.classList.add('hidden');
    }
    let processingInterval = null;
    function setProgress(pct) {
        const bar = overlay.querySelector('#import-progress-bar');
        const txt = overlay.querySelector('#import-progress-text');
        const n = Math.max(0, Math.min(100, Math.round(pct)));
        if (bar) bar.style.width = n + '%';
        if (txt) txt.textContent = n + '%';
    }
    function setPhase(text) {
        const el = overlay.querySelector('#import-phase-text');
        if (el) el.textContent = text;
    }
    function startProcessingAnimation(startAt = 90) {
        setPhase('Memproses data (2/2)...');
        let p = startAt;
        if (processingInterval) clearInterval(processingInterval);
        processingInterval = setInterval(() => {
            // bounce slowly between 90%..99% while server memproses
            p += Math.random() * 1.2; // advance slowly
            if (p >= 99) p = 90;
            setProgress(p);
        }, 400);
    }
    function stopProcessingAnimation() {
        if (processingInterval) clearInterval(processingInterval);
        processingInterval = null;
    }
    function setError(msg) {
        const el = overlay.querySelector('#import-error-text');
        if (el) { el.textContent = msg || 'Terjadi kesalahan saat impor.'; el.classList.remove('hidden'); }
    }

    form.addEventListener('submit', function (ev) {
        // Intercept submit to enable progress feedback
        ev.preventDefault();
        const action = form.getAttribute('action');
        const method = (form.getAttribute('method') || 'POST').toUpperCase();
        const redirectUrl = form.dataset.indexUrl || window.location.href;
        const formData = new FormData(form);

        // Disable form controls
        const controls = form.querySelectorAll('input, button, select, textarea');
        controls.forEach(c => c.setAttribute('disabled', 'disabled'));

        showOverlay();
        setProgress(0);
        setPhase('Mengunggah berkas (1/2)...');

        const xhr = new XMLHttpRequest();
        xhr.open(method, action, true);
        xhr.setRequestHeader('Accept', 'text/html,application/json');
        // Progress of upload (request body)
        if (xhr.upload) {
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    // Cap upload phase to 90%
                    const percent = (e.loaded / e.total) * 90;
                    setProgress(percent);
                }
            });
            // When upload finished, enter processing phase animation
            xhr.upload.addEventListener('load', function () {
                startProcessingAnimation();
            });
        }

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                // Success: server usually redirects to index; use responseURL when available
                if (xhr.status >= 200 && xhr.status < 400) {
                    stopProcessingAnimation();
                    setProgress(100);
                    const target = xhr.responseURL || redirectUrl;
                    window.location.href = target;
                } else {
                    stopProcessingAnimation();
                    setError('Gagal mengimpor. Status: ' + xhr.status);
                    // Re-enable controls to allow retry
                    controls.forEach(c => c.removeAttribute('disabled'));
                }
            }
        };

        xhr.onerror = function () {
            stopProcessingAnimation();
            setError('Jaringan bermasalah. Coba lagi.');
            controls.forEach(c => c.removeAttribute('disabled'));
        };

        xhr.send(formData);
    });
});


