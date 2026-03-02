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

class techniciansLoginScheduler
{
    private $log;
    private $config;
    private $error;
    private $mailer;
    private $techLoginInstance;
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
        |:  Technicians        :|
        |:  Login              :|
        |:                     :|
        -----------------------*/
        $paths = [
            __DIR__ . '/controllers/web/technicians-login/techLoginController.php',
            dirname(__DIR__) . '/controllers/web/technicians-login/techLoginController.php',
            dirname(__DIR__, 2) . '/controllers/web/technicians-login/techLoginController.php'
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
            throw new \Exception('Technicians Login Controller path not found!');
        }

        $this->techLoginInstance = new techLoginController();
    }

    public function sendTechLoginReport()
    {
        try {
            $dateGetData = date('d F Y h.i A');
            $newFileName = "Technician_Login(" . date('dFY') . ').xlsx';
            $JSONData = $this->techLoginInstance->generateExcelTechnicians();
            $data = json_decode($JSONData, true);
            if ($data) {
                $link = "../" .  $data['link'];
                if (file_exists($link)) {
                    $subject = "Technicians Login Report @" . $dateGetData;
                    $body = <<<EOD
                        Dear team, <br>
                        With this we attach report about Technicians Login @{$dateGetData}.
                        <br><br><br>
                        <i>Regards,</i><br>
                        <b>PT. Smartweb Indonesia Kreasi</b>
                    EOD;
                    $to = [];
                    // $to[] = "gilly@csna4u.com";
                    // $to[] = "flaksana@csna4u.com";
                    // $to[] = "hendra@smartwebindonesia.com";
                    // Admin RM
                    // $to[] = "ade_i@smartwebindonesia.com";
                    // $to[] = "nida_ndrh@smartwebindonesia.com";
                    // $to[] = "puspintha@smartwebindonesia.com";
                    // $to[] = "lutfiana@smartwebindonesia.com";
                    // $to[] = "erika_n@smartwebindonesia.com";
                    // $to[] = "kirana_a@smartwebindonesia.com";
                    // $to[] = "tia_s@smartwebindonesia.com";
                    // $to[] = "vera_p@smartwebindonesia.com";
                    // $to[] = "yustika_w@smartwebindonesia.com";
                    // $to[] = "steven_c@smartwebindonesia.com";
                    // $to[] = "dimas_a@smartwebindonesia.com";
                    // $to[] = "yokwan_w@smartwebindonesia.com";
                    // $to[] = "tomy_a@smartwebindonesia.com";
                    $to[] = "fauziab@csna4u.com";
                    $cc = [];
                    $cc[] = "nickenb@csna4u.com";
                    $cc[] = "heriandi@csna4u.com";
                    $cc[] = "budi.sumantri@csna4u.com";
                    $cc[] = "osvaldomdonaldo@csna4u.com";
                    $cc[] = "tomibustami@csna4u.com";
                    $cc[] = "burhan@csna4u.com";
                    $cc[] = "muchammadanggayudha@csna4u.com";
                    // $cc[] = "ardi@smartwebindonesia.com";
                    // $cc[] = "lilimarlina@smartwebindonesia.com";
                    $cc[] = "made@smartwebindonesia.com";
                    // $cc[] = "oliver@csna4u.com";
                    // $cc[] = "cindychang@cybersoft4u.com";
                    $cc[] = "nadia.kristina@csna4u.com";

                    // $cc[] = "tetty.sintauli@smartwebindonesia.com";
                    $bcc = null;
                    $attachment = [];
                    $attachment[$link] = $newFileName;
                    $sendReport = $this->mailer->sendMail($subject, $body, $to, $cc, $bcc, $attachment);
                    if ($sendReport) {
                        $this->log->createLogMessage("--- SUCCESS : Technicians Login Report Send!");
                        exit();
                    } else {
                        $this->log->createLogMessage("Report technicians login did not send successfully");
                        exit();
                    }
                } else {
                    $this->log->createLogMessage("File not exists!");
                    exit();
                }
            } else {
                $this->log->createLogMessage("Not data to send!");
                exit();
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            exit();
        }
    }
}

$techniciansInstance = new techniciansLoginScheduler();
$techniciansInstance->sendTechLoginReport();
