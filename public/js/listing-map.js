/* Listings map view: plot all listings, info window on click. jQuery-based. */
(function ($) {
    'use strict';

    function fmt(n) { return Number(n).toLocaleString(); }

    function points() {
        var el = document.getElementById('map-points');
        try { return el ? JSON.parse(el.textContent || '[]') : []; }
        catch (e) { return []; }
    }

    // Admin-configured zoom levels (data attributes on #map), with fallbacks.
    function zoom(kind, fallback) {
        var v = parseInt($('#map').data(kind), 10);
        return isNaN(v) ? fallback : v;
    }

    // Admin-configured default map centre (data-lat / data-lng on #map).
    function center() {
        var lat = parseFloat($('#map').data('lat'));
        var lng = parseFloat($('#map').data('lng'));
        return { lat: isNaN(lat) ? 23.8103 : lat, lng: isNaN(lng) ? 90.4125 : lng };
    }

    // The point a "Near me" search was centred on (data-origin-lat/lng), or null.
    function origin() {
        var lat = parseFloat($('#map').data('origin-lat'));
        var lng = parseFloat($('#map').data('origin-lng'));
        return (isNaN(lat) || isNaN(lng)) ? null : { lat: lat, lng: lng };
    }

    function popupHtml(p) {
        var facts = [];
        if (p.bedrooms) facts.push('🛏 ' + p.bedrooms + ' bed');
        if (p.bathrooms) facts.push('🛁 ' + p.bathrooms + ' bath');
        if (p.area_sqft) facts.push('📐 ' + fmt(p.area_sqft) + ' sqft');

        return '<div class="map-pop">' +
            (p.image ? '<img src="' + p.image + '" alt="">' : '') +
            '<div class="map-pop-body">' +
            '<div class="map-pop-price">৳' + fmt(p.rent) + ' <small>/mo</small></div>' +
            '<div class="map-pop-title">' + p.title + '</div>' +
            '<div class="map-pop-meta">📍 ' + p.area + ' · ' + p.type + '</div>' +
            (facts.length ? '<div class="map-pop-facts">' + facts.join(' · ') + '</div>' : '') +
            '<a class="map-pop-link" href="' + p.url + '">View details →</a>' +
            '</div></div>';
    }

    // Google Maps entry point (referenced by the loader's ?callback=).
    window.initListingsMap = function () {
        var pts = points();
        var map = new google.maps.Map(document.getElementById('map'), {
            center: center(),
            zoom: zoom('zoom', 12),
            mapTypeControl: false,
            streetViewControl: false
        });
        var info = new google.maps.InfoWindow();

        $.each(pts, function (_, p) {
            var pos = { lat: p.lat, lng: p.lng };
            var marker = new google.maps.Marker({ position: pos, map: map, title: p.title });
            marker.addListener('click', function () {
                info.setContent(popupHtml(p));
                info.open(map, marker);
            });
        });

        // When a "Near me" search is active, drop a distinct marker on the
        // user's location and centre there at a closer zoom.
        var o = origin();
        if (o) {
            new google.maps.Marker({
                position: o,
                map: map,
                title: 'Your location',
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8, fillColor: '#2563eb', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 }
            });
            map.setCenter(o);
            map.setZoom(zoom('zoom-pinned', 14));
            return;
        }

        // Honour the admin's default view (centre + zoom). A single pin is
        // centred on that listing at the closer "pinned" zoom; with several
        // pins we keep the configured default view rather than auto-fitting,
        // so the zoom setting actually takes effect.
        if (pts.length === 1) {
            map.setCenter({ lat: pts[0].lat, lng: pts[0].lng });
            map.setZoom(zoom('zoom-pinned', 15));
        }
    };

    // Leaflet fallback (used when no Google Maps key is configured).
    $(function () {
        var $map = $('#map');
        if (!$map.length || $map.data('maps') !== 'leaflet') return;

        var pts = points();
        var c = center();
        var map = L.map('map').setView([c.lat, c.lng], zoom('zoom', 12));
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        if (pts.length) {
            var markers = $.map(pts, function (p) {
                return L.marker([p.lat, p.lng]).bindPopup(popupHtml(p));
            });
            L.featureGroup(markers).addTo(map);
            // A lone pin gets centred at the closer zoom; otherwise keep the
            // configured default view so the zoom setting takes effect.
            if (pts.length === 1) map.setView([pts[0].lat, pts[0].lng], zoom('zoom-pinned', 15));
        }

        // Mark + centre on the "Near me" origin when present.
        var o = origin();
        if (o) {
            L.circleMarker([o.lat, o.lng], {
                radius: 8, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1
            }).addTo(map).bindPopup('Your location');
            map.setView([o.lat, o.lng], zoom('zoom-pinned', 14));
        }
    });

    // --- Remembered location (cookie) -----------------------------------
    // Store the user's location once they grant it, so "Near me" works on
    // later clicks without re-prompting the browser each time.
    var GEO_COOKIE = 'st_geo';
    var GEO_DAYS = 30;
    // Radius steps the "Near me" button cycles through: near me → 2 → 5 → 10 km.
    var STEPS = [1, 2, 5, 10];

    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }

    function writeCookie(name, value, days) {
        document.cookie = name + '=' + encodeURIComponent(value) +
            '; max-age=' + (days * 24 * 60 * 60) + '; path=/; SameSite=Lax';
    }

    function clearCookie(name) {
        document.cookie = name + '=; max-age=0; path=/; SameSite=Lax';
    }

    // The user's remembered { lat, lng }, or null when none is stored.
    function cachedGeo() {
        var v = readCookie(GEO_COOKIE);
        if (!v) return null;
        var p = v.split(',');
        var lat = parseFloat(p[0]);
        var lng = parseFloat(p[1]);
        return (isNaN(lat) || isNaN(lng)) ? null : { lat: lat, lng: lng };
    }

    // Category dropdown: each option is "param:value" (type or occupancy).
    // Split the selection into the hidden type/occupancy inputs before the
    // filter form submits, then reload so the server filters by category.
    $(function () {
        var $form = $('#map-filters');
        var $cat = $('#category-select');
        if (!$form.length || !$cat.length) return;

        function syncCategory() {
            var parts = ($cat.val() || '').split(':');
            var param = parts[0];
            var value = parts.length > 1 ? parts[1] : '';
            $('#cat-type').val(param === 'type' ? value : '');
            $('#cat-occupancy').val(param === 'occupancy' ? value : '');
        }

        $form.on('submit', syncCategory);
        $cat.on('change', function () { $form.trigger('submit'); });
    });

    // "Near me" button: reuse the remembered location when we have one (no
    // prompt), otherwise ask once and cache it. Each click widens the radius
    // one step. Submitting reloads with lat/lng/radius so the server filters.
    $(function () {
        var $btn = $('#near-me');
        if (!$btn.length) return;

        // Is a "Near me" search already active on this page load?
        function hasActive() {
            return $('#near-lat').val() !== '' && $('#near-lng').val() !== '';
        }

        // The radius the next click should apply: the first step when no
        // near-me search is active yet, otherwise the next step in the cycle.
        function nextRadius() {
            if (!hasActive()) return STEPS[0];
            var i = STEPS.indexOf(parseFloat($('#near-radius').val()));
            return i === -1 ? STEPS[0] : STEPS[(i + 1) % STEPS.length];
        }

        function label(km) {
            return km === 1 ? '📍 Near me' : '📍 Widen to ' + km + ' km';
        }

        function submitNearMe(geo, radius) {
            $('#near-lat').val(geo.lat);
            $('#near-lng').val(geo.lng);
            $('#near-radius').val(radius);
            $('#map-filters').trigger('submit');
        }

        // Hint the next action on the button (the page reloads each click).
        if (hasActive()) $btn.text(label(nextRadius()));

        $btn.on('click', function () {
            var radius = nextRadius();
            var geo = cachedGeo();

            if (geo) {
                submitNearMe(geo, radius);
                return;
            }

            if (!navigator.geolocation) {
                window.alert('Location is not supported by your browser.');
                return;
            }

            var original = $btn.text();
            $btn.prop('disabled', true).text('📍 Locating…');

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    var g = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    writeCookie(GEO_COOKIE, g.lat + ',' + g.lng, GEO_DAYS);
                    submitNearMe(g, radius);
                },
                function () {
                    $btn.prop('disabled', false).text(original);
                    window.alert('Could not get your location. Please allow location access and try again.');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        });

        // Resetting the map also forgets the remembered location.
        $('#map-reset').on('click', function () { clearCookie(GEO_COOKIE); });
    });
})(jQuery);
