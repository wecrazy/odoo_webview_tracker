<?php

session_start();

require_once('controllers/web/auth/authController.php');
$authInstance = new authController();
$redirectTo = $authInstance->authSession();

if ($redirectTo) {
    $currentFile = basename(__FILE__);
    if ($currentFile != $redirectTo) {
        header("Location: $redirectTo");
    }
} else {
    header("Location: login.php");
}