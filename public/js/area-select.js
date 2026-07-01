/* Area filter: searchable dropdown via Select2. jQuery-based. */
(function ($) {
    'use strict';

    $(function () {
        var $areas = $('.js-area-select');
        if (!$areas.length || typeof $areas.select2 !== 'function') {
            return;
        }

        $areas.select2({
            width: '100%',
            allowClear: true,
            placeholder: $areas.data('placeholder') || 'Select an area',
        });
    });
})(jQuery);
