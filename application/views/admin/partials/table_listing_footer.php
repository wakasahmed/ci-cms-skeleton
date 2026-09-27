<?php defined('BASEPATH') OR exit('No direct script access allowed');

$listingTotalRows = max(0, isset($total_rows) ? (int) $total_rows : 0);
$listingPerPage = isset($per_page) ? max(0, (int) $per_page) : 0;
$listingSelectedPerPage = isset($selected_per_page) ? max(0, (int) $selected_per_page) : $listingPerPage;
$listingPageOffset = isset($page_offset) ? max(0, (int) $page_offset) : 0;
$listingPagination = isset($pagination) ? (string) $pagination : '';
$listingPaginationLabel = isset($pagination_label) && trim((string) $pagination_label) !== '' ? trim((string) $pagination_label) : 'Listing pagination';
$listingShowPerPage = !isset($show_per_page) || (bool) $show_per_page;
$listingPerPageId = isset($per_page_id) && trim((string) $per_page_id) !== '' ? trim((string) $per_page_id) : 'per_page';
$listingPerPageOptions = isset($per_page_options) && is_array($per_page_options) ? $per_page_options : range(10, 100, 10);

$listingShowingFrom = $listingTotalRows > 0 ? ($listingPerPage === 0 ? 1 : min($listingTotalRows, $listingPageOffset + 1)) : 0;
$listingShowingTo = $listingPerPage === 0 ? $listingTotalRows : min($listingTotalRows, $listingPageOffset + $listingPerPage);
?>
<?php if ($listingTotalRows > 0) { ?>
<footer class="pages-listing-footer mt-2">
    <div class="pages-footer-summary">
        <?php if ($listingShowPerPage) { ?>
        <div class="pages-per-page-control">
            <label for="<?php echo htmlspecialchars($listingPerPageId, ENT_QUOTES, 'UTF-8'); ?>">Rows per page</label>
            <select class="form-select select2 form-select-sm" data-minimum-results-for-search="-1" name="<?php echo htmlspecialchars($listingPerPageId, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($listingPerPageId, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Rows per page">
                <option value="0" <?php echo $listingSelectedPerPage === 0 ? 'selected' : ''; ?>>All</option>
                <?php foreach ($listingPerPageOptions as $listingPerPageOption) { $listingPerPageOption = max(1, (int) $listingPerPageOption); ?>
                <option value="<?php echo $listingPerPageOption; ?>" <?php echo $listingSelectedPerPage === $listingPerPageOption ? 'selected' : ''; ?>><?php echo $listingPerPageOption; ?></option>
                <?php } ?>
            </select>
        </div>
        <?php } ?>
        <p>Showing <?php echo $listingShowingFrom; ?>&ndash;<?php echo $listingShowingTo; ?> of <?php echo $listingTotalRows; ?> record<?php echo $listingTotalRows === 1 ? '' : 's'; ?></p>
    </div>
    <?php if ($listingPagination !== '') { ?><nav class="pages-pagination" aria-label="<?php echo htmlspecialchars($listingPaginationLabel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $listingPagination; ?></nav><?php } ?>
</footer>
<?php } ?>
