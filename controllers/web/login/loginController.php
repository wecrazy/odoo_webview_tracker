<?php
session_start();
session_regenerate_id(true);

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

class loginController
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

    public function login($email, $password)
    {
        $loginPage = '/' . $this->config['APP']['BASE_DIR'] . '/login.php';
        $dashboardPage = '/' . $this->config['APP']['BASE_DIR'] . '/dashboard.php';
        $adminPage = '/' . $this->config['APP']['BASE_DIR'] . '/admin/admin.php';
        $setPwdPage = '/' . $this->config['APP']['BASE_DIR'] . '/set-new-password.php';

        $email = isset($email) ? $email : '';
        $password = isset($password) ? $password : '';

        try {
            $countUser = $this->medooDB->count($this->tableUser, ['email'], ['email' => $email]);
            if ($countUser > 0) {
                /** User exists */
                $userData = $this->medooDB->get($this->tableUser, ['username', 'password', 'company', 'sac_group', 'role', 'img'], ['email' => $email]);

                $sessionID = session_id();
                $timeExpired = time() + (5 * 3600);  // +5 Hours dateNow !!!!
                $dateTimeExpired = date('Y-m-d H:i:s', $timeExpired);
                $username = $userData['username'] ? $userData['username'] : '';
                $dbPwd = $userData['password'] ? $userData['password'] : '';
                $company = $userData['company'] ? $userData['company'] : '';
                $sacGroup = $userData['sac_group'] ? $userData['sac_group'] : '';
                $role = $userData['role'] ? $userData['role'] : '';
                $img = $userData['img'] ? $userData['img'] : '';
                $encryptPwd = $this->aes256cbc->encryptAES256CBC($password);
                $decryptedDBPwd = $this->aes256cbc->decryptAES256CBC($dbPwd);
                if ($dbPwd == null) {
                    $response = [
                        "email" => $email
                    ];
                    $_SESSION['new-password'] = json_encode($response);
                    $response = [
                        'status' => 'info',
                        'message' => 'Please set the new password for login.'
                    ];
                    $_SESSION['response'] = json_encode($response);
                    header('Location: ' . $setPwdPage);
                    exit();
                }
                // $this->medooDB->delete($this->tableSession, ["email" => $email]);        // delete test !!
                else if ($password === $decryptedDBPwd) {
                    $sessionData = $this->medooDB->count($this->tableSession, ['email'], ['email' => $email]);
                    if ($sessionData > 0) {
                        $sessionResult = $this->medooDB->update($this->tableSession, [
                            'id_session' => $sessionID,
                            'expired' => $dateTimeExpired
                        ], ['email' => $email]);
                        if ($sessionResult) {
                            $userData = [
                                'role' => $role,
                                'company' => $company,
                                'img' => $img,
                                'username' => $username,
                                'sac_group' => $sacGroup,
                                'email' => $email,
                            ];
                            $jsonUserData = json_encode($userData);
                            $encryptJsonUserData = $this->aes256cbc->encryptAES256CBC($jsonUserData);
                            $_SESSION['user_data'] = $encryptJsonUserData;

                            switch (strtolower($role)) {
                                case 'administrator':
                                    $response = [
                                        'status' => 'success',
                                        'message' => 'You are logged in!'
                                    ];
                                    $_SESSION['response'] = json_encode($response);
                                    header('Location: ' . $adminPage);
                                    break;
                                case 'admin-rm':
                                case 'sac':
                                case 'pm':
				case 'hrd':
				case 'head':
                                case 'sco':
                                    $response = [
                                        'status' => 'success',
                                        'message' => 'You are logged in!'
                                    ];
                                    $_SESSION['response'] = json_encode($response);
                                    header('Location: ' . $dashboardPage);
                                    break;
                                default:
                                    $errorCode = 406;
                                    $errorMsg = "Role not accepted!";
                                    $errorURL = $this->error->getPathError($errorCode, $errorMsg);
                                    http_response_code($errorCode);
                                    header("Location: $errorURL");
                                    break;
                            }
                        } else {
                            $this->log->createLogMessage($this->medooDB->error);
                            $response = [
                                'status' => 'error',
                                'message' => $this->medooDB->error
                            ];
                            $_SESSION['response'] = json_encode($response);
                            header('Location: ' . $loginPage);
                            exit();
                        }
                    } else {
                        $sessionResult = $this->medooDB->insert($this->tableSession, [
                            'id_session' => $sessionID,
                            'username' => $username,
                            'email' => $email,
                            'company' => $company,
                            'role' => $role,
                            'expired' => $dateTimeExpired
                        ]);
                        if ($sessionResult) {
                            $userData = [
                                'role' => $role,
                                'company' => $company,
                                'img' => $img,
                                'username' => $username,
                                'sac_group' => $sacGroup,
                                'email' => $email
                            ];
                            $jsonUserData = json_encode($userData);
                            $encryptJsonUserData = $this->aes256cbc->encryptAES256CBC($jsonUserData);
                            $_SESSION['user_data'] = $encryptJsonUserData;

                            switch (strtolower($role)) {
                                case 'administrator':
                                    $response = [
                                        'status' => 'success',
                                        'message' => 'You are logged in!'
                                    ];
                                    $_SESSION['response'] = json_encode($response);
                                    header('Location: ' . $adminPage);
                                    break;
                                case 'admin-rm':
                                case 'sac':
                                case 'pm':
				case 'hrd':
				case 'head':
                                case 'sco':
                                    $response = [
                                        'status' => 'success',
                                        'message' => 'You are logged in!'
                                    ];
                                    $_SESSION['response'] = json_encode($response);
                                    header('Location: ' . $dashboardPage);
                                    break;
                                default:
                                    $errorCode = 406;
                                    $errorMsg = "Role not accepted!";
                                    $errorURL = $this->error->getPathError($errorCode, $errorMsg);
                                    http_response_code($errorCode);
                                    header("Location: $errorURL");
                                    break;
                            }
                        } else {
                            $this->log->createLogMessage($this->medooDB->error);
                            $response = [
                                'status' => 'error',
                                'message' => $this->medooDB->error
                            ];
                            $_SESSION['response'] = json_encode($response);
                            header('Location: ' . $loginPage);
                            exit();
                        }
                    }
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid password!'
                    ];
                    $_SESSION['response'] = json_encode($response);
                    header('Location: ' . $loginPage);
                    exit();
                }
            } else {
                /** User not exists */
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid email!'
                ];
                $_SESSION['response'] = json_encode($response);
                $loginPage = '/' . $this->config['APP']['BASE_DIR'] . '/login.php';
                header('Location: ' . $loginPage);
                exit();
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $_SESSION['response'] = json_encode($response);
            $loginPage = '/' . $this->config['APP']['BASE_DIR'] . '/login.php';
            header('Location: ' . $loginPage);
            exit();
        }
    }
}

if (isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $loginInstance = new loginController();

        if (isset($_POST['action']) && $_POST['action'] === 'login') {
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $loginInstance->login($email, $password);
        } else {
            $error = new errorController();
            $errorCode = 400;
            $errorPage = $error->getPathError($errorCode, null);
            http_response_code($errorCode);
            header("Location: $errorPage");
            exit();
        }
    } else {
        $loginInstance = new loginController();

        $error = new errorController();
        $errorCode = 400;
        $errorPage = $error->getPathError($errorCode, null);
        http_response_code($errorCode);
        header("Location: $errorPage");
        exit();
    }
}
