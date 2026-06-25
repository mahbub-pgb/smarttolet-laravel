/* Listing create/edit form: photo gallery modal + map location picker. jQuery. */
(function ($) {
    'use strict';

    var MAX = 10;

    function existingImages() {
        var el = document.getElementById('existing-images');
        try { return el ? JSON.parse(el.textContent || '[]') : []; }
        catch (e) { return []; }
    }

    // ================= Photo gallery modal =================
    $(function () {
        var imgInput = document.getElementById('img-input');
        if (!imgInput) return;

        var $preview = $('#media-preview');
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

        function render() {
            $preview.empty();
            $.each(keptExisting(), function (_, url) {
                $preview.append(tile(url, 'saved', function () { removed[url] = true; syncRemove(); render(); }));
            });
            $.each(picked, function (id, url) {
                $preview.append(tile(url, 'library', function () { delete picked[id]; syncPicked(); render(); refreshLib(); }));
            });
            $.each(uploads(), function (idx, file) {
                $preview.append(tile(URL.createObjectURL(file), 'new', function () { removeUpload(idx); }));
            });
            if (!$preview.children().length) {
                $preview.html('<p class="form-hint" style="margin:0">No photos added yet.</p>');
            }
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

        // Upload (dropzone)
        var $dropzone = $('#dropzone');
        $dropzone.on('click', function () { imgInput.click(); });
        $dropzone.on('dragover dragenter', function (e) { e.preventDefault(); $dropzone.addClass('over'); });
        $dropzone.on('dragleave drop', function (e) { e.preventDefault(); $dropzone.removeClass('over'); });
        $dropzone.on('drop', function (e) {
            var dt = new DataTransfer();
            $.each(uploads(), function (_, f) { dt.items.add(f); });
            $.each(e.originalEvent.dataTransfer.files, function (_, f) { if (f.type.indexOf('image/') === 0) dt.items.add(f); });
            imgInput.files = dt.files;
            enforceMax(); render();
        });
        $(imgInput).on('change', function () { enforceMax(); render(); });

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

    // ================= Location map picker =================
    function reverseGeocodeGoogle(geocoder, lat, lng) {
        geocoder.geocode({ location: { lat: +lat, lng: +lng } }, function (results, status) {
            if (status !== 'OK' || !results[0]) return;
            $('#address').val(results[0].formatted_address || $('#address').val());
            var comp = results[0].address_components || [];
            var area = null;
            $.each(comp, function (_, c) {
                if (!area && (c.types.indexOf('sublocality') >= 0 || c.types.indexOf('neighborhood') >= 0)) area = c;
            });
            if (!area) $.each(comp, function (_, c) { if (!area && c.types.indexOf('locality') >= 0) area = c; });
            if (area) $('#area_name').val(area.long_name);
        });
    }

    window.initListingMap = function () {
        var el = document.getElementById('pick-map');
        if (!el) return;
        var startLat = parseFloat($(el).data('lat')) || 23.8103;
        var startLng = parseFloat($(el).data('lng')) || 90.4125;
        var hasPin = !!($(el).data('lat') && $(el).data('lng'));

        var map = new google.maps.Map(el, { center: { lat: startLat, lng: startLng }, zoom: hasPin ? 16 : 12, mapTypeControl: false, streetViewControl: false });
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
                if (p.geometry) { map.setZoom(16); setPin(p.geometry.location.lat(), p.geometry.location.lng(), true); }
            });
        }

        $('#use-location').on('click', function () {
            if (!navigator.geolocation) { alert('Geolocation is not supported by your browser.'); return; }
            navigator.geolocation.getCurrentPosition(
                function (pos) { map.setZoom(16); setPin(pos.coords.latitude, pos.coords.longitude, true); },
                function () { alert('Could not get your location. Please allow location access or pick on the map.'); }
            );
        });
    };

    // Leaflet fallback (no Google Maps key).
    $(function () {
        var el = document.getElementById('pick-map');
        if (!el || $(el).data('maps') !== 'leaflet') return;

        var startLat = parseFloat($(el).data('lat')) || 23.8103;
        var startLng = parseFloat($(el).data('lng')) || 90.4125;
        var hasPin = !!($(el).data('lat') && $(el).data('lng'));

        var map = L.map(el).setView([startLat, startLng], hasPin ? 16 : 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        var marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
        if (hasPin) { $('#latitude').val(startLat.toFixed(7)); $('#longitude').val(startLng.toFixed(7)); }

        function reverseGeocode(lat, lng) {
            $.getJSON('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng)
                .done(function (d) {
                    if (d.display_name) $('#address').val(d.display_name);
                    var a = d.address || {};
                    var area = a.suburb || a.neighbourhood || a.city_district || a.city || a.town || a.village;
                    if (area) $('#area_name').val(area);
                });
        }
        function setPin(lat, lng) { $('#latitude').val((+lat).toFixed(7)); $('#longitude').val((+lng).toFixed(7)); marker.setLatLng([lat, lng]); reverseGeocode(lat, lng); }
        marker.on('dragend', function (e) { var p = e.target.getLatLng(); setPin(p.lat, p.lng); });
        map.on('click', function (e) { setPin(e.latlng.lat, e.latlng.lng); });
        $('#use-location').on('click', function () {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(function (pos) { map.setView([pos.coords.latitude, pos.coords.longitude], 16); setPin(pos.coords.latitude, pos.coords.longitude); });
        });
    });
})(jQuery);
