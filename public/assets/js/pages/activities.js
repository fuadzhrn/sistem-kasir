(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('[data-activity-filters]');

        if (!form) {
            return;
        }

        var from = form.querySelector('[name="date_from"]');
        var to = form.querySelector('[name="date_to"]');

        if (from && to) {
            from.addEventListener('change', function () {
                to.min = from.value;
            });
            to.min = from.value;
        }
    });
})();
