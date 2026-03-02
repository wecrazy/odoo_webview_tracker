<?php

use Symfony\Component\Yaml\Yaml;

date_default_timezone_set('Asia/Jakarta');

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

class refreshDataJOPlanTodayScheduler
{
    private $log;
    private $config;
    private $error;
    private $mailer;
    private $todayJOPlanInstance;
    private $guzzleClient;

    public function __construct()
    {
        /*-----------------------
        |:                     :|
        |:  Path: Log          :|
        |:                     :|
        -----------------------*/
        $logPaths = [
            __DIR__ . '/controllers/log/log.php',
            dirname(__DIR__) . '/controllers/log/log.php',
            dirname(__DIR__, 2) . '/controllers/log/log.php'
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
            __DIR__ . '/controllers/web/error/errorController.php',
            dirname(__DIR__) . '/controllers/web/error/errorController.php',
            dirname(__DIR__, 2) . '/controllers/web/error/errorController.php'
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
        |:  Path: Mail         :|
        |:                     :|
        -----------------------*/
        $paths = [
            __DIR__ . '/controllers/mail/mail.php',
            dirname(__DIR__) . '/controllers/mail/mail.php',
            dirname(__DIR__, 2) . '/controllers/mail/mail.php'
        ];

        $pathFound = false;

        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $pathFound = true;
                break;
            }
        }
        if (!$pathFound) {
            throw new \Exception('Mailer path not found!');
        }
        $this->mailer = new mail();

        /*-----------------------
        |:                     :|
        |:  Controllers:       :|
        |:  Today JO Plan      :|
        |:                     :|
        -----------------------*/

        $paths = [
            __DIR__ . '/controllers/web/today-jo-plan/todayJOPlanController.php',
            dirname(__DIR__) . '/controllers/web/today-jo-plan/todayJOPlanController.php',
            dirname(__DIR__, 2) . '/controllers/web/today-jo-plan/todayJOPlanController.php',
        ];

        $pathFound = false;

        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $pathFound = true;
                break;
            }
        }

        if (!$pathFound) {
            throw new \Exception('Today JO Plan Controller path not found!');
        }

        $this->todayJOPlanInstance = new todayJOPlanController();
    }

    public function refreshData() {
        $response = $this->todayJOPlanInstance->getPlannedJOToday();
        if ($response) {
            $this->log->createLogMessage("--- SUCCESS : JO Planned Today Success Updated!");
        }
    }
}

$techniciansInstance = new refreshDataJOPlanTodayScheduler();
$techniciansInstance->refreshData();