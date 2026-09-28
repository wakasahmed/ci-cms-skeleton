<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$totalSections = count($sections);
?>

<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => 'Miscellaneous Contents', 'active' => TRUE),
    ),
)); ?>

<section
    class="admin-records-listing miscellaneous-contents-listing"
    aria-labelledby="miscellaneous-contents-title"
>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => 'Miscellaneous Contents',
        'description' => 'Manage reusable global content shown across the website.',
        'id' => 'miscellaneous-contents-title',
    )); ?>

    <?php if ($success_message) { ?>
        <div class="alert alert-success" role="status">
            <?php echo $escape($success_message); ?>
        </div>
    <?php } ?>

    <div class="card admin-card">
        <div class="card-body p-0">
            <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Miscellaneous contents">
                <table class="table pages-listing-table miscellaneous-contents-table mb-0">
                    <thead>
                        <tr>
                            <th class="miscellaneous-section-column" scope="col">Content block</th>
                            <th class="miscellaneous-status-column" scope="col">Status</th>
                            <th scope="col" class="d-none d-md-table-cell">Created On</th>
                            <th scope="col" class="d-none d-md-table-cell">Updated On</th>
                            <th class="pages-actions-column" scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($totalSections > 0) { ?>
                            <?php foreach ($sections as $section) { ?>
                                <?php
                                $editUrl = base_url(
                                    'manage/miscellaneous-contents/'
                                    .rawurlencode($section['section_key']).'/edit'
                                );
                                $sectionLabel = $escape($section['section_label']);
                                ?>
                                <tr>
                                    <td class="miscellaneous-section-column">
                                        <?php if ($can_update) { ?>
                                            <a class="pages-name-link" href="<?php echo $editUrl; ?>">
                                                <?php echo $sectionLabel; ?>
                                            </a>
                                        <?php } else { ?>
                                            <span class="miscellaneous-section-name"><?php echo $sectionLabel; ?></span>
                                        <?php } ?>
                                       
                                    </td>
                                    <td class="miscellaneous-status-column">
                                        <?php if ($can_update) { ?>
                                            <button type="button" class="changestatus pages-status-button" data-controller="miscellaneous-contents" id="statusID<?php echo (int) $section['section_id']; ?>" aria-label="Change status for <?php echo $sectionLabel; ?>"><?php echo $escape($section['section_status']); ?></button>
                                        <?php } else { ?>
                                            <span class="pages-status-static" id="statusID<?php echo (int) $section['section_id']; ?>"><?php echo $escape($section['section_status']); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php if (!empty($section['created_at']) && strtotime($section['created_at'])) { ?>
                                            <time datetime="<?php echo date('c', strtotime($section['created_at'])); ?>">
                                                <?php echo date(ADMIN_DATE_FORMAT, strtotime($section['created_at'])); ?>
                                                <span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($section['created_at'])); ?></span>
                                            </time>
                                        <?php } else { ?>&mdash;<?php } ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php if (!empty($section['updated_at']) && strtotime($section['updated_at'])) { ?>
                                            <time datetime="<?php echo date('c', strtotime($section['updated_at'])); ?>">
                                                <?php echo date(ADMIN_DATE_FORMAT, strtotime($section['updated_at'])); ?>
                                                <span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($section['updated_at'])); ?></span>
                                            </time>
                                        <?php } else { ?>&mdash;<?php } ?>
                                    </td>
                                    <td class="pages-actions-column pages-actions-cell">
                                        <?php if ($can_update) { ?>
                                            <a
                                                class="admin-action-icon font16"
                                                href="<?php echo $editUrl; ?>"
                                                aria-label="Edit <?php echo $sectionLabel; ?>"
                                                title="Edit <?php echo $sectionLabel; ?>"
                                            >
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="pages-empty-state" colspan="5">
                                    <strong>No content blocks are configured.</strong>
                                    <span>Add a section to the content configuration to make it available here.</span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalSections > 0) { ?>
            <?php $this->load->view('admin/partials/table_listing_footer', array(
                'total_rows' => $totalSections,
                'per_page' => 0,
                'show_per_page' => FALSE,
            )); ?>
        <?php } ?>
    </div>
</section>
