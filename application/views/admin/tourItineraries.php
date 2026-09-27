<?php
$hasActiveFilters = $keywords !== '-' || $status !== '-';
$tourName = htmlspecialchars((string) $tourData['tour_name'], ENT_QUOTES, 'UTF-8');
$sortUrl = function ($column) use ($itinerary_tour_id, $order, $status, $keywords, $page_numb) {
    return base_url('manage/tour-itineraries/index/'.$itinerary_tour_id.'/'.$column.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.$page_numb);
};
$sortIcon = function ($column) use ($sortby, $order) {
    return $sortby !== $column ? 'bi-arrow-down-up' : ($order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down');
};
$sortAria = function ($column) use ($sortby, $order) {
    if ($sortby !== $column) return '';
    return $order === 'DESC' ? 'ascending' : 'descending';
};
$sortingEnabled = !$hasActiveFilters && $sortby === 'itinerary_order' && $order === 'DESC';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Tours', 'url' => ADMIN_URL.'tours'), array('label' => $tourData['tour_name'].' Itineraries', 'active' => TRUE)))); ?>
<?php $this->load->view('admin/partials/crud_alert', array('module_name' => 'Itinerary', 'status' => $alert)); ?>
<section class="admin-records-listing" aria-labelledby="tour-itineraries-title" data-translation-poll data-module="tour_itineraries" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4">
    <?php $this->load->view('admin/partials/module_header', array('title' => $tourData['tour_name'].' Itineraries', 'description' => $this->moduleDesc, 'id' => 'tour-itineraries-title', 'action_url' => base_url('manage/tour-itineraries/control/'.$itinerary_tour_id), 'action_label' => 'Add '.$this->moduleNameSingular)); ?>
    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="tour-itineraries-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search itineraries</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search title, day/hour..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control"><label class="visually-hidden" for="search_status">Filter by status</label><select class="form-select select2" id="search_status" data-minimum-results-for-search="-1">
                <option value="-">All Statuses</option>
                <option value="Enable"<?php echo $status === 'Enable' ? ' selected' : ''; ?>>Enabled</option>
                <option value="Disable"<?php echo $status === 'Disable' ? ' selected' : ''; ?>>Disabled</option>
            </select></div>
        </form>
    </div>
    <div class="pages-bulk-actions" id="tour-itineraries-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="tour-itineraries-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button></div>
    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" id="multiDel">
        <input type="hidden" name="tour_id" value="<?php echo (int) $itinerary_tour_id; ?>">
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Tour itineraries table">
            <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table"<?php if ($sortingEnabled) { ?> data-sortable-records data-sort-handle=".pages-drag-handle" data-sort-url="<?php echo base_url('manage/record-sorting/sort/'.$this->controller); ?>" data-sort-offset="<?php echo (int) $page_numb; ?>" data-sort-scope="<?php echo (int) $itinerary_tour_id; ?>"<?php } ?>>
                <thead>
                    <tr>
                        <?php if ($sortingEnabled) { ?><th scope="col"><span class="visually-hidden">Reorder</span></th><?php } ?>
                        <th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all itineraries"></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria($this->pKey) !== '') { ?> aria-sort="<?php echo $sortAria($this->pKey); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($this->pKey); ?>">ID <i class="bi <?php echo $sortIcon($this->pKey); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col">Image</th>
                        <th scope="col"<?php if ($sortAria('title') !== '') { ?> aria-sort="<?php echo $sortAria('title'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('title'); ?>">Itinerary <i class="bi <?php echo $sortIcon('title'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col">Attraction</th>
                        <th scope="col"<?php if ($sortAria($this->tStatus) !== '') { ?> aria-sort="<?php echo $sortAria($this->tStatus); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($this->tStatus); ?>">Status <i class="bi <?php echo $sortIcon($this->tStatus); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col">Translation</th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('added_on') !== '') { ?> aria-sort="<?php echo $sortAria('added_on'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('added_on'); ?>">Created On <i class="bi <?php echo $sortIcon('added_on'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('updated_on') !== '') { ?> aria-sort="<?php echo $sortAria('updated_on'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('updated_on'); ?>">Updated On <i class="bi <?php echo $sortIcon('updated_on'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)) { foreach ($records as $record) {
                        $id = (int) $record[$this->pKey];
                        $title = htmlspecialchars((string) $record['title'], ENT_QUOTES, 'UTF-8');
                        $dayHour = htmlspecialchars((string) $record['day_hour'], ENT_QUOTES, 'UTF-8');
                        $editUrl = base_url('manage/'.$this->controller.'/control/'.$itinerary_tour_id.'/'.$id);
                        $attractionId = !empty($record['attraction_id']) ? (int) $record['attraction_id'] : 0;
                        $attractionName = $attractionId > 0 && isset($attraction_names[$attractionId]) ? htmlspecialchars((string) $attraction_names[$attractionId], ENT_QUOTES, 'UTF-8') : '';
                    ?>
                        <tr id="<?php echo $this->controller.'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                            <?php if ($sortingEnabled) { ?><td class="pages-drag-handle" title="Drag to reorder <?php echo $title; ?>" aria-label="Drag to reorder <?php echo $title; ?>"><i class="bi bi-grip-vertical" aria-hidden="true"></i></td><?php } ?>
                            <td class="pages-select-column"><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $title; ?>"></td>
                            <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                            <td><?php echo image_thumb('assets/frontend/images/'.$this->controller.'/'.basename((string) $record['image']), 96, 96, 'webp', 'square', TRUE, array('alt' => $record['title'], 'placeholder' => TRUE, 'size' => 56, 'group' => 'tour-itineraries', 'class' => 'admin-table-image')); ?></td>
                            <td><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $title; ?></a><div class="pages-cell-meta"><?php echo $dayHour; ?></div></td>
                            <td><?php echo $attractionName !== '' ? $attractionName : '<span class="text-muted">No attraction</span>'; ?></td>
                            <td><button type="button" class="changestatus pages-status-button" data-controller="<?php echo $this->controller; ?>" id="statusID<?php echo $id; ?>" aria-label="Change status for <?php echo $title; ?>" title="Change status" data-bs-toggle="tooltip"><?php echo htmlspecialchars($record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button></td>
                            <td><?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => isset($translation_statuses[(string) $id]) ? $translation_statuses[(string) $id] : 'MISSING', 'translation_entity_id' => $id)); ?></td>
                            <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record['added_on'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['added_on'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['added_on'])); ?></span></time></td>
                            <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record['updated_on'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['updated_on'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['updated_on'])); ?></span></time></td>
                            <td class="pages-actions-cell">
                                <a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $title; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                <a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo $this->controller; ?>" data-record-name="<?php echo $title; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $title; ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                    <?php } } else { ?>
                        <tr class="nodrag">
                            <td class="pages-empty-state" colspan="<?php echo $sortingEnabled ? 10 : 9; ?>">
                                <strong><?php echo $hasActiveFilters ? 'No itineraries match your filters.' : 'No itineraries have been added for '.$tourName.'.'; ?></strong>
                                <span><?php echo $hasActiveFilters ? 'Try changing your search or status filter.' : 'Add an itinerary item to get started.'; ?></span>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php $this->load->view('admin/partials/table_listing_footer', array('total_rows' => $total_rows, 'per_page' => $per_page, 'selected_per_page' => $this->per_page, 'page_offset' => $page_numb, 'pagination' => isset($paginate) ? $paginate : '', 'pagination_label' => 'Tour itinerary pagination')); ?>
</section>
<script>
    jQuery(function($) {
        var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$itinerary_tour_id.'/'.$sortby.'/'.$order)); ?>,
            $rows = $('#multiDel .cselect'),
            $all = $('#all-checkbox'),
            $bulk = $('#tour-itineraries-bulk-actions'),
            $count = $('#tour-itineraries-selection-count'),
            $delete = $('#deleteAllRecords');

        function applyFilters() {
            var keyword = $.trim($('#search_keywords').val());
            window.location = filterUrl + '/' + $('#search_status').val() + '/' + (keyword === '' ? '-' : encodeURIComponent(keyword));
        }

        function updateSelection() {
            var selected = $rows.filter(':checked').length;
            $count.text(selected + ' selected');
            $delete.prop('disabled', selected === 0);
            $bulk.prop('hidden', selected === 0);
            $all.prop('checked', $rows.length > 0 && selected === $rows.length).prop('indeterminate', selected > 0 && selected < $rows.length).prop('disabled', $rows.length === 0);
        }

        $('#tour-itineraries-filter-form').on('submit', function(event) {
            event.preventDefault();
            applyFilters();
        });
        $('#search_status').on('change', applyFilters);
        $rows.on('change', updateSelection);
        $all.on('change', function() {
            window.setTimeout(updateSelection, 0);
        });
        updateSelection();
    });
</script>
