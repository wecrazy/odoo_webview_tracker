<?php
session_start();
// print_r($_SESSION);
?>

<!DOCTYPE html>

<html
  lang="en"
  class="light-style layout-wide customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="public/assets/"
  data-template="horizontal-menu-template">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Error: 500</title>

    <meta name="description" content="" />

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

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="public/assets/css/pages/page-misc.css" />

    <!-- Helpers -->
    <script src="public/assets/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="public/assets/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="public/assets/js/config.js"></script>
  </head>

  <body>
    <!-- Content -->

    <!-- Error -->
    <div class="container-xxl container-p-y">
      <div class="misc-wrapper">
      <h1 class="mb-2 mx-2" style="line-height: 6rem; font-size: 6rem">500</h1>
            <h4 class="mb-5 mx-2">Internal Server Error ⚠️</h4>
            <?php 
              if (isset($_SESSION['error_message']) && $_SESSION['error_message']) {
                $errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : 'Default error message';
                $errorContent = <<<EOD
                    <p class="mb-2 mx-2">{$errorMessage}</p>
                EOD;
                // <<<EOD
                // <div class="mb-3 d-flex flex-column align-items-center gap-6">
                //     <h3 class="mb-1 text-"><button class="btn btn-sm btn-danger" disabled>{$_SERVER['REQUEST_METHOD']}</button> &nbsp;
                //         <mark>{$_SERVER['REQUEST_URI']}</mark></h3>
                // </div>
                // EOD;
                echo $errorContent;
                unset($_SESSION['error_message']);
              }
            ?>
            <br>
            <a href="login.php" class="btn btn-primary">Go Back</a>
        <div class="mt-3">
          <img
            src="public/assets/img/illustrations/page-misc-error-light.png"
            alt="page-misc-error-light"
            width="500"
            class="img-fluid"
            data-app-dark-img="illustrations/page-misc-error-dark.png"
            data-app-light-img="illustrations/page-misc-error-light.png" />
        </div>
      </div>
    </div>
    <!-- /Error -->

    <!-- / Content -->

    <!-- Core JS -->
    <!-- build:js public/assets/js/core.js -->

    <script src="public/assets/js/bootstrap.js"></script>

    <!-- Libs -->
    <script src="public/libs/jquery/jquery.js"></script>
    <script src="public/libs/popper/popper.js"></script>
    <script src="public/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="public/libs/hammer/hammer.js"></script>
    <script src="public/libs/i18n/i18n.js"></script>
    <script src="public/libs/typeahead-js/typeahead.js"></script>
    <!-- / Libs -->

    <script src="public/assets/js/menu.js"></script>

    <!-- Main JS -->
    <script src="public/assets/js/main.js"></script>

    <!-- Page JS -->
  </body>
</html>
