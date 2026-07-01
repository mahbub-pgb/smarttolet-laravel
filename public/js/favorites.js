/* Favourite (❤️) toggle on listing cards. The button sits inside the card's
   <a>, so we stop the click from navigating and POST the toggle via AJAX.
   jQuery, matching the rest of the site. */
(function ($) {
    'use strict';

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    $(function () {
        // Delegated so it works for cards rendered on any page.
        $(document).on('click', 'button.fav-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            if ($btn.data('busy')) { return; }
            $btn.data('busy', true);

            $.ajax({
                url: $btn.data('url'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
            }).done(function (res) {
                var on = !!(res && res.favorited);
                $btn.toggleClass('is-fav', on).attr('aria-pressed', on ? 'true' : 'false');

                // Labelled variant (the wide "Save" button on the detail page).
                var labelOn = $btn.data('label-on');
                var labelOff = $btn.data('label-off');
                if (labelOn && labelOff) {
                    $btn.text(on ? labelOn : labelOff);
                }
            }).fail(function (xhr) {
                if (xhr.status === 401 || xhr.status === 419) {
                    window.location.href = '/login';
                }
            }).always(function () {
                $btn.removeData('busy');
            });
        });
    });
})(jQuery);
