/* Quick-filter "Near me" chip on the listings page: toggles a geolocation
   radius filter into the query string. jQuery. */
(function ($) {
    'use strict';

    $(function () {
        $('#quick-near-me').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);

            // Already active → clear the geo filter.
            if ($btn.data('active') === 1 || $btn.data('active') === '1') {
                window.location.href = $btn.data('clear-url');
                return;
            }

            if (!navigator.geolocation) {
                alert('Your browser does not support location.');
                return;
            }

            var original = $btn.text();
            $btn.text('Locating…');

            navigator.geolocation.getCurrentPosition(function (pos) {
                var u = new URL(window.location.href);
                u.searchParams.set('lat', pos.coords.latitude.toFixed(6));
                u.searchParams.set('lng', pos.coords.longitude.toFixed(6));
                u.searchParams.set('radius', '5');
                u.searchParams.delete('page');
                window.location.href = u.toString();
            }, function () {
                $btn.text(original);
                alert('Could not get your location. Please allow location access.');
            });
        });
    });
})(jQuery);
