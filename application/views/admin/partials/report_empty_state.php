<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Empty table row: either nothing exists yet, or nothing matches the filters.
 * Expects $columns (colspan).
 */
?>
<tr>
    <td class="pages-empty-state" colspan="<?php echo (int) $columns; ?>">
        <?php if ($has_active_filters && $has_any_records) { ?>
            <strong>No records match these filters.</strong>
            <span>Try removing a filter or widening the date range. <a class="pages-clear-filters" href="<?php echo report_e($clear_url); ?>">Clear all filters</a></span>
        <?php } else { ?>
            <strong>No records exist for this report yet.</strong>
            <span>Bookings will appear here once customers start reserving tours.</span>
        <?php } ?>
    </td>
</tr>
