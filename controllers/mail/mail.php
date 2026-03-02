<?php
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php'
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

use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Yaml\Yaml;

class mail {
    private $log;
    private $config;
    private $mailer;

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

        $this->mailer = new PHPMailer();
    }

    public function sendMail($subject, $body, $to, $cc, $bcc, $attachment) {
        $maxRetries = $this->config['EMAIL']['MAX_RETRY'];
        $retryCount = 0;
        $retryDelay = $this->config['EMAIL']['RETRY_DELAY'];
    
        while ($retryCount < $maxRetries) {
            try {
                $this->mailer->isSMTP();
                $this->mailer->CharSet = $this->config['EMAIL']['CHARSET'];
                $this->mailer->Host = $this->config['EMAIL']['HOST'];
                $this->mailer->SMTPAuth = $this->config['EMAIL']['AUTH'];
                $this->mailer->Username = $this->config['EMAIL']['USERNAME'];
                $this->mailer->Password = $this->config['EMAIL']['PASSWORD'];
                $this->mailer->SMTPSecure = $this->config['EMAIL']['SECURE'];
                $this->mailer->Port = $this->config['EMAIL']['PORT'];
                $this->mailer->setFrom($this->config['EMAIL']['USERNAME'], 'Reporting Email Service');
                if (!empty($to) && is_array($to)) {
                    foreach ($to as $address) {
                        $this->mailer->addAddress($address);
                    }
                }
                if (!empty($cc) && is_array($cc)) {
                    foreach ($cc as $ccMail) {
                        $this->mailer->addCC($ccMail);
                    }
                }
                if (!empty($bcc) && is_array($bcc)) {
                    foreach ($bcc as $bccMail) {
                        $this->mailer->addBCC($bccMail);
                    }
                }
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
                $this->mailer->isHTML($this->config['EMAIL']['ISHTML']);

                if ($attachment && is_array($attachment)) {
                    foreach ($attachment as $originalUrl => $renameTo) {
                        $this->mailer->addAttachment($originalUrl, $renameTo);
                    }
                }
                
                if ($this->mailer->send()) {
                    $logMsg = "Email to: " . json_encode($to);
                    if (!empty($subject)) {
                        $logMsg .= PHP_EOL . "Subject: $subject";
                    }
                    if (!empty($body)) {
                        $logMsg .= PHP_EOL . "Body: $body";
                    }
                    if (!empty($cc)) {
                        $logMsg .= PHP_EOL . "CC: " . json_encode($cc);
                    }
                    if (!empty($bcc)) {
                        $logMsg .= PHP_EOL . "BCC: " . json_encode($bcc);
                    }
                    if (!empty($attachment)) {
                        $logMsg .= PHP_EOL . "Attachment: " . json_encode($attachment);
                    }
                    $logMsg .= PHP_EOL . "--- Success send!";

                    $this->log->createLogMessage($logMsg);
                    return true;
                } else {
                    $this->log->createLogMessage($this->mailer->ErrorInfo);
                    return false;
                }
            } catch (Exception $e) {
                $this->log->createLogMessage("Error: " . $e->getMessage());
                $retryCount++;
                sleep($retryDelay);
            }
        }
    
        $this->log->createLogMessage("Failed after $maxRetries retries: Unable to send email");
        return false;
    }
}