(function (window, document, $) {
    'use strict';

    if (!$ || !$.fn.tableDnD) {
        return;
    }

    function recordIds($table) {
        return $table.find('tbody tr[data-record-id]').map(function () {
            return String($(this).data('record-id'));
        }).get();
    }

    function restoreOrder($table, order) {
        var rows = {};
        $table.find('tbody tr[data-record-id]').each(function () {
            rows[String($(this).data('record-id'))] = this;
        });
        order.forEach(function (id) {
            if (rows[id]) {
                $table.children('tbody').append(rows[id]);
            }
        });
    }

    $('[data-sortable-records]').each(function () {
        var $table = $(this);
        var request = null;
        var originalOrder = [];
        var $status = $('<span class="visually-hidden" role="status" aria-live="polite"></span>').insertAfter($table);

        $table.addClass('admin-sortable-table').tableDnD({
            onDragClass: 'admin-sortable-row-dragging',
            dragHandle: $table.data('sort-handle') || null,
            onDragStart: function () {
                originalOrder = recordIds($table);
                $status.text('Moving record.');
            },
            onDrop: function () {
                if (request) {
                    request.abort();
                }

                var payload = {
                    records: recordIds($table),
                    offset: parseInt($table.data('sort-offset'), 10) || 0
                };
                var scopeValue = $table.data('sort-scope');
                if (scopeValue !== undefined && scopeValue !== null && scopeValue !== '') {
                    payload.scope = scopeValue;
                }

                request = $.ajax({
                    url: $table.data('sort-url'),
                    method: 'POST',
                    dataType: 'json',
                    data: payload
                }).done(function (response) {
                    $status.text(response.message || 'The record order was saved.');
                }).fail(function (xhr, status) {
                    if (status === 'abort') {
                        return;
                    }
                    restoreOrder($table, originalOrder);
                    var response = xhr.responseJSON || {};
                    $status.text(response.message || 'The record order could not be saved.');
                }).always(function () {
                    request = null;
                });
            }
        });
    });
}(window, document, window.jQuery));
