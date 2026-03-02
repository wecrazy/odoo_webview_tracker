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

class databaseController
{
    private $log;
    private $config;
    private $error;

    private static $instance = null;
    private $connection;
    private $projectTaskConnection;
    private $projectTaskConnectionLastMonth;

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
            __DIR__ . '/web/error/errorController.php',
            dirname(__DIR__) . '/web/error/errorController.php',
            dirname(__DIR__, 2) . '/web/error/errorController.php'
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

        try {
            $this->connection = new Medoo([
                'type' => $this->config['DATABASE']['TYPE'],
                'host' => $this->config['DATABASE']['HOST'],
                'port' => $this->config['DATABASE']['PORT'],
                'database' => $this->config['DATABASE']['NAME'],
                'username' => $this->config['DATABASE']['USERNAME'],
                'password' => $this->config['DATABASE']['PASSWORD'],
                'charset' => $this->config['DATABASE']['CHARSET'],
                'logging' => $this->config['DATABASE']['LOGGING'],
                'error' => constant($this->config['DATABASE']['ERROR']),
            ]);

            /**
             * Current Month
             */
            $projectTaskDBName = strtolower("project_task_" . date('F_Y'));
            $pdo = new PDO(
                "mysql:host=" . $this->config['DATABASE_PROJECT_TASK']['HOST'],
                $this->config['DATABASE_PROJECT_TASK']['USERNAME'],
                $this->config['DATABASE_PROJECT_TASK']['PASSWORD']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$projectTaskDBName`");

            $this->projectTaskConnection = new Medoo([
                'type' => $this->config['DATABASE_PROJECT_TASK']['TYPE'],
                'host' => $this->config['DATABASE_PROJECT_TASK']['HOST'],
                'port' => $this->config['DATABASE_PROJECT_TASK']['PORT'],
                'database' => $projectTaskDBName,
                'username' => $this->config['DATABASE_PROJECT_TASK']['USERNAME'],
                'password' => $this->config['DATABASE_PROJECT_TASK']['PASSWORD'],
                'charset' => $this->config['DATABASE_PROJECT_TASK']['CHARSET'],
                'logging' => $this->config['DATABASE_PROJECT_TASK']['LOGGING'],
                'error' => constant($this->config['DATABASE_PROJECT_TASK']['ERROR']),
            ]);

            /**
             * Prev Month
             */
            $projectTaskDBNameLastMonth = strtolower("project_task_" . date('F_Y', strtotime('last month')));
            $this->projectTaskConnectionLastMonth = new Medoo([
                'type' => $this->config['DATABASE_PROJECT_TASK']['TYPE'],
                'host' => $this->config['DATABASE_PROJECT_TASK']['HOST'],
                'port' => $this->config['DATABASE_PROJECT_TASK']['PORT'],
                'database' => $projectTaskDBNameLastMonth,
                'username' => $this->config['DATABASE_PROJECT_TASK']['USERNAME'],
                'password' => $this->config['DATABASE_PROJECT_TASK']['PASSWORD'],
                'charset' => $this->config['DATABASE_PROJECT_TASK']['CHARSET'],
                'logging' => $this->config['DATABASE_PROJECT_TASK']['LOGGING'],
                'error' => constant($this->config['DATABASE_PROJECT_TASK']['ERROR']),
            ]);

        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            $errorCode = 500;
            $errorURL = $this->error->getPathError($errorCode, $e->getMessage());
            http_response_code($errorCode);
            header("Location: $errorURL");
            exit();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new databaseController();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getProjectTaskConnection()
    {
        return $this->projectTaskConnection;
    }

    public function getProjectTaskConnectionLastMonth()
    {
        return $this->projectTaskConnectionLastMonth;
    }

}