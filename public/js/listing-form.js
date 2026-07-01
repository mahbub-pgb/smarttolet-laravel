/* Listing create/edit form: photo gallery modal + map location picker. jQuery. */
(function ($) {
    'use strict';

    var MAX = 10;

    function existingImages() {
        var el = document.getElementById('existing-images');
        try { return el ? JSON.parse(el.textContent || '[]') : []; }
        catch (e) { return []; }
    }

    // ---- Client-side image compression (resize + re-encode to JPEG) ----
    var MAX_DIM = 1600;     // longest edge, px
    var QUALITY = 0.8;      // JPEG quality

    function blobToFile(blob, origName) {
        var name = String(origName || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
    }

    // Encode to JPEG. Any failure (no 2d context, toBlob throws/returns null,
    // tainted canvas) falls back to the original file instead of hanging.
    function drawToJpeg(draw, w, h, file, resolve) {
        try {
            var scale = Math.min(1, MAX_DIM / Math.max(w, h));
            var cw = Math.max(1, Math.round(w * scale));
            var ch = Math.max(1, Math.round(h * scale));
            var canvas = document.createElement('canvas');
            canvas.width = cw; canvas.height = ch;
            var ctx = canvas.getContext('2d');
            if (!ctx) { resolve(file); return; }
            draw(ctx, cw, ch);
            canvas.toBlob(function (blob) {
                // Keep the original if compression didn't actually make it smaller.
                if (!blob || blob.size >= file.size) { resolve(file); return; }
                resolve(blobToFile(blob, file.name));
            }, 'image/jpeg', QUALITY);
        } catch (e) {
            resolve(file);
        }
    }

    function compressFile(file) {
        return new Promise(function (resolve) {
            // Settle exactly once; a watchdog guarantees we never hang the form
            // if the browser's image decoder/encoder silently stalls.
            var done = false;
            var timer = setTimeout(function () { settle(file); }, 15000);
            function settle(f) {
                if (done) return;
                done = true;
                clearTimeout(timer);
                resolve(f || file);
            }

            if (!file.type || file.type.indexOf('image/') !== 0) { settle(file); return; }

            function viaImage() {
                var url = URL.createObjectURL(file);
                var img = new Image();
                img.onload = function () {
                    URL.revokeObjectURL(url);
                    drawToJpeg(function (ctx, cw, ch) { ctx.drawImage(img, 0, 0, cw, ch); }, img.naturalWidth, img.naturalHeight, file, settle);
                };
                img.onerror = function () { URL.revokeObjectURL(url); settle(file); };
                img.src = url;
            }

            // Prefer createImageBitmap — it honours EXIF orientation from phones.
            if (window.createImageBitmap) {
                var pr;
                try { pr = createImageBitmap(file, { imageOrientation: 'from-image' }); }
                catch (e) { viaImage(); return; }
                pr.then(function (bmp) {
                    drawToJpeg(function (ctx, cw, ch) { ctx.drawImage(bmp, 0, 0, cw, ch); if (bmp.close) bmp.close(); }, bmp.width, bmp.height, file, settle);
                }).catch(viaImage);
            } else {
                viaImage();
            }
        });
    }

    function compressFiles(files) {
        return Promise.all($.map(files, function (f) { return compressFile(f); }));
    }

    // ================= Photo gallery modal =================
    $(function () {
        var imgInput = document.getElementById('img-input');
        if (!imgInput) return;

        var $preview = $('#media-preview');
        var $modalPreview = $('#modal-preview'); // mirrors the strip inside the modal
        var $pickedBox = $('#picked-inputs');
        var $removeBox = $('#remove-inputs');
        var $modal = $('#gallery-modal');

        var removed = {};          // existing urls flagged for removal
        var picked = {};           // id -> url (library)
        var existing = existingImages();

        function uploads() { return imgInput.files ? Array.prototype.slice.call(imgInput.files) : []; }
        function keptExisting() { return $.grep(existing, function (u) { return !removed[u]; }); }
        function total() { return keptExisting().length + Object.keys(picked).length + uploads().length; }

        function tile(src, badge, onRemove) {
            var $t = $('<div class="media-thumb"></div>');
            $t.append($('<img alt="">').attr('src', src));
            if (badge) $t.append('<span class="media-badge">' + badge + '</span>');
            var $x = $('<button type="button" class="media-x" aria-label="Remove">✕</button>');
            $x.on('click', onRemove);
            $t.append($x);
            return $t;
        }

        function syncPicked() {
            $pickedBox.empty();
            $.each(picked, function (id) {
                $pickedBox.append($('<input type="hidden" name="picked[]">').val(id));
            });
        }
        function syncRemove() {
            $removeBox.empty();
            $.each(removed, function (url) {
                $removeBox.append($('<input type="hidden" name="remove_images[]">').val(url));
            });
        }
        function removeUpload(idx) {
            var dt = new DataTransfer();
            $.each(uploads(), function (i, f) { if (i !== idx) dt.items.add(f); });
            imgInput.files = dt.files;
            render();
        }

        // Paint the thumbnail strip into one container. Tiles carry click
        // handlers, so each container gets its own freshly-built set.
        function paint($container) {
            $container.empty();
            $.each(keptExisting(), function (_, url) {
                $container.append(tile(url, 'saved', function () { removed[url] = true; syncRemove(); render(); }));
            });
            $.each(picked, function (id, url) {
                $container.append(tile(url, 'library', function () { delete picked[id]; syncPicked(); render(); refreshLib(); }));
            });
            $.each(uploads(), function (idx, file) {
                $container.append(tile(URL.createObjectURL(file), 'new', function () { removeUpload(idx); }));
            });
            if (!$container.children().length) {
                $container.html('<p class="form-hint" style="margin:0">No photos added yet.</p>');
            }
        }

        function render() {
            paint($preview);
            if ($modalPreview.length) paint($modalPreview);
        }

        // Modal open/close + tabs
        function openModal() { $modal.addClass('open').attr('aria-hidden', 'false'); }
        function closeModal() { $modal.removeClass('open').attr('aria-hidden', 'true'); }
        $('#open-gallery').on('click', openModal);
        $('#gallery-close, #gallery-done').on('click', closeModal);
        $modal.on('click', function (e) { if (e.target === this) closeModal(); });

        $modal.find('.modal-tab').on('click', function () {
            var tab = $(this).data('tab');
            $modal.find('.modal-tab').removeClass('active');
            $modal.find('.modal-pane').removeClass('active');
            $(this).addClass('active');
            $modal.find('.modal-pane[data-pane="' + tab + '"]').addClass('active');
        });

        // Upload (dropzone) — selected images are compressed before they're queued.
        var busy = false;

        function commitFiles(keep, files) {
            var dt = new DataTransfer();
            $.each(keep, function (_, f) { if (f) dt.items.add(f); });
            $.each(files, function (_, f) { if (f) dt.items.add(f); });
            imgInput.files = dt.files; // programmatic set does not refire 'change'
            busy = false;
            enforceMax(); render();
        }

        function addFiles(newFiles, mergeExisting) {
            if (busy) return;
            if (!newFiles.length) { enforceMax(); render(); return; }
            busy = true;
            var keep = mergeExisting ? uploads() : [];
            $preview.add($modalPreview).html('<p class="form-hint" style="margin:0">Compressing photos…</p>');
            compressFiles(newFiles)
                .then(function (out) { commitFiles(keep, out); })
                .catch(function () { commitFiles(keep, newFiles); }); // store originals on failure
        }

        var $dropzone = $('#dropzone');
        $dropzone.on('click', function () { imgInput.click(); });
        $dropzone.on('dragover dragenter', function (e) { e.preventDefault(); $dropzone.addClass('over'); });
        $dropzone.on('dragleave drop', function (e) { e.preventDefault(); $dropzone.removeClass('over'); });
        $dropzone.on('drop', function (e) {
            var dropped = $.grep(Array.prototype.slice.call(e.originalEvent.dataTransfer.files), function (f) {
                return f.type && f.type.indexOf('image/') === 0;
            });
            addFiles(dropped, true);
        });
        $(imgInput).on('change', function () { addFiles(uploads(), false); });

        function enforceMax() {
            if (total() <= MAX) return;
            var allowed = Math.max(0, MAX - (keptExisting().length + Object.keys(picked).length));
            var dt = new DataTransfer();
            $.each(uploads().slice(0, allowed), function (_, f) { dt.items.add(f); });
            imgInput.files = dt.files;
            alert('You can add at most ' + MAX + ' photos.');
        }

        // Library selection
        function refreshLib() {
            $modal.find('.lib-item').each(function () {
                $(this).toggleClass('selected', Object.prototype.hasOwnProperty.call(picked, $(this).data('id')));
            });
        }
        $modal.find('.lib-item').on('click', function () {
            var id = String($(this).data('id'));
            if (Object.prototype.hasOwnProperty.call(picked, id)) {
                delete picked[id];
            } else {
                if (total() >= MAX) { alert('You can add at most ' + MAX + ' photos.'); return; }
                picked[id] = $(this).data('url');
            }
            syncPicked(); refreshLib(); render();
        });

        render();
    });

    // ================= Numeric-only fields =================
    // type="number" still lets Chrome accept "e", "+", "." and stray symbols.
    // Block them at the keystroke so these fields only ever hold whole numbers
    // (a leading "-" is allowed where the field's min is negative).
    $(function () {
        $('.listing-form input[type="number"]').each(function () {
            var allowNeg = parseFloat($(this).attr('min')) < 0;

            $(this).on('keydown', function (e) {
                if (e.ctrlKey || e.metaKey || e.altKey) return;   // copy/paste/shortcuts
                var k = e.key;
                if (!k || k.length !== 1) return;                 // arrows, Backspace, Tab…
                if (k >= '0' && k <= '9') return;
                if (k === '-' && allowNeg && this.selectionStart === 0) return;
                e.preventDefault();
            });

            $(this).on('paste', function (e) {
                var clip = (e.originalEvent || e).clipboardData || window.clipboardData;
                if (!clip) return;
                var text = clip.getData('text') || '';
                var cleaned = text.replace(/[^\d-]/g, '');
                cleaned = allowNeg
                    ? (cleaned.charAt(0) === '-' ? '-' : '') + cleaned.replace(/-/g, '')
                    : cleaned.replace(/-/g, '');
                e.preventDefault();
                if (cleaned !== '') this.value = cleaned;
            });
        });
    });

    // ================= Client-side validation =================
    // A failed server validation redirects back, and browsers cannot restore a
    // file <input> — so the user's uploaded photos would be lost. Catch the
    // common required-field mistakes up-front and block the submit (no reload)
    // until they're fixed, flagging each problem field in red.
    $(function () {
        var $form = $('#listing-form');
        if (!$form.length) return;

        function setError($field, message) {
            $field.addClass('invalid');
            var $msg = $field.next('.field-msg');
            if ($msg.length) $msg.text(message);
            else $field.after($('<p class="field-msg"></p>').text(message));
        }
        function clearError($field) {
            $field.removeClass('invalid').next('.field-msg').remove();
        }

        function val(name) {
            var $f = $form.find('[name="' + name + '"]').first();
            return { $el: $f, v: $f.length ? String($f.val() == null ? '' : $f.val()) : '' };
        }

        var required = [
            { name: 'type', msg: 'Select a property type.' },
            { name: 'title', msg: 'Title is required.' },
            { name: 'description', msg: 'Description is required.' },
            { name: 'rent', msg: 'Enter the monthly rent.', test: function (v) { return /^\d+$/.test(v); } }
        ];

        $form.on('submit', function (e) {
            var firstBad = null;

            $.each(required, function (_, r) {
                var f = val(r.name);
                if (!f.$el.length) return;
                clearError(f.$el);
                var ok = r.test ? (f.v.trim() !== '' && r.test(f.v.trim())) : f.v.trim() !== '';
                if (!ok) { setError(f.$el, r.msg); firstBad = firstBad || f.$el; }
            });

            // YouTube URL is optional but must be valid when provided.
            var video = val('video_tour_url');
            if (video.$el.length) {
                clearError(video.$el);
                var vv = video.v.trim();
                if (vv !== '' && !/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)\//i.test(vv)) {
                    setError(video.$el, 'Enter a valid YouTube URL.');
                    firstBad = firstBad || video.$el;
                }
            }

            if (firstBad) {
                e.preventDefault();
                firstBad.trigger('focus');
                if (firstBad[0].scrollIntoView) firstBad[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // Clear a field's red mark as soon as the user starts fixing it.
        $form.on('input change', '.invalid', function () { clearError($(this)); });

        // Server-returned errors (after a redirect-back): mark those fields red
        // too, so the user sees exactly what to fix — not just the top summary.
        (function () {
            var el = document.getElementById('form-errors');
            if (!el) return;
            var bag;
            try { bag = JSON.parse(el.textContent || '{}'); } catch (e) { return; }
            $.each(bag, function (key, messages) {
                var base = key.split('.')[0]; // images.0 -> images
                var $field = $form.find('[name="' + base + '"], [name="' + base + '[]"]').filter(':visible').first();
                if ($field.length) setError($field, messages[0]);
            });
        })();
    });

    // ================= Location map picker =================
    function reverseGeocodeGoogle(geocoder, lat, lng) {
        geocoder.geocode({ location: { lat: +lat, lng: +lng } }, function (results, status) {
            if (status !== 'OK' || !results[0]) return;
            // Only the address is auto-filled from the pin; the area is chosen
            // manually via the searchable Area dropdown.
            $('#address').val(results[0].formatted_address || $('#address').val());
        });
    }

    // Admin-configured zoom levels (data attributes on #pick-map), with fallbacks.
    function mapZoom(el, kind, fallback) {
        var v = parseInt($(el).data(kind), 10);
        return isNaN(v) ? fallback : v;
    }

    // Admin-configured default centre coordinate (data-default-lat / -lng).
    function mapDefaultCoord(el, kind, fallback) {
        var v = parseFloat($(el).data(kind));
        return isNaN(v) ? fallback : v;
    }

    window.initListingMap = function () {
        var el = document.getElementById('pick-map');
        if (!el) return;
        var startLat = parseFloat($(el).data('lat')) || mapDefaultCoord(el, 'default-lat', 23.8103);
        var startLng = parseFloat($(el).data('lng')) || mapDefaultCoord(el, 'default-lng', 90.4125);
        var hasPin = !!($(el).data('lat') && $(el).data('lng'));
        var defaultZoom = mapZoom(el, 'zoom', 12);
        var pinnedZoom = mapZoom(el, 'zoom-pinned', 16);

        var map = new google.maps.Map(el, { center: { lat: startLat, lng: startLng }, zoom: hasPin ? pinnedZoom : defaultZoom, mapTypeControl: false, streetViewControl: false });
        var marker = new google.maps.Marker({ map: map, position: { lat: startLat, lng: startLng }, draggable: true });
        var geocoder = new google.maps.Geocoder();
        if (hasPin) { $('#latitude').val(startLat.toFixed(7)); $('#longitude').val(startLng.toFixed(7)); }

        function setPin(lat, lng, geocode) {
            $('#latitude').val((+lat).toFixed(7));
            $('#longitude').val((+lng).toFixed(7));
            marker.setPosition({ lat: +lat, lng: +lng });
            map.panTo({ lat: +lat, lng: +lng });
            if (geocode) reverseGeocodeGoogle(geocoder, lat, lng);
        }

        marker.addListener('dragend', function (e) { setPin(e.latLng.lat(), e.latLng.lng(), true); });
        map.addListener('click', function (e) { setPin(e.latLng.lat(), e.latLng.lng(), true); });

        var search = document.getElementById('map-search');
        if (search && google.maps.places) {
            var ac = new google.maps.places.Autocomplete(search, { fields: ['geometry', 'formatted_address'] });
            ac.addListener('place_changed', function () {
                var p = ac.getPlace();
                if (p.geometry) { map.setZoom(pinnedZoom); setPin(p.geometry.location.lat(), p.geometry.location.lng(), true); }
            });
        }

        $('#use-location').on('click', function () {
            if (!navigator.geolocation) { alert('Geolocation is not supported by your browser.'); return; }
            navigator.geolocation.getCurrentPosition(
                function (pos) { map.setZoom(pinnedZoom); setPin(pos.coords.latitude, pos.coords.longitude, true); },
                function () { alert('Could not get your location. Please allow location access or pick on the map.'); }
            );
        });
    };

    // Leaflet fallback (no Google Maps key).
    $(function () {
        var el = document.getElementById('pick-map');
        if (!el || $(el).data('maps') !== 'leaflet') return;

        var startLat = parseFloat($(el).data('lat')) || mapDefaultCoord(el, 'default-lat', 23.8103);
        var startLng = parseFloat($(el).data('lng')) || mapDefaultCoord(el, 'default-lng', 90.4125);
        var hasPin = !!($(el).data('lat') && $(el).data('lng'));
        var defaultZoom = mapZoom(el, 'zoom', 12);
        var pinnedZoom = mapZoom(el, 'zoom-pinned', 16);

        var map = L.map(el).setView([startLat, startLng], hasPin ? pinnedZoom : defaultZoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        var marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
        if (hasPin) { $('#latitude').val(startLat.toFixed(7)); $('#longitude').val(startLng.toFixed(7)); }

        function reverseGeocode(lat, lng) {
            $.getJSON('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng)
                .done(function (d) {
                    // Only the address is auto-filled; area is chosen manually.
                    if (d.display_name) $('#address').val(d.display_name);
                });
        }
        function setPin(lat, lng) { $('#latitude').val((+lat).toFixed(7)); $('#longitude').val((+lng).toFixed(7)); marker.setLatLng([lat, lng]); reverseGeocode(lat, lng); }
        marker.on('dragend', function (e) { var p = e.target.getLatLng(); setPin(p.lat, p.lng); });
        map.on('click', function (e) { setPin(e.latlng.lat, e.latlng.lng); });
        $('#use-location').on('click', function () {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(function (pos) { map.setView([pos.coords.latitude, pos.coords.longitude], pinnedZoom); setPin(pos.coords.latitude, pos.coords.longitude); });
        });
    });
})(jQuery);
