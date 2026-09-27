<?php defined('BASEPATH') OR exit('No direct script access allowed');

$breadcrumbItems = isset($items) && is_array($items) ? $items : array();
$breadcrumbShowHome = !isset($show_home) || (bool) $show_home;
$breadcrumbClass = isset($class) ? trim((string) $class) : '';
?>
<nav<?php echo $breadcrumbClass !== '' ? ' class="'.htmlspecialchars($breadcrumbClass, ENT_QUOTES, 'UTF-8').'"' : ''; ?> aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <?php if ($breadcrumbShowHome) { ?><li><a href="<?php echo htmlspecialchars(ADMIN_URL, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-house" aria-hidden="true"></i> Home</a></li><?php } ?>
        <?php foreach ($breadcrumbItems as $breadcrumbIndex => $breadcrumbItem) {
            $breadcrumbLabel = isset($breadcrumbItem['label']) ? (string) $breadcrumbItem['label'] : '';
            $breadcrumbUrl = isset($breadcrumbItem['url']) ? (string) $breadcrumbItem['url'] : '';
            $breadcrumbActive = array_key_exists('active', $breadcrumbItem) ? (bool) $breadcrumbItem['active'] : ($breadcrumbIndex === count($breadcrumbItems) - 1);
        ?>
        <li<?php echo $breadcrumbActive ? ' class="active" aria-current="page"' : ''; ?>><?php if (!$breadcrumbActive && $breadcrumbUrl !== '') { ?><a href="<?php echo htmlspecialchars($breadcrumbUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($breadcrumbLabel, ENT_QUOTES, 'UTF-8'); ?></a><?php } elseif (!$breadcrumbActive) { ?><span><?php echo htmlspecialchars($breadcrumbLabel, ENT_QUOTES, 'UTF-8'); ?></span><?php } else { ?><strong><?php echo htmlspecialchars($breadcrumbLabel, ENT_QUOTES, 'UTF-8'); ?></strong><?php } ?></li>
        <?php } ?>
    </ol>
</nav>
