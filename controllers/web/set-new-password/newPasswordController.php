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

class newPasswordController {
    private $log;
    private $config;
    private $medooDB;
    private $tableUser;
    private $tableSession;
    private $aes256cbc;
    private $mail;
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

        /*-----------------------
        |:                     :|
        |:  Mailer             :|
        |:                     :|
        -----------------------*/
        $mailPaths = [
            __DIR__ . '/mail/mail.php',
            dirname(__DIR__) . '/mail/mail.php',
            dirname(__DIR__, 2) . '/mail/mail.php'
        ];

        $pathFound = false;

        foreach ($mailPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $pathFound = true;
                break;
            }
        }

        if (!$pathFound) {
            throw new \Exception('Mailer Path not found!');
        }
        $this->mail = new mail();
    }

    public function setNewPassword($email, $password) {
        $loginPage = '/' . $this->config['APP']['BASE_DIR'] . '/login.php';

        try {
            $encryptPwd = $this->aes256cbc->encryptAES256CBC($password);
            $updateNewPwd = $this->medooDB->update($this->tableUser, ["password" => $encryptPwd], [
                "email" => $email
            ]);

            if ($updateNewPwd) {
                /**
                 * Email
                 */
                // $loginPublicURL = "https://localhost/" . $this->config['APP']['BASE_DIR'] . "/login.php"; // => Local dev
                $loginPublicURL = $this->config['APP']['PUBLIC_URL'] . '/' . $this->config['APP']['BASE_DIR'] . '/login.php';
                $tokenLogin = base64_encode(json_encode([
                    "email" => $email,
                    "password" => $password
                ]));
                $verifyURL = $loginPublicURL . '?token=' . $tokenLogin;
                $subject = 'New Password Updated!';
                $htmlContent = <<<EOD
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        body {
                            font-family: 'Arial', sans-serif;
                            margin: 0;
                            padding: 0;
                            background-color: #f9f9f9;
                            color: #333;
                        }
                        .email-container {
                            max-width: 600px;
                            margin: 0 auto;
                            background-color: #ffffff;
                            padding: 20px;
                            border-radius: 10px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        }
                        .email-header {
                            text-align: center;
                            padding: 10px 0;
                            background-color: #4CAF50;
                            color: #ffffff;
                            border-top-left-radius: 10px;
                            border-top-right-radius: 10px;
                        }
                        .email-content {
                            padding: 20px;
                            font-size: 16px;
                            line-height: 1.5;
                            text-align: center;
                        }
                        .email-footer {
                            margin-top: 20px;
                            font-size: 12px;
                            color: #999;
                            text-align: center;
                        }
                        .verify-button {
                            display: inline-block;
                            margin-top: 20px;
                            padding: 10px 20px;
                            font-size: 16px;
                            color: #fff; /* Text color */
                            background-color: #4CAF50; /* Background color */
                            text-decoration: none; /* No underline */
                            border-radius: 5px;
                        }

                        /* This will apply when the button is hovered over */
                        .verify-button:hover {
                            background-color: #45a049; /* Darker background color on hover */
                            color: #fff; /* Keep text color white on hover */
                        }

                        /* Add this to prevent link color change on hover or focus */
                        .verify-button:visited {
                            color: #fff; /* Keep text color white for visited links */
                        }

                        .verify-button:focus {
                            outline: none; /* Remove outline when focused */
                            color: #fff; /* Keep text color white when focused */
                        }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <div class="email-header">
                            <h1>Your New Journey Awaits!</h1>
                        </div>
                        <div class="email-content">
                            <p>Hello, <i>{$email}</i></p>
                            <p>Congratulations on taking the next step! Your new password: <h1>{$password}</h1> is ready, and your journey into our dashboard begins now. Click the button below to dive in and explore everything we have in store for you.</p>
                            <a href="{$verifyURL}" class="verify-button">Go Explore!</a>
                            <p>If the button doesn’t work, copy and paste the following link into your browser:</p>
                            <p><a href="{$verifyURL}">{$verifyURL}</a></p>
                        </div>
                        <div class="email-footer">
                            &copy; 2024 Made with < / >. All rights reserved.
                        </div>
                    </div>
                </body>
                </html>
                EOD;
                $body = $htmlContent;
                $to = [];
                $to[] = $email;
                $cc = null;
                $bcc = null;
                $attachment = null;
                $newPwdMail = $this->mail->sendMail($subject, $body, $to, $cc, $bcc, $attachment);

                $response = [
                    'status' => 'info',
                    'message' => 'Your new password success updated! Now, you can access the dashboard!'
                ];
                $_SESSION['response'] = json_encode($response);
                unset($_SESSION['new-password']);
                header('Location: ' . $loginPage);
                exit();
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
        $newPasswordInstance = new newPasswordController();
    
        if (isset($_POST['action']) && $_POST['action'] === 'set-new-password') {
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $confirmPwd = isset($_POST['confirm-password']) ? $_POST['confirm-password'] : '';
            if ($password === $confirmPwd) {
                $newPasswordInstance->setNewPassword($email, $password);
            } else {
                $error = new errorController();
                $errorCode = 500;
                $errorPage = $error->getPathError($errorCode, null);
                $_SESSION['error_message'] = "Unmatch password: $password and confirm password: $confirmPwd ! For email: $email";
                http_response_code($errorCode);
                header("Location: $errorPage");
                exit();
            }
        } else {
            $error = new errorController();
            $errorCode = 400;
            $errorPage = $error->getPathError($errorCode, null);
            http_response_code($errorCode);
            header("Location: $errorPage");
            exit();
        }
    } else {
        $newPasswordInstance = new newPasswordController();
    
        $error = new errorController();
        $errorCode = 400;
        $errorPage = $error->getPathError($errorCode, null);
        http_response_code($errorCode);
        header("Location: $errorPage");
        exit();
    }
}

unset($_SESSION['new-password']);