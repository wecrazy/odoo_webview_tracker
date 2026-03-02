<?php

session_start();
// print_r($_SESSION);

require_once('controllers/web/auth/authController.php');
$authInstance = new authController();
$redirectTo = $authInstance->authSession();

if ($redirectTo) {
    $currentFile = basename(__FILE__);
    if ($currentFile != $redirectTo) {
        header("Location: $redirectTo");
    }
}

require_once('controllers/web/titleController.php');
$titleInstance = new titleController();
$title = $titleInstance->title;
$titleFancy = $titleInstance->fancyText($title);

require_once('public/views/layouts/html_login.php');
$htmlInstance = new html();

require_once('public/views/layouts/header.php');
$headerInstance = new header($title, $titleFancy);

require_once('public/views/layouts/body.php');
$bodyInstance = new body($title);

if (isset($_SESSION['response'])) {
    $sessionResponse = json_decode($_SESSION['response'], true);
    if (isset($sessionResponse['status'])) {
        $htmlNotif = <<<EOD
            <div id="show-izi-toast" data-izi-status="{$sessionResponse['status']}" data-izi-message="{$sessionResponse['message']}">
                <!--  -->
            </div>
        EOD;
        echo $htmlNotif;
    }
}

if (isset($_GET['token'])) {
    $jsonData = json_decode(base64_decode($_GET['token']), true);
    $token = <<<EOD
        <div id="token-automatic" data-email="{$jsonData['email']}" data-password="{$jsonData['password']}">
        </div>
    EOD;
    echo $token;
}

$bodyInstance->bodyClose();

$htmlInstance->htmlClose();

// Remove session
unset($_SESSION['response']);
unset($_SESSION['new-password']);