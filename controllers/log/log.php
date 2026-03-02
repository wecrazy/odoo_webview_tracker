<?php 

date_default_timezone_set("Asia/Jakarta");

class log {
    private $logFile;

    public function __construct() {
        $this->logFile = $this->makeLogDir();
        set_error_handler([$this, 'customErrorHandler']);
        register_shutdown_function([$this, 'customShutdownFunction']);
    }

    private function makeLogDir() {
        $timezone = new DateTimeZone("Asia/Jakarta");
        $datetime = new DateTime("now", $timezone);
        $datetime->setTimezone($timezone);

        $Ymd = $datetime->format("Y-m-d");

        $logDirs = [
            __DIR__ . '/../logs',
            __DIR__ . '/../../logs',
            __DIR__ . '/../../../logs',
        ];
        
        $logPath = null;
        
        foreach ($logDirs as $dir) {
            if (is_dir($dir)) {
                $logPath = $dir . '/' . $Ymd;
                break;
            }
        }
        
        if ($logPath !== null) {
            if (!is_dir($logPath)) {
                mkdir($logPath, 0777, true);
            }
        
            $logFile = $logPath . '/log.txt';
        
            if (!file_exists($logFile)) {
                touch($logFile);
            }
            $GLOBALS['logFile'] = $logFile;
            return $logFile;
        } else {
            echo '\n▓▓ No valid log directory found.';
        }
    }

    public function createLogMessage($message) {
        $logMessage = "[". date("d-M-Y H:i:s") . "] " . $message;
        file_put_contents($this->logFile, $logMessage . PHP_EOL, FILE_APPEND);
    }

    public function customErrorHandler($errno, $errstr, $errfile, $errline) {
        $errorMessage = "Error: [$errno] $errstr in $errfile on line $errline";
        $this->createLogMessage($errorMessage);
    }

    public function customShutdownFunction() {
        $error = error_get_last();
        if ($error !== null) {
            $errno = isset($error['type']) ? $error['type'] : 0;
            $errstr = isset($error['message']) ? $error['message'] : '';
            $errfile = isset($error['file']) ? $error['file'] : '';
            $errline = isset($error['line']) ? $error['line'] : '';
            $errorMessage = "Fatal error: [$errno] $errstr in $errfile on line $errline";
            $this->createLogMessage($errorMessage);
        }
    }

}