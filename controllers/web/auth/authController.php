<?php

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

class authController
{
    private $log;
    private $config;
    private $medooDB;
    private $tableUser;
    private $tableSession;
    private $aes256cbc;
    private $error;

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

        $this->tableUser = $this->config['DATABASE']['TB_USER'];
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

    public function authSession()
    {
        $callingFile = str_replace(".php", "", basename(debug_backtrace()[0]['file']));

        $redirectTo = null;

        if (isset($_SESSION['user_data'])) {
            /**
             * Session Exists!
             */
            $sessionJSON = $this->aes256cbc->decryptAES256CBC($_SESSION['user_data']);
            $session = json_decode($sessionJSON, true);
            $email = isset($session['email']) ? $session['email'] : '';
            $username = isset($session['username']) ? $session['username'] : '';
            $sessionDBData = $this->medooDB->get($this->tableSession, ["expired"], [
                "email" => $email,
                "username" => $username,
                // "id_session" => session_id()
            ]);
            if ($sessionDBData) {
                if (date('Y-m-d H:i:s') > $sessionDBData['expired']) {
                    session_destroy();
                    session_start();
                    session_regenerate_id(true);
                    $newResponse = [
                        'status' => 'warning',
                        'message' => 'Your session is expired! Please re-login.'
                    ];
                    $_SESSION['response'] = json_encode($newResponse);
                    $redirectTo = 'login.php';
                } else {
                    switch (strtolower($callingFile)) {
                        case 'login':
                        case 'index':
                            $response = [
                                'status' => 'success',
                                'message' => 'Welcome back!'
                            ];
                            $_SESSION['response'] = json_encode($response);
                            $redirectTo = 'dashboard.php';
                            break;
                    }
                }
            } else {
                session_destroy();
                session_start();
                session_regenerate_id(true);
                $newResponse = [
                    'status' => 'warning',
                    'message' => 'Your session is expired! Please re-login.'
                ];
                $_SESSION['response'] = json_encode($newResponse);
                $redirectTo = 'login.php';
            }
        } else {
            /**
             * Session not exists!
             */
            switch (strtolower($callingFile)) {
                case 'today_jo_plan':
                case 'assigned_jo':
                case 'geo_location_today_plan':
                case 'dashboard':
                    // case 'index':
                    $response = [
                        'status' => 'error',
                        'message' => 'You are not authenticate! Please login first!'
                    ];
                    $_SESSION['response'] = json_encode($response);
                    http_response_code(401);
                    $redirectTo = 'login.php';
                    break;
            }
        }

        return $redirectTo;
    }
}