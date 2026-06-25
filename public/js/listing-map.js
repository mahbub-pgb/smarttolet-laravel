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
})(jQuery);
