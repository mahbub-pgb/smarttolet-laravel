/* Listings map view: plot all listings, info window on click. jQuery-based. */
(function ($) {
    'use strict';

    function fmt(n) { return Number(n).toLocaleString(); }

    function points() {
        var el = document.getElementById('map-points');
        try { return el ? JSON.parse(el.textContent || '[]') : []; }
        catch (e) { return []; }
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
            center: { lat: 23.8103, lng: 90.4125 }, // Dhaka
            zoom: 12,
            mapTypeControl: false,
            streetViewControl: false
        });
        var info = new google.maps.InfoWindow();
        var bounds = new google.maps.LatLngBounds();

        $.each(pts, function (_, p) {
            var pos = { lat: p.lat, lng: p.lng };
            var marker = new google.maps.Marker({ position: pos, map: map, title: p.title });
            bounds.extend(pos);
            marker.addListener('click', function () {
                info.setContent(popupHtml(p));
                info.open(map, marker);
            });
        });

        if (pts.length) {
            map.fitBounds(bounds);
            if (pts.length === 1) map.setZoom(15);
        }
    };

    // Leaflet fallback (used when no Google Maps key is configured).
    $(function () {
        var $map = $('#map');
        if (!$map.length || $map.data('maps') !== 'leaflet') return;

        var pts = points();
        var map = L.map('map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        if (pts.length) {
            var markers = $.map(pts, function (p) {
                return L.marker([p.lat, p.lng]).bindPopup(popupHtml(p));
            });
            var group = L.featureGroup(markers).addTo(map);
            map.fitBounds(group.getBounds().pad(0.2));
        } else {
            map.setView([23.8103, 90.4125], 12);
        }
    });
})(jQuery);
