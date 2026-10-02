<?php
$adminName = isset($userdata['full_name']) ? $userdata['full_name'] : $this->session->userdata('admin_full_name');
$adminAvatar = isset($userdata['avatar']) ? basename((string) $userdata['avatar']) : '';
$adminAvatarPath = FCPATH.'assets/frontend/images/admins/'.$adminAvatar;
$siteSettings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
$logoPath = './assets/frontend/images/logo/'.(isset($siteSettings['logo_white']) ? $siteSettings['logo_white'] : '');
$footerMenuOneLabel = !empty($siteSettings['foot_col_2']) ? $siteSettings['foot_col_2'] : 'Footer Menu';
$footerMenuTwoLabel = !empty($siteSettings['foot_col_3']) ? $siteSettings['foot_col_3'] : 'Footer Menu Two';
$menusOpen = isset($menuActive);
$blogsOpen = isset($blogsActive) || isset($blogCategoriesActive);
$servicesOpen = isset($servicesActive) || isset($serviceCategoriesActive);
$galleryOpen = isset($galleryActive) || isset($galleryCategoriesActive);
$lastLoginValue = $this->session->userdata('last_login');
$lastLoginTimestamp = $lastLoginValue ? strtotime($lastLoginValue) : false;
$canAccessMiscContent = isset($userdata) && (
    $userdata['user_role'] === 'Super Admin'
    || (isset($userdata['access_sections']) && $userdata['access_sections'] === 'Yes')
);

/**
 * Sidebar navigation config.
 *
 * Each section has a `label` and a list of `items`. An item is either:
 * - type `link`: title, icon, url, active
 * - type `toggle`: title, icon, target (collapse id), active, children[] (title, url, active)
 *
 * `visible` (optional, default true) hides an item without touching the array shape.
 */
