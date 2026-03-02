<?php

class body
{
    public function __construct($page)
    {
        echo <<<EOD
            {$this->setBody($page)}
        EOD;
    }

    private function getFooter()
    {
        ob_start();
        require_once('public/views/layouts/footer.html');
        return ob_get_clean();
    }

    /*-----------------------
    |:                     :|
    |:  Page: Login        :|
    |:                     :|
    -----------------------*/
    private function loginContent()
    {
        ob_start();
        require_once('public/views/pages/login/login.html');
        return ob_get_clean();
    }

    /*-----------------------
    |:                     :|
    |:  Page: Set New      :|
    |:  Password           :|
    |:                     :|
    -----------------------*/
    private function setNewPasswordContent()
    {
        ob_start();
        require_once('public/views/pages/set-new-password/set-new-password.html');
        return ob_get_clean();
    }

    /*-----------------------
    |:                     :|
    |:  Page: Dashboard    :|
    |:                     :|
    -----------------------*/
    private function setDashboardContent()
    {
        ob_start();
        require_once('public/views/pages/dashboard/dashboard.php');
        return ob_get_clean();
    }

    private function setNavbar()
    {
        ob_start();
        require_once('public/views/layouts/navbar.php');
        return ob_get_clean();
    }

    private function setMenu()
    {
        ob_start();
        require_once('public/views/layouts/menu.php');
        return ob_get_clean();
    }

    /*-----------------------
    |:                     :|
    |:  Page: Today JO     :|
    |:  Planned            :|
    |:                     :|
    -----------------------*/
    private function setTodayJOPlannedContent()
    {
        ob_start();
        require_once('public/views/pages/today_jo_plan/today_jo_plan.php');
        return ob_get_clean();
    }

    /*-----------------------
    |:                     :|
    |:  Page: Assigned     :|
    |:  JO                 :|
    |:                     :|
    -----------------------*/
    private function setAssignedJOContent()
    {
        ob_start();
        require_once('public/views/pages/assigned_jo/assigned_jo.php');
        return ob_get_clean();
    }

    /*-----------------------
    |:                     :|
    |:  Page: Geo          :|
    |:  Location Today     :|
    |:  Planned            :|
    |:                     :|
    -----------------------*/
    private function setGeoLocationTodayPlanned()
    {
        ob_start();
        require_once('public/views/pages/geo_location_today_plan/geo_location_today_plan.php');
        return ob_get_clean();
    }

    private function setTodayWithdrawalJO()
    {
        ob_start();
        require_once('public/views/pages/today_withdrawal_jo/today_withdrawal_jo.php');
        return ob_get_clean();
    }

