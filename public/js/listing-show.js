/* Single listing page: gallery + lightbox, map, get-directions. jQuery-based. */
(function ($) {
    'use strict';

    // ---- Gallery + lightbox (with keyboard + arrow navigation) ----
    $(function () {
        var $lead = $('#gallery-lead');
        if (!$lead.length) return;

        // All gallery image sources, in order (thumbnails render only when > 1 image).
        var images = $('.gthumb').map(function () { return $(this).data('src'); }).get();
        if (!images.length) images = [$lead.attr('src')];
        var current = 0;

        var $lb = $('#lightbox');
        var $lbImg = $('#lightbox-img');

        // Hide the prev/next arrows when there's nothing to navigate.
        if (images.length <= 1) $('#lightbox-prev, #lightbox-next').hide();

        function wrap(i) { return (i + images.length) % images.length; }

        // Update the main gallery image (thumbnail click).
        function setLead(i) {
            current = wrap(i);
            $lead.attr('src', images[current]);
            $('.gthumb').removeClass('active').eq(current).addClass('active');
        }

        // Show a given index inside the open lightbox.
        function showLb(i) {
            current = wrap(i);
            $lbImg.attr('src', images[current]);
        }
        function openLb() { $lb.addClass('open'); showLb(current); }
        function closeLb() { $lb.removeClass('open'); }

        $('.gthumb').on('click', function () { setLead($(this).index()); });
        $lead.on('click', openLb);

        $('#lightbox-close').on('click', closeLb);
        $('#lightbox-prev').on('click', function (e) { e.stopPropagation(); showLb(current - 1); });
        $('#lightbox-next').on('click', function (e) { e.stopPropagation(); showLb(current + 1); });
        $lb.on('click', function (e) { if (e.target === this) closeLb(); });

        // Keyboard: Esc closes, ←/→ navigate — only while the lightbox is open.
        $(document).on('keydown', function (e) {
            if (!$lb.hasClass('open')) return;
            if (e.key === 'Escape') { closeLb(); }
            else if (e.key === 'ArrowLeft') { showLb(current - 1); }
            else if (e.key === 'ArrowRight') { showLb(current + 1); }
        });
    });

    function dest() {
        var $map = $('#map');
        return { lat: parseFloat($map.data('lat')), lng: parseFloat($map.data('lng')) };
    }

    // Admin-configured zoom (data-zoom on #map), with a sensible fallback.
    function mapZoom(fallback) {
        var v = parseInt($('#map').data('zoom'), 10);
        return isNaN(v) ? fallback : v;
    }

    // ---- Google Maps + directions (loader ?callback=) ----
    window.initShowMap = function () {
        var $map = $('#map');
        if (!$map.length) return;
        var d = dest();
        var map = new google.maps.Map($map[0], { center: d, zoom: mapZoom(15), mapTypeControl: false, streetViewControl: false });
        var marker = new google.maps.Marker({ map: map, position: d, title: $map.data('title') });
        var info = new google.maps.InfoWindow({ content: String($map.data('title') || '') });
        marker.addListener('click', function () { info.open(map, marker); });

        var dirSvc = new google.maps.DirectionsService();
        var dirRender = new google.maps.DirectionsRenderer({ map: map });
        var $info = $('#dir-info');

        $('#get-directions').on('click', function () {
            if (!navigator.geolocation) { alert('Geolocation is not supported by your browser.'); return; }
            $info.show().text('Locating you…');
            navigator.geolocation.getCurrentPosition(function (pos) {
                var origin = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                dirSvc.route({ origin: origin, destination: d, travelMode: google.maps.TravelMode.DRIVING }, function (res, status) {
                    if (status === 'OK') {
                        dirRender.setDirections(res);
                        var leg = res.routes[0].legs[0];
                        $info.text('🚗 ' + leg.distance.text + ' · about ' + leg.duration.text + ' from your location.');
                    } else {
                        $info.text('Could not calculate directions.');
                    }
                });
            }, function () { $info.text('Could not get your location. Please allow location access.'); });
        });
    };

    // ---- Leaflet fallback (no Google Maps key) ----
    $(function () {
        var $map = $('#map');
        if (!$map.length || $map.data('maps') !== 'leaflet') return;

        var d = dest();
        var map = L.map('map').setView([d.lat, d.lng], mapZoom(15));
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        L.marker([d.lat, d.lng]).addTo(map).bindPopup(String($map.data('title') || '')).openPopup();

        $('#get-directions').on('click', function () {
            window.open('https://www.google.com/maps/dir/?api=1&destination=' + d.lat + ',' + d.lng, '_blank');
        });
    });
})(jQuery);
