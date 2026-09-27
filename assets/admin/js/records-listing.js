/*
 * Shared behaviour for manage/admin record listings.
 *
 * Filters: a form marked [data-records-filter] navigates to
 *   data-filter-base-url + '/' + each [data-filter-segment] value (in order)
 *   + '/' + the [data-filter-keyword] value.
 * Empty values become "-", matching the controllers' route defaults.
 *
 * Bulk selection: a listing marked [data-records-listing] shows its
 * [data-bulk-actions] bar and [data-selection-count] text while any
 * .cselect checkbox is ticked. Select-all and the delete confirmation are
 * handled by custom.js.
 */
(function (window, document, $) {
    'use strict';

    function segmentValue(value) {
        var trimmed = $.trim(String(value || ''));

        return trimmed === '' ? '-' : encodeURIComponent(trimmed);
    }

    function initializeFilters(form) {
        var $form = $(form);
        var baseUrl = $form.attr('data-filter-base-url');

        if (!baseUrl) {
            return;
        }

        function applyFilters() {
            var parts = [baseUrl];

            $form.find('[data-filter-segment]').each(function () {
                parts.push(segmentValue($(this).val()));
            });

            parts.push(segmentValue($form.find('[data-filter-keyword]').val()));
            window.location = parts.join('/');
        }

        $form.on('submit', function (event) {
            event.preventDefault();
            applyFilters();
        });

        $form.find('select[data-filter-segment]').on('change', applyFilters);
    }

    function initializeSelection(listing) {
        var $listing = $(listing);
        var $rows = $listing.find('.cselect');
        var $all = $listing.find('#all-checkbox');
        var $bulk = $listing.find('[data-bulk-actions]');
        var $count = $listing.find('[data-selection-count]');
        var $delete = $listing.find('#deleteAllRecords');

        if (!$bulk.length) {
            return;
        }

        function updateSelection() {
            var selected = $rows.filter(':checked').length;

            $count.text(selected + ' selected');
            $delete.prop('disabled', selected === 0);
            $bulk.prop('hidden', selected === 0);
            $all
                .prop('checked', $rows.length > 0 && selected === $rows.length)
                .prop('indeterminate', selected > 0 && selected < $rows.length)
                .prop('disabled', $rows.length === 0);
        }

        $rows.on('change', updateSelection);
        $all.on('change', function () {
            window.setTimeout(updateSelection, 0);
        });
        updateSelection();
    }

    $(function () {
        document.querySelectorAll('[data-records-filter]').forEach(initializeFilters);
        document.querySelectorAll('[data-records-listing]').forEach(initializeSelection);
    });
})(window, document, window.jQuery);
