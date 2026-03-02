<!-- Menu -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu bg-menu-theme flex-grow-0">
    <div class="container-fluid d-flex h-100">
        <ul class="menu-inner">

            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            
            // Menu items configuration
            $pages = [
                'dashboard.php' => [
                    'label' => 'Technicians Login',
                    'icon' => 'bx bxs-user-check',
                ],
                'today_jo_plan.php' => [
                    'label' => 'Today JO Planned',
                    'icon' => 'bx bxs-calendar-event',
                    'submenu' => [ // Submenu items
                        [
                            'label' => 'Datatables JO Planned',
                            'icon' => 'bx bx-table',
                            'link' => 'today_jo_plan.php',
                        ],
                        [
                            'label' => 'Technician Location Based On JO Planned',
                            'icon' => 'fa-solid fa-map-location-dot',
                            'link' => 'geo_location_today_plan.php',
                        ],
                    ],
                ],
                'assigned_jo.php' => [
                    'label' => 'Assigned JO Technicians',
                    'icon' => 'bx bxs-user-detail',
                ],
                // Add more items here...
            ];

            function isActiveSubmenu($submenu_items, $current_page)
            {
                foreach ($submenu_items as $submenu) {
                    if (basename($submenu['link']) == $current_page) {
                        return true;
                    }
                }
                return false;
            }
            ?>

            <!-- Loop through the pages to create menu items -->
            <?php foreach ($pages as $page => $info): ?>
                <?php
                // Check if the current menu or any of its submenu items is active
                $is_active_menu = ($current_page == $page) || (isset($info['submenu']) && isActiveSubmenu($info['submenu'], $current_page));
                ?>

                <li class="menu-item <?php echo $is_active_menu ? 'active' : ''; ?>">
                    <a href="<?php echo isset($info['submenu']) ? 'javascript:void(0);' : $page; ?>"
                        class="menu-link <?php echo isset($info['submenu']) ? 'menu-toggle' : ''; ?>">
                        <i class="menu-icon tf-icons <?php echo $info['icon']; ?>"></i>
                        <div data-i18n="<?php echo $info['label']; ?>"><?php echo $info['label']; ?></div>
                    </a>

                    <!-- If the page has a submenu, create the submenu -->
                    <?php if (isset($info['submenu'])): ?>
                        <ul class="menu-sub <?php echo isActiveSubmenu($info['submenu'], $current_page) ? 'open' : ''; ?>">
                            <?php foreach ($info['submenu'] as $submenu): ?>
                                <li class="menu-item <?php echo (basename($submenu['link']) == $current_page) ? 'active' : ''; ?>">
                                    <a href="<?php echo $submenu['link']; ?>" class="menu-link">
                                        <i class="menu-icon tf-icons <?php echo $submenu['icon']; ?>"></i>
                                        <div data-i18n="<?php echo $submenu['label']; ?>"><?php echo $submenu['label']; ?></div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>

        </ul>
    </div>
</aside>