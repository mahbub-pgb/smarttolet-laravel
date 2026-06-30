/* Central media library picker. Reusable across the admin: include the
   partials/media-library.blade.php modal once on a page, then call
   window.MediaLibrary.open(fn) — fn receives the picked image URL.

   Two tabs: pick an already-uploaded image, or upload a new one (compressed
   client-side, then stored + compressed again server-side via MediaService). */
(function ($) {
    'use strict';

    var MAX_DIM = 1600;   // longest edge after client-side resize
    var QUALITY = 0.8;    // JPEG quality

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    // Resize + re-encode an image File to a smaller JPEG. Any failure falls back
    // to the original file (a 15s watchdog guarantees we never hang).
    function compress(file) {
        return new Promise(function (resolve) {
            if (!file.type || file.type.indexOf('image/') !== 0) { resolve(file); return; }

            var done = false;
            var timer = setTimeout(function () { settle(file); }, 15000);
            function settle(f) { if (done) return; done = true; clearTimeout(timer); resolve(f || file); }

            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                URL.revokeObjectURL(url);
                try {
                    var scale = Math.min(1, MAX_DIM / Math.max(img.naturalWidth, img.naturalHeight));
                    var w = Math.max(1, Math.round(img.naturalWidth * scale));
                    var h = Math.max(1, Math.round(img.naturalHeight * scale));
                    var canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    var ctx = canvas.getContext('2d');
                    if (!ctx) { settle(file); return; }
                    ctx.drawImage(img, 0, 0, w, h);
                    canvas.toBlob(function (blob) {
                        if (!blob || blob.size >= file.size) { settle(file); return; }
                        var name = String(file.name || 'image').replace(/\.[^.]+$/, '') + '.jpg';
                        settle(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                    }, 'image/jpeg', QUALITY);
                } catch (e) { settle(file); }
            };
            img.onerror = function () { URL.revokeObjectURL(url); settle(file); };
            img.src = url;
        });
    }

    var $modal, listUrl, uploadUrl, onSelect, page, lastPage, loaded, bound;

    function tile(media, prepend) {
        var $btn = $('<button type="button" class="lib-item"></button>').attr('data-url', media.url);
        $btn.append($('<img alt="">').attr('src', media.url));
        $('#ml-status').remove();
        if (prepend) { $('#ml-grid').prepend($btn); } else { $('#ml-grid').append($btn); }
    }

    function toggleMore() {
        $('#ml-load-more').prop('hidden', !(lastPage && page < lastPage));
    }

    function loadPage(p) {
        return $.getJSON(listUrl, { page: p, limit: 24 }).done(function (res) {
            page = res.current_page; lastPage = res.last_page; loaded = true;
            if (!res.data.length && page === 1) {
                $('#ml-grid').html('<p class="form-hint" id="ml-status">Your library is empty. Upload an image to get started.</p>');
            } else {
                res.data.forEach(function (m) { tile(m, false); });
            }
            toggleMore();
        }).fail(function () {
            $('#ml-status').text('Could not load the library. Please try again.');
        });
    }

    function setTab(name) {
        $modal.find('[data-ml-tab]').removeClass('active')
            .filter('[data-ml-tab="' + name + '"]').addClass('active');
        $modal.find('[data-ml-pane]').removeClass('active')
            .filter('[data-ml-pane="' + name + '"]').addClass('active');
    }

    function uploadFiles(files) {
        var list = $.grep(Array.prototype.slice.call(files), function (f) {
            return f.type && f.type.indexOf('image/') === 0;
        });
        if (!list.length) return;

        var $status = $('#ml-upload-status');
        var total = list.length, doneCount = 0, firstUrl = null;
        $status.css('color', '').text('Uploading 0/' + total + '…');

        function next(i) {
            if (i >= list.length) {
                $status.text('Uploaded ' + doneCount + '/' + total + '.');
                if (firstUrl && onSelect) { onSelect(firstUrl); closeModal(); }
                return;
            }
            compress(list[i]).then(function (file) {
                var data = new FormData();
                data.append('file', file);
                $.ajax({
                    url: uploadUrl, method: 'POST', data: data,
                    processData: false, contentType: false,
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
                }).done(function (res) {
                    doneCount++;
                    if (res && res.url) { tile(res, true); if (!firstUrl) firstUrl = res.url; }
                    $status.text('Uploading ' + doneCount + '/' + total + '…');
                    next(i + 1);
                }).fail(function (xhr) {
                    var msg = 'Upload failed.';
                    try { var j = JSON.parse(xhr.responseText); msg = j.message || msg; } catch (e) {}
                    $status.css('color', '#b91c1c').text(msg);
                    next(i + 1);
                });
            });
        }
        next(0);
    }

    function bind() {
        if (bound) return;
        bound = true;

        $modal.on('click', '[data-ml-close]', closeModal);
        $modal.on('click', function (e) { if (e.target === this) closeModal(); });
        $modal.on('click', '[data-ml-tab]', function () { setTab($(this).data('ml-tab')); });

        // Pick an image (event-delegated so dynamically added tiles work).
        $modal.on('click', '.lib-item', function () {
            var url = $(this).data('url');
            if (url && onSelect) { onSelect(url); }
            closeModal();
        });

        $('#ml-load-more').on('click', function () { loadPage(page + 1); });

        // Upload via the hidden file input + drag/drop on the dropzone.
        $('#ml-file').on('change', function () { uploadFiles(this.files); this.value = ''; });
        var $dz = $('#ml-dropzone');
        $dz.on('dragover dragenter', function (e) { e.preventDefault(); $dz.addClass('over'); });
        $dz.on('dragleave drop', function (e) { e.preventDefault(); $dz.removeClass('over'); });
        $dz.on('drop', function (e) { uploadFiles(e.originalEvent.dataTransfer.files); });
    }

    function closeModal() { $modal.removeClass('open').attr('aria-hidden', 'true'); }

    function open(cb) {
        $modal = $('#media-library-modal');
        if (!$modal.length) { if (window.console) console.error('[media] modal markup missing'); return; }
        listUrl = $modal.data('list-url');
        uploadUrl = $modal.data('upload-url');
        onSelect = cb;
        bind();
        setTab('library');
        $modal.addClass('open').attr('aria-hidden', 'false');
        if (!loaded) { loadPage(1); }
    }

    window.MediaLibrary = { open: open };
})(jQuery);
