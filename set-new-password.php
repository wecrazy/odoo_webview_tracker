<?php

session_start();

if (isset($_SESSION['new-password'])) {
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

    $bodyInstance->bodyClose();

    $htmlInstance->htmlClose();

    unset($_SESSION['response']);

} else {
    // i think u must send this to http error code !! 
    $response = [
        'status' => 'warning',
        'message' => 'You already has a password! Cannot set the new one!'
    ];
    $_SESSION['response'] = json_encode($response);
    header("Location: login.php");
    exit();
}