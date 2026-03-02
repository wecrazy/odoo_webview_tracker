<?php

session_start();

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

use Medoo\Medoo;
use Symfony\Component\Yaml\Yaml;

class logoutController {
    private $log;
    private $config;
    private $medooDB;
    private $tableSession;
    private $error;
    private $aes256cbc;

    public function __construct()
    {
        /*-----------------------
        |:                     :|
        |:  Path: Log          :|
        |:                     :|
        -----------------------*/
        $logPaths = [
            __DIR__ . '/log/log.php',
            dirname(__DIR__) . '/log/log.php',
            dirname(__DIR__, 2) . '/log/log.php'
        ];

        $logPathFound = false;

        foreach ($logPaths as $logPath) {
            if (file_exists($logPath)) {
                require_once $logPath;
                $logPathFound = true;
                break;
            }
        }

        if (!$logPathFound) {
            throw new \Exception('Log path not found!');
        }
        $log = new log();
        $this->log = $log;

        /*-----------------------
       |:                     :|
       |:  Error              :|
       |:                     :|
       -----------------------*/
        $errorPaths = [
            __DIR__ . '/error/errorController.php',
            dirname(__DIR__) . '/error/errorController.php',
            dirname(__DIR__, 2) . '/error/errorController.php'
        ];

        $errorPathFound = false;

        foreach ($errorPaths as $errorPath) {
            if (file_exists($errorPath)) {
                require_once $errorPath;
                $errorPathFound = true;
                break;
            }
        }

        if (!$errorPathFound) {
            throw new \Exception('Error path not found!');
        }
        $this->error = new errorController();

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
            $this->config = Yaml::parseFile($configFile);
        } catch (\Exception $e) {
            throw new \Exception('Failed to parse the YAML config file: ' . $e->getMessage());
        }

        /*-----------------------
        |:                     :|
        |:  Init: DB           :|
        |:                     :|
        -----------------------*/
        $dbPaths = [
            __DIR__ . '/database/databaseController.php',
            dirname(__DIR__) . '/database/databaseController.php',
            dirname(__DIR__, 2) . '/database/databaseController.php'
        ];

        $pathFound = false;

        foreach ($dbPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $pathFound = true;
                break;
            }
        }

        if (!$pathFound) {
            throw new \Exception('Database Path not found!');
        }
        $this->medooDB = databaseController::getInstance()->getConnection();

        $this->tableSession = $this->config['DATABASE']['TB_SESSION'];

        /*-----------------------
        |:                     :|
        |:  Hash:              :|
        |:  AES-256-CBC        :|
        |:                     :|
        -----------------------*/
        $aes256cbcPaths = [
            __DIR__ . '/hash/aes256cbc.php',
            dirname(__DIR__) . '/hash/aes256cbc.php',
            dirname(__DIR__, 2) . '/hash/aes256cbc.php'
        ];

        $pathFound = false;

        foreach ($aes256cbcPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $pathFound = true;
                break;
            }
        }

        if (!$pathFound) {
            throw new \Exception('AES-256-CBC Path not found!');
        }
        $aes256cbc = new aes256cbc();
        $this->aes256cbc = $aes256cbc;
    }

    public function logout() {
        $response = [
            "status" => '',
            "message" => ''
        ];

        if (isset($_SESSION['user_data'])) {
            $jsonUserDataSession = $this->aes256cbc->decryptAES256CBC($_SESSION['user_data']);
            $userData = json_decode($jsonUserDataSession, true);

            $email = isset($userData['email']) ? $userData['email'] : '';
            $deleteSession = $this->medooDB->delete($this->tableSession, ["email" => $email]);
            if ($deleteSession->rowCount() > 0) {
                $loginPage = '/' . $this->config['APP']['BASE_DIR'] . '/login.php';

                $response['status'] = 'success';
                $response['message'] = 'You are logged out!';
                session_destroy();
                session_start();
                session_regenerate_id(true);
                $newResponse = [
                    'status' => 'info',
                    'message' => 'You are logged out!'
                ];
                $_SESSION['response'] = json_encode($newResponse);
                $this->log->createLogMessage("Email: $email is logged out.");
                $response['page'] = $loginPage;
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Cannot logout! data of session not found!';    
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Cannot logout! session not found!';
        }

        return $response;
    }
}

if (isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $logoutInstance = new logoutController();
        if (isset($_POST['action']) && $_POST['action'] === 'logout') {
            $result = $logoutInstance->logout();
            echo json_encode($result);
        } else {
            $error = new errorController();
            $errorCode = 400;
            $errorPage = $error->getPathError($errorCode, null);
            http_response_code($errorCode);
            header("Location: $errorPage");
            exit();
        }
    } else {
        $logoutInstance = new logoutController();
        $error = new errorController();
        $errorCode = 400;
        $errorPage = $error->getPathError($errorCode, null);
        http_response_code($errorCode);
        header("Location: $errorPage");
        exit();
    }
}