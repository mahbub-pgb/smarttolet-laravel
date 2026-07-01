/* Dual-thumb rent range slider. Shared by the listings filter and map views. jQuery-based. */
(function ($) {
    'use strict';

    // Mirror the two range thumbs into the hidden min_rent / max_rent inputs
    // (and a readable label). At the extremes the value is left blank so it
    // doesn't over-filter ("any" min / max).
    $(function () {
        var $wrap = $('.rent-slider');
        if (!$wrap.length) return;

        var floor = parseInt($wrap.data('min'), 10) || 0;
        var ceil = parseInt($wrap.data('max'), 10) || 0;
        var $min = $('#rent-min');
        var $max = $('#rent-max');
        var $minOut = $('#rent-min-out');
        var $maxOut = $('#rent-max-out');
        var $minInput = $('#rent-min-input');
        var $maxInput = $('#rent-max-input');
        var $range = $('#rent-range');

        function fmt(n) { return Number(n).toLocaleString(); }
        function pct(v) { return ceil > floor ? ((v - floor) / (ceil - floor)) * 100 : 0; }

        function refresh(active) {
            var lo = parseInt($min.val(), 10);
            var hi = parseInt($max.val(), 10);

            // Keep the thumbs from crossing each other.
            if (lo > hi) {
                if (active === 'min') { lo = hi; $min.val(lo); }
                else { hi = lo; $max.val(hi); }
            }

            $minOut.text(fmt(lo));
            $maxOut.text(hi >= ceil ? fmt(ceil) + '+' : fmt(hi));
            $range.css({ left: pct(lo) + '%', right: (100 - pct(hi)) + '%' });

            $minInput.val(lo <= floor ? '' : lo);
            $maxInput.val(hi >= ceil ? '' : hi);
        }

        $min.on('input', function () { refresh('min'); });
        $max.on('input', function () { refresh('max'); });
        refresh();
    });
})(jQuery);
