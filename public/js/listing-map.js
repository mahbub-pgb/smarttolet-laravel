/* Listings map view: plot all listings, info window on click. jQuery-based. */
(function ($) {
    'use strict';

    // Set once the map is built (Google or Leaflet). The "Near me" button uses
    // it to drop a "you are here" marker and pan to the user's location, which
    // brings the nearby listing clusters into view.
    var mapApi = null;
    var userMarker = null;

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

    // --- "Near me" persistence (cookie) ---------------------------------
    // Remember the user's location and that "Near me" is on, so the state
    // survives reloads (including Apply and Clear all) without re-prompting.
    var NEAR_COOKIE = 'st_nearme';
    var NEAR_DAYS = 30;

    function writeNearCookie(lat, lng) {
        document.cookie = NEAR_COOKIE + '=' + encodeURIComponent(lat + ',' + lng) +
            '; max-age=' + (NEAR_DAYS * 24 * 60 * 60) + '; path=/; SameSite=Lax';
    }

    function clearNearCookie() {
        document.cookie = NEAR_COOKIE + '=; max-age=0; path=/; SameSite=Lax';
    }

    // The remembered { lat, lng } when "Near me" is active, or null.
    function readNearCookie() {
        var m = document.cookie.match(new RegExp('(?:^|; )' + NEAR_COOKIE + '=([^;]*)'));
        if (!m) return null;
        var p = decodeURIComponent(m[1]).split(',');
        var lat = parseFloat(p[0]);
        var lng = parseFloat(p[1]);
        return (isNaN(lat) || isNaN(lng)) ? null : { lat: lat, lng: lng };
    }

    // Re-apply a stored "Near me" focus once the map (mapApi) is ready.
    function applyStoredNearMe() {
        var geo = readNearCookie();
        if (geo && mapApi) {
            mapApi.markUser(geo.lat, geo.lng);
            mapApi.focus(geo.lat, geo.lng);
        }
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

        // Build all markers first. With thousands of listings we group them
        // with MarkerClusterer (when its library is present) so the map stays
        // responsive; otherwise we fall back to dropping every marker directly.
        var markers = $.map(pts, function (p) {
            var marker = new google.maps.Marker({ position: { lat: p.lat, lng: p.lng }, title: p.title });
            marker.addListener('click', function () {
                info.setContent(popupHtml(p));
                info.open(map, marker);
            });
            return marker;
        });

        if (window.markerClusterer && markers.length) {
            new markerClusterer.MarkerClusterer({ map: map, markers: markers });
        } else {
            $.each(markers, function (_, m) { m.setMap(map); });
        }

        // Expose pan + user-marker hooks for the "Near me" button.
        mapApi = {
            focus: function (lat, lng) {
                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(zoom('zoom-pinned', 14));
            },
            markUser: function (lat, lng) {
                if (userMarker) userMarker.setMap(null);
                userMarker = new google.maps.Marker({
                    position: { lat: lat, lng: lng },
                    map: map,
                    title: 'You are here',
                    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8, fillColor: '#2563eb', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 }
                });
            },
            reset: function () {
                if (userMarker) { userMarker.setMap(null); userMarker = null; }
                map.setCenter(center());
                map.setZoom(zoom('zoom', 12));
            }
        };

        applyStoredNearMe();

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
            // Cluster markers when the markercluster plugin is loaded, so a
            // dense map (thousands of pins) stays usable; otherwise plain group.
            var group = (typeof L.markerClusterGroup === 'function')
                ? L.markerClusterGroup()
                : L.featureGroup();
            $.each(pts, function (_, p) {
                group.addLayer(L.marker([p.lat, p.lng]).bindPopup(popupHtml(p)));
            });
            group.addTo(map);
            // A lone pin gets centred at the closer zoom; otherwise keep the
            // configured default view so the zoom setting takes effect.
            if (pts.length === 1) map.setView([pts[0].lat, pts[0].lng], zoom('zoom-pinned', 15));
        }

        // Expose pan + user-marker hooks for the "Near me" button.
        mapApi = {
            focus: function (lat, lng) { map.setView([lat, lng], zoom('zoom-pinned', 14)); },
            markUser: function (lat, lng) {
                if (userMarker) map.removeLayer(userMarker);
                userMarker = L.circleMarker([lat, lng], {
                    radius: 8, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1
                }).addTo(map).bindPopup('You are here');
            },
            reset: function () {
                if (userMarker) { map.removeLayer(userMarker); userMarker = null; }
                var c = center();
                map.setView([c.lat, c.lng], zoom('zoom', 12));
            }
        };

        applyStoredNearMe();
    });

    // Category dropdown: each option is "param:value" (type or occupancy).
    // Split the selection into the hidden type/occupancy inputs when the filter
    // form is submitted (via Apply), so it applies alongside the other filters.
    $(function () {
        var $form = $('#map-filters');
        var $cat = $('#category-select');
        if (!$form.length || !$cat.length) return;

        $form.on('submit', function () {
            var parts = ($cat.val() || '').split(':');
            var param = parts[0];
            var value = parts.length > 1 ? parts[1] : '';
            $('#cat-type').val(param === 'type' ? value : '');
            $('#cat-occupancy').val(param === 'occupancy' ? value : '');
        });
    });

    // Dual-thumb rent slider: mirror the two range thumbs into the hidden
    // min_rent / max_rent inputs (and a readable label). At the extremes the
    // value is left blank so it doesn't over-filter ("any" min / max).
    $(function () {
        var $wrap = $('.rent-slider');
        if (!$wrap.length) return;

        var floor = parseInt($wrap.data('min'), 10) || 0;
        var ceil = parseInt($wrap.data('max'), 10) || 0;
        var $min = $('#rent-min');
        var $max = $('#rent-max');
        var $minOut = $('#rent-min-out');
        var $maxOut = $('#rent-max-out');
        var $minInput = $('#rent-min-input');
        var $maxInput = $('#rent-max-input');
        var $range = $('#rent-range');

        function fmt(n) { return Number(n).toLocaleString(); }
        function pct(v) { return ceil > floor ? ((v - floor) / (ceil - floor)) * 100 : 0; }

        function refresh(active) {
            var lo = parseInt($min.val(), 10);
            var hi = parseInt($max.val(), 10);

            // Keep the thumbs from crossing each other.
            if (lo > hi) {
                if (active === 'min') { lo = hi; $min.val(lo); }
                else { hi = lo; $max.val(hi); }
            }

            $minOut.text(fmt(lo));
            $maxOut.text(hi >= ceil ? fmt(ceil) + '+' : fmt(hi));
            $range.css({ left: pct(lo) + '%', right: (100 - pct(hi)) + '%' });

            $minInput.val(lo <= floor ? '' : lo);
            $maxInput.val(hi >= ceil ? '' : hi);
        }

        $min.on('input', function () { refresh('min'); });
        $max.on('input', function () { refresh('max'); });
        refresh();
    });

    // "Near me" checkbox: when checked, ask the browser for the user's location,
    // mark it on the map and pan there so the nearby listing clusters come into
    // view. Unchecking resets the map to the default view (all listings). All
    // listings are already plotted, so this is purely a client-side focus.
    $(function () {
        var $chk = $('#near-me');
        if (!$chk.length) return;

        // Restore the checkbox state from a previous "Near me" session. The map
        // itself is re-focused by applyStoredNearMe() once mapApi is ready.
        if (readNearCookie()) $chk.prop('checked', true);

        $chk.on('change', function () {
            if (!this.checked) {
                clearNearCookie();
                if (mapApi) mapApi.reset();
                return;
            }

            if (!navigator.geolocation) {
                window.alert('Location is not supported by your browser.');
                $chk.prop('checked', false);
                return;
            }

            $chk.prop('disabled', true);

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    writeNearCookie(lat, lng);
                    if (mapApi) {
                        mapApi.markUser(lat, lng);
                        mapApi.focus(lat, lng);
                    }
                    $chk.prop('disabled', false);
                },
                function () {
                    $chk.prop('disabled', false).prop('checked', false);
                    window.alert('Could not get your location. Please allow location access and try again.');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        });
    });
})(jQuery);
