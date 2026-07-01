/* Styled confirmation popup for destructive actions. Any
   `<form data-confirm="message">` is intercepted here: the form only submits
   after the user confirms in the modal (replaces the native confirm()).

   Optional per-form attributes:
     data-confirm-title   — heading (default "Please confirm")
     data-confirm-action  — confirm-button label (default "Delete")
   jQuery. */
(function ($) {
    'use strict';

    $(function () {
        var $modal = $('#confirm-modal');
        if (!$modal.length) { return; }

        var $title = $('#confirm-title');
        var $message = $('#confirm-message');
        var $accept = $('#confirm-accept');
        var pendingForm = null;

        function open(form) {
            pendingForm = form;
            var $f = $(form);
            $message.text($f.data('confirm') || 'Are you sure?');
            $title.text($f.data('confirm-title') || 'Please confirm');
            $accept.text($f.data('confirm-action') || 'Delete');
            $modal.addClass('open').attr('aria-hidden', 'false');
            $accept.trigger('focus');
        }

        function close() {
            pendingForm = null;
            $modal.removeClass('open').attr('aria-hidden', 'true');
        }

        // Intercept the submit of any form flagged for confirmation.
        $(document).on('submit', 'form[data-confirm]', function (e) {
            e.preventDefault();
            open(this);
        });

        // Confirm → submit the form natively (does not re-fire the handler above).
        $accept.on('click', function () {
            if (!pendingForm) { return; }
            var form = pendingForm;
            close();
            form.submit();
        });

        // Cancel via button, backdrop click, or Escape.
        $modal.on('click', '[data-confirm-cancel]', close);
        $modal.on('click', function (e) { if (e.target === this) { close(); } });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $modal.hasClass('open')) { close(); }
        });
    });
})(jQuery);
