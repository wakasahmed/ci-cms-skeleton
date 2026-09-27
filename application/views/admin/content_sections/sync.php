<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$missingCount = count($items);
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => 'Web Pages', 'url' => ADMIN_URL.'pages'),
        array('label' => $module_name, 'active' => TRUE),
    ),
)); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'status' => $status,
    'module_name' => $module_name,
    'status_messages' => array(
        'editsuccess' => array(
            'success',
            'Success!',
            $inserted === 1
                ? '1 missing field was added.'
                : $inserted.' missing fields were added.',
        ),
    ),
)); ?>

<section class="admin-records-listing" aria-labelledby="content-section-sync-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $module_name,
        'description' => 'Compare the configured page and miscellaneous content fields with the database and add any that are missing. Existing values are never changed.',
        'id' => 'content-section-sync-title',
    )); ?>

    <?php if ($load_error) { ?>
        <div class="alert alert-danger" role="alert">
            Some content sections could not be loaded, so this list may be incomplete.
        </div>
    <?php } ?>

    <form
        method="post"
        action="<?php echo $escape(base_url('manage/'.$controller.'/run')); ?>"
        class="mb-3"
    >
        <?php if ($this->config->item('csrf_protection')) { ?>
            <input
                type="hidden"
                name="<?php echo $escape($this->security->get_csrf_token_name()); ?>"
                value="<?php echo $escape($this->security->get_csrf_hash()); ?>"
            >
        <?php } ?>
        <button
            type="submit"
            class="btn btn-primary"
            <?php echo $missingCount === 0 || $load_error ? 'disabled' : ''; ?>
        >
            <i class="bi bi-database-add" aria-hidden="true"></i>
            Add <?php echo (int) $missingCount; ?> Missing <?php echo $missingCount === 1 ? 'Field' : 'Fields'; ?>
        </button>
    </form>

    <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Missing content section fields table">
        <table class="table pages-listing-table admin-records-table">
            <thead>
                <tr>
                    <th scope="col">Page</th>
                    <th scope="col">Section</th>
                    <th scope="col">Field</th>
                    <th scope="col">Key</th>
                    <th scope="col">Language</th>
                    <th scope="col">Value to Add</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)) { ?>
                    <?php foreach ($items as $item) { ?>
                        <?php
                        $fieldLabel = $item['field_group'] !== null
                            ? $item['field_group'].' › '.$item['field_label']
                            : $item['field_label'];
                        $localeLabel = isset($locales[$item['locale']]['label'])
                            ? $locales[$item['locale']]['label']
                            : $item['locale'];
                        ?>
                        <tr>
                            <td><?php echo $escape($item['group_label']); ?></td>
                            <td><?php echo $escape($item['section_label']); ?></td>
                            <td><?php echo $escape($fieldLabel); ?></td>
                            <td><code><?php echo $escape($item['field_key']); ?></code></td>
                            <td><?php echo $escape($localeLabel); ?></td>
                            <td>
                                <?php if ($item['field_value'] === null) { ?>
                                    <span class="text-muted">Empty</span>
                                <?php } else { ?>
                                    <?php echo $escape($item['field_value']); ?>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td class="pages-empty-state" colspan="6">
                            <strong>No missing fields.</strong>
                            <span>Every configured field exists in the database for every language.</span>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>
