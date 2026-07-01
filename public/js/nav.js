/* Public header: mobile hamburger toggle. On small screens the nav links are
   hidden behind the ☰ button (see .nav-toggle / .nav-links.open in app.css).
   jQuery, matching the rest of the site's front-end. */
(function ($) {
    'use strict';

    $(function () {
        var $toggle = $('#nav-toggle');
        var $nav = $('#primary-nav');
        if (!$toggle.length || !$nav.length) { return; }

        function setOpen(open) {
            $nav.toggleClass('open', open);
            $toggle.toggleClass('open', open).attr('aria-expanded', open ? 'true' : 'false');
        }

        $toggle.on('click', function (e) {
            e.stopPropagation();
            setOpen(!$nav.hasClass('open'));
        });

        // Close when tapping outside the menu or pressing Escape.
        $(document).on('click', function (e) {
            if ($nav.hasClass('open') &&
                !$(e.target).closest('#primary-nav, #nav-toggle').length) {
                setOpen(false);
            }
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') { setOpen(false); }
        });
    });
})(jQuery);
