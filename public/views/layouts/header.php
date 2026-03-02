<?php

class header
{
    public function __construct($page, $title)
    {
        echo <<<EOD
            <head>
                <meta charset="utf-8" />
                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
                {$this->setTitle($title)}
                <meta name="description" content="" />                                                                         
                {$this->setCSS($page)}
            </head>
        EOD;
    }

    public function setTitle($title)
    {
        return <<<EOD
            <title>$title</title>
        EOD;
    }

    public function setCSS($page)
    {
        $mainCSS = <<<EOD
            <!-- Favicon -->
            <link rel="icon" type="image/x-icon" href="public/assets/img/favicon/favicon.ico" />

            <!-- Fonts -->
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
            <link
            href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
            rel="stylesheet" />

            <!-- Icons -->
            <link rel="stylesheet" href="public/assets/fonts/boxicons.css" />
            <link rel="stylesheet" href="public/assets/fonts/fontawesome.css" />
            <link rel="stylesheet" href="public/assets/fonts/flag-icons.css" />

            <!-- Core CSS -->
            <link rel="stylesheet" href="public/assets/css/rtl/core.css" class="template-customizer-core-css" />
            <link rel="stylesheet" href="public/assets/css/rtl/theme-default.css" class="template-customizer-theme-css" />
            <link rel="stylesheet" href="public/assets/css/demo.css" />

            <!-- Libs CSS -->
            <link rel="stylesheet" href="public/libs/perfect-scrollbar/perfect-scrollbar.css" />
            <link rel="stylesheet" href="public/libs/typeahead-js/typeahead.css" />

            <!-- Jquery -->
            <script src="public/libs/jquery/jquery.js"></script>

            <!-- IziToast -->
            <link rel="stylesheet" href="public/libs/izitoast/iziToast.css" />
            
            <!-- Logout -->
            <script src="public/assets/js/dashboard/logout.js"></script>
        EOD;

        switch (strtolower($page)) {
            case 'login':
            case 'set new password':
                $mainCSS .= <<<EOD
                    <link rel="stylesheet" href="public/libs/@form-validation/umd/styles/index.min.css" />

                    <!-- Page CSS -->
                    <link rel="stylesheet" href="public/assets/css/pages/page-auth.css" />

                    <!-- Helpers -->
                    <script src="public/assets/js/helpers.js"></script>
                    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
                    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
                    <script src="public/assets/js/template-customizer.js"></script>
                    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
                    <script src="public/assets/js/config.js"></script>
                EOD;
                return $mainCSS;
            // break;
            case 'dashboard':
                $mainCSS .= <<<EOD
                    <!-- Helpers -->
                    <script src="public/assets/js/helpers.js"></script>
                    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
                    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
                    <script src="public/assets/js/template-customizer.js"></script>
                    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
                    <script src="public/assets/js/config.js"></script>

                    <!-- Datatables -->
                    <link rel="stylesheet" href="public/libs/datatables-bs5/datatables.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
                    <link rel="stylesheet" href="public/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/flatpickr/flatpickr.css" />
                    <!-- Form Validation -->
                    <link rel="stylesheet" href="public/libs/@form-validation/umd/styles/index.min.css" />
                    <!-- Spinkit -->
                    <link rel="stylesheet" href="public/libs/spinkit/spinkit.css" />
                    
                EOD;
                return $mainCSS;
            // break;
            case 'today_jo_plan':
                $mainCSS .= <<<EOD
                    <!-- Helpers -->
                    <script src="public/assets/js/helpers.js"></script>
                    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
                    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
                    <script src="public/assets/js/template-customizer.js"></script>
                    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
                    <script src="public/assets/js/config.js"></script>

                    <!-- Datatables -->
                    <link rel="stylesheet" href="public/libs/datatables-bs5/datatables.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
                    <link rel="stylesheet" href="public/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/flatpickr/flatpickr.css" />
                    <!-- Form Validation -->
                    <link rel="stylesheet" href="public/libs/@form-validation/umd/styles/index.min.css" />
                    <!-- Spinkit -->
                    <link rel="stylesheet" href="public/libs/spinkit/spinkit.css" />
                EOD;
                return $mainCSS;
            // break;
            case 'assigned_jo':
                $mainCSS .= <<<EOD
                    <!-- Helpers -->
                    <script src="public/assets/js/helpers.js"></script>
                    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
                    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
                    <script src="public/assets/js/template-customizer.js"></script>
                    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
                    <script src="public/assets/js/config.js"></script>

                    <!-- Datatables -->
                    <link rel="stylesheet" href="public/libs/datatables-bs5/datatables.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
                    <link rel="stylesheet" href="public/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/flatpickr/flatpickr.css" />
                    <!-- Form Validation -->
                    <link rel="stylesheet" href="public/libs/@form-validation/umd/styles/index.min.css" />
                    <!-- Spinkit -->
                    <link rel="stylesheet" href="public/libs/spinkit/spinkit.css" />
                EOD;
                return $mainCSS;
            case 'geo_location_today_plan':
                $mainCSS .= <<<EOD
                    <!-- Helpers -->
                    <script src="public/assets/js/helpers.js"></script>
                    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
                    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
                    <script src="public/assets/js/template-customizer.js"></script>
                    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
                    <script src="public/assets/js/config.js"></script>

                    <!-- Spinkit -->
                    <link rel="stylesheet" href="public/libs/spinkit/spinkit.css" />

                    <!-- Leaflet -->
                    <link rel="stylesheet" href="public/libs/leaflet/leaflet.css" />
                    <!-- Leaflet Plugin -->
                    <!-- <link rel="stylesheet" href="public/libs/leaflet/plugins/brunob-fullscreen/Control.Fullscreen.css" /> -->
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/zimmicz-coordinates/Control.Coordinates.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/norkart-minimap/Control.MiniMap.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/coryasilva-extramarkers/leaflet.extra-markers.min.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/elmarquis-gesturehandling/leaflet-gesture-handling.min.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/mapshakers-iconpulse/L.Icon.Pulse.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/stefanocudini-search/leaflet-search.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/ppete2-polylinemeasure/Leaflet.PolylineMeasure.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/aazuspan-legend/feature-legend.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/stefanocudini-panellayers/leaflet-panel-layers.css" />
                    <link rel="stylesheet" href="public/libs/leaflet/plugins/leaflet-fullscreen/leaflet.fullscreen.css" />

                    <!-- Tom Select -->
                    <link rel="stylesheet" href="public/libs/tom-select/tom-select.bootstrap5.css" />
                    <link rel="stylesheet" href="public/libs/tom-select/tom-select.min.css" />

                    <!-- Bootstrap Date Range Picker -->
                    <link rel="stylesheet" href="public/libs/bootstrap-datepicker/bootstrap-datepicker.css" />
                    <link rel="stylesheet" href="public/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css" />
                EOD;
                return $mainCSS;
            case 'today_withdrawal_jo':
                $mainCSS .= <<<EOD
                        <!-- Helpers -->
                        <script src="public/assets/js/helpers.js"></script>
                        <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
                        <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
                        <script src="public/assets/js/template-customizer.js"></script>
                        <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
                        <script src="public/assets/js/config.js"></script>
                    EOD;
                return $mainCSS;
            default:
                return <<<EOD
                    <!-- No css data found for page: $page -->
                EOD;
            // break;
        }
    }
}