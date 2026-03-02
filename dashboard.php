<?php
session_start();

/*-----------------------
|:                     :|
|:  Vendor/Autoload    :|
|:                     :|
-----------------------*/
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php'
];

$autoloadFound = false;

foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    throw new \Exception('Autoload file not found. Please run `composer install`.');
}

use Symfony\Component\Yaml\Yaml;

require_once('controllers/hash/aes256cbc.php');

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

require_once('public/views/layouts/html_dashboard.php');
$htmlInstance = new html();

require_once('public/views/layouts/header.php');
$headerInstance = new header($title, $titleFancy);

if (isset($_SESSION['user_data'])) {
    $aes256cbc = new aes256cbc();
    $sessionJSON = $aes256cbc->decryptAES256CBC($_SESSION['user_data']);
    $userDataSession = json_decode($sessionJSON, true);

     /*-----------------------
    |:                     :|
    |:  Path: Config       :|
    |:                     :|
    -----------------------*/
    $configPaths = [
        realpath(__DIR__ . '/config/conf.yaml'),
        realpath(dirname(__DIR__) . '/config/conf.yaml'),
        realpath(dirname(__DIR__, 2) . '/config/conf.yaml'),
        realpath(dirname(__DIR__, 3) . '/config/conf.yaml')
    ];

    $configFile = null;

    foreach ($configPaths as $configPath) {
        if ($configPath && file_exists($configPath)) {
            $configFile = $configPath;
            break;
        }
    }

    if ($configFile === null) {
        throw new \Exception('Config file not found!');
    }

    try {
        $config = Yaml::parseFile($configFile);
    } catch (\Exception $e) {
        throw new \Exception('Failed to parse the YAML config file: ' . $e->getMessage());
    }
}

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

// session_destroy();
// print_r($_SESSION);

// Remove session
unset($_SESSION['response']);