    private function loadJS($page)
    {
        $mainJS = <<<EOD
            <!-- Core JS -->
            <!-- build:js public/assets/js/core.js -->

            <script src="public/assets/js/bootstrap.js"></script>

            <!-- Libs -->
            <script src="public/libs/popper/popper.js"></script>
            <script src="public/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
            <script src="public/libs/hammer/hammer.js"></script>
            <script src="public/libs/i18n/i18n.js"></script>
            <script src="public/libs/typeahead-js/typeahead.js"></script>
            <!-- / Libs -->

            <script src="public/assets/js/menu.js"></script>

            <!-- IziToast -->
            <script src="public/libs/izitoast/iziToast.js"></script>
            <script src="public/libs/izitoast/showIziToast.js"></script>

            <!-- Main JS -->
            <script src="public/assets/js/main.js"></script>
        EOD;

        switch (strtolower($page)) {
            case 'login':
                $mainJS .= <<<EOD
                    <!-- Libs -->
                    <script src="public/libs/@form-validation/umd/bundle/popular.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-bootstrap5/index.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-auto-focus/index.min.js"></script>
                    <!-- Page JS -->
                    <script src="public/assets/js/login/login.js"></script>
                EOD;
                return $mainJS;
            case 'set new password':
                $mainJS .= <<<EOD
                    <!-- Libs -->
                    <script src="public/libs/@form-validation/umd/bundle/popular.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-bootstrap5/index.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-auto-focus/index.min.js"></script>
                    <!-- Page JS -->
                    <script src="public/assets/js/set-new-password/set-new-password.js"></script>
                EOD;
                return $mainJS;
            case 'dashboard':
                $mainJS .= <<<EOD
                    <!-- Page JS -->
                    <script src="public/assets/js/dashboard/dashboard.js"></script>
                    <!-- Datatables -->
                    <script src="public/libs/datatables-bs5/datatables-bootstrap5.js"></script>
                    <!-- Flat Picker -->
                    <script src="public/libs/moment/moment.js"></script>
                    <script src="public/libs/flatpickr/flatpickr.js"></script>
                    <!-- Form Validation -->
                    <script src="public/libs/@form-validation/umd/bundle/popular.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-bootstrap5/index.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-auto-focus/index.min.js"></script>
                    <!-- Block UI -->
                    <script src="public/libs/block-ui/block-ui.js"></script>
                    <!-- Datatables -->
                    <script src="public/assets/js/dashboard/datatables-technicians.js"></script>
                EOD;
                return $mainJS;
            case 'today_jo_plan':
                $mainJS .= <<<EOD
                    <!-- Page JS -->
                    <script src="public/assets/js/dashboard/dashboard.js"></script>
                    <!-- Datatables -->
                    <script src="public/libs/datatables-bs5/datatables-bootstrap5.js"></script>
                    <!-- Flat Picker -->
                    <script src="public/libs/moment/moment.js"></script>
                    <script src="public/libs/flatpickr/flatpickr.js"></script>
                    <!-- Form Validation -->
                    <script src="public/libs/@form-validation/umd/bundle/popular.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-bootstrap5/index.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-auto-focus/index.min.js"></script>
                    <!-- Block UI -->
                    <script src="public/libs/block-ui/block-ui.js"></script>
                    <!-- Sortable -->
                    <script src="public/libs/sortablejs/sortable.js"></script>
                    <!-- Datatables -->
                    <script src="public/assets/js/today_jo_plan/today_jo_plan.js"></script>
                EOD;
                return $mainJS;
            case 'assigned_jo':
                $mainJS .= <<<EOD
                    <!-- Page JS -->
                    <script src="public/assets/js/dashboard/dashboard.js"></script>
                    <!-- Datatables -->
                    <script src="public/libs/datatables-bs5/datatables-bootstrap5.js"></script>
                    <!-- Flat Picker -->
                    <script src="public/libs/moment/moment.js"></script>
                    <script src="public/libs/flatpickr/flatpickr.js"></script>
                    <!-- Form Validation -->
                    <script src="public/libs/@form-validation/umd/bundle/popular.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-bootstrap5/index.min.js"></script>
                    <script src="public/libs/@form-validation/umd/plugin-auto-focus/index.min.js"></script>
                    <!-- Block UI -->
                    <script src="public/libs/block-ui/block-ui.js"></script>
                    <!-- Sortable -->
                    <script src="public/libs/sortablejs/sortable.js"></script>
                    <!-- Datatables -->
                    <script src="public/assets/js/assigned_jo/assigned_jo.js"></script>
                EOD;
                return $mainJS;
            case 'geo_location_today_plan':
                $mainJS .= <<<EOD
                    <!-- Page JS -->
                    <script src="public/assets/js/dashboard/dashboard.js"></script>

                    <!-- Block UI -->
                    <script src="public/libs/block-ui/block-ui.js"></script>

                    <!-- Leaflet -->
                    <script src="public/libs/leaflet/leaflet.js"></script>
                    <script src="public/assets/js/geo_location_today_plan/geo_location.js"></script>
                    <!-- Leaflet Plugins -->
                    <!-- <script src="public/libs/leaflet/plugins/brunob-fullscreen/Control.Fullscreen.js"></script> -->
                    <script src="public/libs/leaflet/plugins/zimmicz-coordinates/Control.Coordinates.js"></script>
                    <script src="public/libs/leaflet/plugins/norkart-minimap/Control.MiniMap.js"></script>
                    <script src="public/libs/leaflet/plugins/coryasilva-extramarkers/leaflet.extra-markers.min.js"></script>
                    <script src="public/libs/leaflet/plugins/elmarquis-gesturehandling/leaflet-gesture-handling.min.js"></script>
                    <script src="public/libs/leaflet/plugins/mapshakers-iconpulse/L.Icon.Pulse.js"></script>
                    <script src="public/libs/leaflet/plugins/stefanocudini-search/leaflet-search.js"></script>
                    <script src="public/libs/leaflet/plugins/ppete2-polylinemeasure/Leaflet.PolylineMeasure.js"></script>
                    <script src="public/libs/leaflet/plugins/aazuspan-legend/feature-legend.js"></script>
                    <script src="public/libs/leaflet/plugins/stefanocudini-panellayers/leaflet-panel-layers.js"></script>
                    <script src="public/libs/leaflet/plugins/leaflet-fullscreen/leaflet.fullscreen.js"></script>

                    <!-- Tom Select -->
                    <script src="public/libs/tom-select/tom-select.complete.js"></script>

                    <!-- Moment -->
                    <script src="public/libs/moment/moment.js"></script>

                    <!-- Bootstrap Date Range Picker -->
                    <script src="public/libs/bootstrap-datepicker/bootstrap-datepicker.js"></script>
                    <script src="public/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
                EOD;
                return $mainJS;
            case 'today_withdrawal_jo':
                $mainJS .= <<<EOD
                    <!-- Page JS -->
                    <script src="public/assets/js/dashboard/dashboard.js"></script>

                    <!-- Block UI -->
                    <script src="public/libs/block-ui/block-ui.js"></script>

                    <!-- Datatables -->
                    <script src="public/assets/js/today_withdrawal_jo/today_withdrawal_jo.js"></script>
                    <script src="public/libs/datatables-bs5/datatables-bootstrap5.js"></script>
                    <!-- Flat Picker -->
                    <script src="public/libs/moment/moment.js"></script>
                    <script src="public/libs/flatpickr/flatpickr.js"></script>
                    <!-- Sortable -->
                    <script src="public/libs/sortablejs/sortable.js"></script>
                EOD;
                return $mainJS;
            default:
                return "<!-- No JS found to be loaded for page: $page! -->";
        }
    }

