/* Admin Manage Listings: preview modal + reject (with message) modal. jQuery. */
(function ($) {
    'use strict';

    $(function () {
        // ---- Preview modal ----
        var $pModal = $('#preview-modal');
        var $pBody = $('#preview-body');
        function openP() { $pModal.addClass('open').attr('aria-hidden', 'false'); }
        function closeP() { $pModal.removeClass('open').attr('aria-hidden', 'true'); }

        $('.preview-btn').on('click', function () {
            $pBody.html('<p class="muted">Loading…</p>');
            openP();
            $.ajax({ url: $(this).data('url'), dataType: 'html' })
                .done(function (html) { $pBody.html(html); })
                .fail(function () { $pBody.html('<p class="alert alert-error">Could not load the preview.</p>'); });
        });
        $('#preview-close').on('click', closeP);
        $pModal.on('click', function (e) { if (e.target === this) closeP(); });

        // ---- Reject modal ----
        var $rModal = $('#reject-modal');
        var $rForm = $('#reject-form');
        var $rTarget = $('#reject-target');
        function openR() { $rModal.addClass('open').attr('aria-hidden', 'false'); }
        function closeR() { $rModal.removeClass('open').attr('aria-hidden', 'true'); }

        // Delegated: works for row buttons AND buttons inside the fetched preview.
        $(document).on('click', '.reject-btn', function () {
            $rForm.attr('action', $(this).data('url'));
            var title = $(this).data('title');
            if (title) $rTarget.text('Why is “' + title + '” being rejected? The owner will see this message.');
            $rForm.find('[name="reason"]').val('');
            closeP();
            openR();
            setTimeout(function () { $rForm.find('[name="reason"]').trigger('focus'); }, 50);
        });
        $('#reject-close, #reject-cancel').on('click', closeR);
        $rModal.on('click', function (e) { if (e.target === this) closeR(); });

        $(document).on('keydown', function (e) { if (e.key === 'Escape') { closeP(); closeR(); } });
    });
})(jQuery);
