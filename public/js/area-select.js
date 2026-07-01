/* Area filter: searchable dropdown via Select2. jQuery-based. */
(function ($) {
    'use strict';

    $(function () {
        var $areas = $('.js-area-select');
        if (!$areas.length || typeof $.fn.select2 !== 'function') {
            return;
        }

        $areas.each(function () {
            var $el = $(this);
            var tags = $el.data('tags') === true;      // allow typing a new area
            var required = $el.prop('required');

            $el.select2({
                width: '100%',
                tags: tags,                             // free-text entry when enabled
                allowClear: !required,                  // required pickers keep a value
                placeholder: $el.data('placeholder') || 'Select an area',
            });
        });
    });
})(jQuery);