    public function setBody($page)
    {
        $footerContent = $this->getFooter();

        switch (strtolower($page)) {
            case 'login':
                $loginContent = $this->loginContent();
                return <<<EOD
                    <body>
                        <!-- Content -->
                        <div class="container-xxl">
                            <div class="authentication-wrapper authentication-basic container-p-y">
                                {$loginContent}
                            </div>
                            {$footerContent}
                        </div>
                        <!-- / Content -->
                        {$this->loadJS($page)}
                EOD;
            case 'set new password':
                $setNewPasswordContent = $this->setNewPasswordContent();
                $JSONNewPwd = $_SESSION['new-password'];
                $dataNewPwd = json_decode($JSONNewPwd, true);

                return <<<EOD
                    <body>
                        <!-- Content -->
                        <div class="container-xxl">
                            <div class="authentication-wrapper authentication-basic container-p-y">
                                <div id="email_data" data-email="{$dataNewPwd['email']}">
                                </div>
                                {$setNewPasswordContent}
                            </div>
                            {$footerContent}
                        </div>
                        <!-- / Content -->
                        {$this->loadJS($page)}
                EOD;
            case 'dashboard':
                $dashboardContent = $this->setDashboardContent();
                $navbarContent = $this->setNavbar();
                $menuContent = $this->setMenu();

                return <<<EOD
                    <body>
                        <!-- Layout wrapper -->
                        <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
                            <!-- Layout container -->
                            <div class="layout-container">
                            {$navbarContent}
                                <div class="layout-page">
                                    <!-- Content wrapper -->
                                    <div class="content-wrapper">
                                        {$menuContent}
                                        {$dashboardContent}
                                        {$footerContent}
                                    <div class="content-backdrop fade"></div>
                                    </div>
                                    <!--/ Content wrapper -->
                                </div>

                                </div>
                                <!--/ Layout container -->
                        </div>
                        <!--/ Layout wrapper -->

                        <!-- Overlay -->
                        <div class="layout-overlay layout-menu-toggle"></div>

                        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
                        <div class="drag-target"></div>

                        {$this->loadJS($page)}
                EOD;
            case 'today_jo_plan':
                $todayJOPlanContent = $this->setTodayJOPlannedContent();
                $navbarContent = $this->setNavbar();
                $menuContent = $this->setMenu();

                return <<<EOD
                    <body>
                        <!-- Layout wrapper -->
                        <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
                            <!-- Layout container -->
                            <div class="layout-container">
                            {$navbarContent}
                                <div class="layout-page">
                                    <!-- Content wrapper -->
                                    <div class="content-wrapper">
                                        {$menuContent}
                                        {$todayJOPlanContent}
                                        {$footerContent}
                                    <div class="content-backdrop fade"></div>
                                    </div>
                                    <!--/ Content wrapper -->
                                </div>

                                </div>
                                <!--/ Layout container -->
                        </div>
                        <!--/ Layout wrapper -->

                        <!-- Overlay -->
                        <div class="layout-overlay layout-menu-toggle"></div>

                        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
                        <div class="drag-target"></div>

                        {$this->loadJS($page)}
                EOD;
            case 'assigned_jo':
                $assignedJOContent = $this->setAssignedJOContent();
                $navbarContent = $this->setNavbar();
                $menuContent = $this->setMenu();

                return <<<EOD
                    <body>
                        <!-- Layout wrapper -->
                        <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
                            <!-- Layout container -->
                            <div class="layout-container">
                            {$navbarContent}
                                <div class="layout-page">
                                    <!-- Content wrapper -->
                                    <div class="content-wrapper">
                                        {$menuContent}
                                        {$assignedJOContent}
                                        {$footerContent}
                                    <div class="content-backdrop fade"></div>
                                    </div>
                                    <!--/ Content wrapper -->
                                </div>

                                </div>
                                <!--/ Layout container -->
                        </div>
                        <!--/ Layout wrapper -->

                        <!-- Overlay -->
                        <div class="layout-overlay layout-menu-toggle"></div>

                        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
                        <div class="drag-target"></div>

                        {$this->loadJS($page)}
                EOD;
            case 'geo_location_today_plan':
                $geoLocationTodayPlanContent = $this->setGeoLocationTodayPlanned();
                $navbarContent = $this->setNavbar();
                $menuContent = $this->setMenu();

                return <<<EOD
                        <body>
                            <!-- Layout wrapper -->
                            <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
                                <!-- Layout container -->
                                <div class="layout-container">
                                {$navbarContent}
                                    <div class="layout-page">
                                        <!-- Content wrapper -->
                                        <div class="content-wrapper">
                                            {$menuContent}
                                            {$geoLocationTodayPlanContent}
                                            {$footerContent}
                                        <div class="content-backdrop fade"></div>
                                        </div>
                                        <!--/ Content wrapper -->
                                    </div>
    
                                    </div>
                                    <!--/ Layout container -->
                            </div>
                            <!--/ Layout wrapper -->
    
                            <!-- Overlay -->
                            <div class="layout-overlay layout-menu-toggle"></div>
    
                            <!-- Drag Target Area To SlideIn Menu On Small Screens -->
                            <div class="drag-target"></div>
    
                            {$this->loadJS($page)}
                EOD;
            case 'today_withdrawal_jo':
                $todayWithdrawalContent = $this->setTodayWithdrawalJO();
                $navbarContent = $this->setNavbar();
                $menuContent = $this->setMenu();

                return <<<EOD
                        <body>
                            <!-- Layout wrapper -->
                            <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
                                <!-- Layout container -->
                                <div class="layout-container">
                                {$navbarContent}
                                    <div class="layout-page">
                                        <!-- Content wrapper -->
                                        <div class="content-wrapper">
                                            {$menuContent}
                                            {$todayWithdrawalContent}
                                            {$footerContent}
                                        <div class="content-backdrop fade"></div>
                                        </div>
                                        <!--/ Content wrapper -->
                                    </div>
    
                                    </div>
                                    <!--/ Layout container -->
                            </div>
                            <!--/ Layout wrapper -->
    
                            <!-- Overlay -->
                            <div class="layout-overlay layout-menu-toggle"></div>
    
                            <!-- Drag Target Area To SlideIn Menu On Small Screens -->
                            <div class="drag-target"></div>
    
                            {$this->loadJS($page)}
                EOD;
            default:
                return "<!-- No Body Shown for page: $page! -->";
        }
    }

    public function bodyClose()
    {
        echo "</body>";
    }
}