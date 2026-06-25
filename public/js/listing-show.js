/* Single listing page: gallery + lightbox, map, get-directions. jQuery-based. */
(function ($) {
    'use strict';

    // ---- Gallery + lightbox ----
    $(function () {
        var $lead = $('#gallery-lead');
        if (!$lead.length) return;

        $('.gthumb').on('click', function () {
            $lead.attr('src', $(this).data('src'));
            $('.gthumb').removeClass('active');
            $(this).addClass('active');
        });

        var $lb = $('#lightbox');
        var $lbImg = $('#lightbox-img');
        $lead.on('click', function () { $lbImg.attr('src', $lead.attr('src')); $lb.addClass('open'); });
        $('#lightbox-close').on('click', function () { $lb.removeClass('open'); });
        $lb.on('click', function (e) { if (e.target === this) $lb.removeClass('open'); });
    });

    function dest() {
        var $map = $('#map');
        return { lat: parseFloat($map.data('lat')), lng: parseFloat($map.data('lng')) };
    }

    // ---- Google Maps + directions (loader ?callback=) ----
    window.initShowMap = function () {
        var $map = $('#map');
        if (!$map.length) return;
        var d = dest();
        var map = new google.maps.Map($map[0], { center: d, zoom: 15, mapTypeControl: false, streetViewControl: false });
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
        var map = L.map('map').setView([d.lat, d.lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        L.marker([d.lat, d.lng]).addTo(map).bindPopup(String($map.data('title') || '')).openPopup();

        $('#get-directions').on('click', function () {
            window.open('https://www.google.com/maps/dir/?api=1&destination=' + d.lat + ',' + d.lng, '_blank');
        });
    });
})(jQuery);