$adminNav = array(
    array(
        'label' => 'Overview',
        'items' => array(
            array(
                'type' => 'link',
                'title' => 'Dashboard',
                'icon' => 'bi-speedometer2',
                'url' => ADMIN_URL,
                'active' => isset($dashBoard),
            ),
        ),
    ),
    array(
        'label' => 'Salon',
        'items' => array(
            array(
                'type' => 'link',
                'title' => 'Appointments',
                'icon' => 'bi-calendar-check',
                'url' => ADMIN_URL.'appointments',
                'active' => isset($appointmentsActive),
            ),
            array(
                'type' => 'link',
                'title' => 'Reports',
                'icon' => 'bi-bar-chart-line',
                'url' => ADMIN_URL.'reports',
                'active' => isset($reportsActive),
            ),
            array(
                'type' => 'link',
                'title' => 'Customers',
                'icon' => 'bi-people',
                'url' => ADMIN_URL.'customers',
                'active' => isset($customersActive),
            ),
            array(
                'type' => 'toggle',
                'title' => 'Services',
                'icon' => 'bi-stars',
                'target' => 'adminServicesSubnav',
                'active' => $servicesOpen,
                'children' => array(
                    array(
                        'title' => 'All Services',
                        'url' => ADMIN_URL.'services',
                        'active' => isset($servicesActive),
                    ),
                    array(
                        'title' => 'Categories',
                        'url' => ADMIN_URL.'service-categories',
                        'active' => isset($serviceCategoriesActive),
                    ),
                ),
            ),
            array(
                'type' => 'link',
                'title' => 'Artists',
                'icon' => 'bi-person-heart',
                'url' => ADMIN_URL.'artists',
                'active' => isset($artistsActive),
            ),
            array(
                'type' => 'toggle',
                'title' => 'Gallery',
                'icon' => 'bi-images',
                'target' => 'adminGallerySubnav',
                'active' => $galleryOpen,
                'children' => array(
                    array(
                        'title' => 'Images',
                        'url' => ADMIN_URL.'gallery',
                        'active' => isset($galleryActive),
                    ),
                    array(
                        'title' => 'Categories',
                        'url' => ADMIN_URL.'gallery-categories',
                        'active' => isset($galleryCategoriesActive),
                    ),
                ),
            ),
            array(
                'type' => 'link',
                'title' => 'Offers',
                'icon' => 'bi-gift',
                'url' => ADMIN_URL.'offers',
                'active' => isset($offersActive),
            ),
        ),
    ),
    array(
        'label' => 'Content',
        'items' => array(
            array(
                'type' => 'link',
                'title' => 'Image Sliders',
                'icon' => 'bi-images',
                'url' => ADMIN_URL.'sliders',
                'active' => isset($sliderActive),
            ),
            array(
                'type' => 'link',
                'title' => 'Web Pages',
                'icon' => 'bi-file-earmark-text',
                'url' => ADMIN_URL.'pages',
                'active' => isset($pagesActive),
            ),
            array(
                'type' => 'toggle',
                'title' => 'Blogs',
                'icon' => 'bi-journal-text',
                'target' => 'adminBlogSubnav',
                'active' => $blogsOpen,
                'children' => array(
                    array(
                        'title' => 'Categories',
                        'url' => ADMIN_URL.'blog-categories',
                        'active' => isset($blogCategoriesActive),
                    ),
                    array(
                        'title' => 'Posts',
                        'url' => ADMIN_URL.'blogs',
                        'active' => isset($blogsActive),
                    ),
                ),
            ),
            array(
                'type' => 'link',
                'title' => 'Miscellaneous Contents',
                'icon' => 'bi-layout-text-window-reverse',
                'url' => base_url('manage/miscellaneous-contents'),
                'active' => isset($contentSectionsActive),
                'visible' => $canAccessMiscContent,
            ),
            array(
                'type' => 'toggle',
                'title' => 'Menu Manager',
                'icon' => 'bi-diagram-3',
                'target' => 'adminMenuSubnav',
                'active' => $menusOpen,
                'children' => array(
                    array(
                        'title' => 'Main Menu',
                        'url' => ADMIN_URL.'menu',
                        'active' => isset($menuMainActive),
                    ),
                    array(
                        'title' => $footerMenuOneLabel,
                        'url' => ADMIN_URL.'foot/index/one',
                        'active' => isset($menuFootActive) && $menuFootActive === 'one',
                    ),
                    array(
                        'title' => $footerMenuTwoLabel,
                        'url' => ADMIN_URL.'foot/index/two',
                        'active' => isset($menuFootActive) && $menuFootActive === 'two',
                    ),
                    array(
                        'title' => 'Email',
                        'url' => ADMIN_URL.'foot/index/three',
                        'active' => isset($menuFootActive) && $menuFootActive === 'three',
                    ),
                ),
            ),
        ),
    ),
    array(
        'label' => 'Communication',
        'items' => array(
            array(
                'type' => 'link',
                'title' => 'Customer Reviews',
                'icon' => 'bi-star',
                'url' => ADMIN_URL.'customer-reviews',
                'active' => isset($customerReviewsActive),
            ),
            array(
                'type' => 'link',
                'title' => 'Contact Requests',
                'icon' => 'bi-envelope-paper',
                'url' => ADMIN_URL.'contact-requests',
                'active' => isset($contactRequestsActive),
            ),
            array(
                'type' => 'link',
                'title' => 'FAQs',
                'icon' => 'bi-question-circle',
                'url' => ADMIN_URL.'faqs-categories',
                'active' => isset($faqsCategoriesActive) || isset($faqsActive),
            ),
            array(
                'type' => 'link',
                'title' => 'Email Templates',
                'icon' => 'bi-envelope-at',
                'url' => ADMIN_URL.'email-templates',
                'active' => isset($emailTemplatesActive),
            ),
        ),
    ),
    array(
        'label' => 'Administration',
        'items' => array(
            array(
                'type' => 'link',
                'title' => 'Admin Users',
                'icon' => 'bi-people',
                'url' => ADMIN_URL.'admins',
                'active' => isset($adminsActive),
            ),
            array(
                'type' => 'link',
                'title' => 'Form Settings',
                'icon' => 'bi-ui-checks',
                'url' => ADMIN_URL.'form-settings',
                'active' => isset($formSettingsActive),
            ),
            array(
                'type' => 'link',
                'title' => 'Website Settings',
                'icon' => 'bi-gear',
                'url' => ADMIN_URL.'website-settings',
                'active' => isset($wsettingActive),
            ),
        ),
    ),
);
?>
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar" aria-label="Administration navigation">
        <div class="admin-brand">
            <a href="<?php echo ADMIN_URL; ?>" class="admin-brand-link" aria-label="Dashboard">
                <?php if (file_exists($logoPath)) { ?>
                <img src="<?php echo image_thumb_src($logoPath, 0, 132, 'webp'); ?>" alt="<?php echo htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8'); ?>">
                <?php } else { ?>
                <span class="admin-brand-mark"><i class="bi bi-compass" aria-hidden="true"></i></span>
                <span class="admin-brand-name"><?php echo htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php } ?>
            </a>
            <button type="button" class="admin-sidebar-collapse d-none d-lg-inline-flex" data-sidebar-collapse aria-label="Collapse sidebar" title="Collapse sidebar">
                <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="admin-nav" id="adminPrimaryNav">
            <?php foreach ($adminNav as $section) { ?>
                <?php
                $sectionItems = array_filter($section['items'], function ($item) {
                    return !isset($item['visible']) || $item['visible'];
                });

                if (empty($sectionItems)) {
                    continue;
                }
                ?>
                <div class="admin-nav-label"><?php echo htmlspecialchars($section['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php foreach ($sectionItems as $item) { ?>
                    <?php if ($item['type'] === 'toggle') { ?>
                        <button
                            type="button"
                            class="admin-nav-link admin-nav-toggle <?php echo $item['active'] ? 'active' : 'collapsed'; ?>"
                            title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?php echo htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8'); ?>"
                            aria-expanded="<?php echo $item['active'] ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <i class="bi bi-chevron-down admin-nav-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="collapse admin-subnav <?php echo $item['active'] ? 'show' : ''; ?>" id="<?php echo htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($item['children'] as $child) { ?>
                            <a
                                class="admin-subnav-link <?php echo $child['active'] ? 'active' : ''; ?>"
                                href="<?php echo $child['url']; ?>"
                                title="<?php echo htmlspecialchars($child['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $child['active'] ? 'aria-current="page"' : ''; ?>
                            ><?php echo htmlspecialchars($child['title'], ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <a
                            class="admin-nav-link <?php echo $item['active'] ? 'active' : ''; ?>"
                            href="<?php echo $item['url']; ?>"
                            title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $item['active'] ? 'aria-current="page"' : ''; ?>
                        >
                            <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    <?php } ?>
                <?php } ?>
            <?php } ?>
        </nav>
        <script>
        (function () {
            if (!document.body.classList.contains('admin-sidebar-collapsed')) {
                return;
            }
            document.querySelectorAll('#adminSidebar .admin-subnav.show').forEach(function (subnav) {
                subnav.classList.remove('show');
                var toggle = document.querySelector('[data-bs-target="#' + subnav.id + '"]');
                if (toggle) {
                    toggle.classList.add('collapsed');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        })();
        </script>
    </aside>

    <button type="button" class="admin-sidebar-backdrop" data-sidebar-close aria-label="Close navigation"></button>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-mobile-toggle" data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="false" aria-label="Open navigation">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div class="admin-topbar-context">
                
                <span class="admin-server-time" id="admin-server-time" data-server-time="<?php echo time() * 1000; ?>"><strong>Server time:</strong> <span class="admin-server-time-value"><?php echo date(ADMIN_DATETIME_FORMAT); ?></span></span>
                <span class="admin-topbar-title"><strong>Last login:</strong> <?php echo $lastLoginTimestamp ? date(ADMIN_DATETIME_FORMAT, $lastLoginTimestamp) : 'Not available'; ?></span>
            </div>
            <div class="dropdown ms-auto">
                <button
                    class="admin-user-menu dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-haspopup="true"
                    aria-label="Account menu for <?php echo htmlspecialchars((string) $adminName, ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <span class="admin-user-avatar" aria-hidden="true">
                        <?php if ($adminAvatar !== '' && is_file($adminAvatarPath)) { ?>
                        <img src="<?php echo base_url('assets/frontend/images/admins/'.rawurlencode($adminAvatar)); ?>" alt="">
                        <?php } else { ?>
                        <?php echo strtoupper(substr(trim((string) $adminName), 0, 1)); ?>
                        <?php } ?>
                    </span>
                    <span class="admin-user-copy" aria-hidden="true"><strong><?php echo htmlspecialchars((string) $adminName, ENT_QUOTES, 'UTF-8'); ?></strong><small>Administrator</small></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>home/settings"><i class="bi bi-person-gear" aria-hidden="true"></i> Account Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo ADMIN_URL; ?>home/logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log Out</a></li>
                </ul>
            </div>
        </header>
        <main class="admin-content" id="admin-main-content">
