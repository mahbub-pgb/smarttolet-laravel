/* Form UX helpers to avoid accidental rate-limit hits.
   - form[data-once]              : disable the submit button after the first
                                     click so a double/triple-click can't fire
                                     several requests.
   - button[data-resend-countdown]: disable + count down (seconds) so users
                                     can't spam a resend before the cooldown.
   jQuery. */
(function ($) {
    'use strict';

    $(function () {
        // Prevent double-submits.
        $(document).on('submit', 'form[data-once]', function () {
            var $btn = $(this).find('[type="submit"]').first();
            // The current submit is already in flight; locking the button now
            // just blocks any further clicks.
            $btn.prop('disabled', true);
            var busy = $btn.data('busy-text');
            if (busy) { $btn.text(busy); }
        });

        // Resend cooldown countdown.
        $('[data-resend-countdown]').each(function () {
            var $btn = $(this);
            var secs = parseInt($btn.data('resend-countdown'), 10) || 0;
            if (secs <= 0) { return; }

            var label = ($btn.text() || 'Resend code').trim();

            (function tick() {
                if (secs <= 0) {
                    $btn.prop('disabled', false).text(label);
                    return;
                }
                $btn.prop('disabled', true).text(label + ' (' + secs + 's)');
                secs -= 1;
                setTimeout(tick, 1000);
            })();
        });
    });
})(jQuery);
