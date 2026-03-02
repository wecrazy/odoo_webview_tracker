<?php
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    protected $log;
    protected $logFile;

    protected function setUp(): void {
        $this->log = new log();
        $this->logFile = $GLOBALS['logFile'];
    }

    protected function tearDown(): void {
        restore_error_handler();
        restore_exception_handler();
        
        if (file_exists($this->logFile)) {
            // unlink($this->logFile);
        }
    }

    public function testLogMessage() {
        $message = "Ini test";
        $this->log->createLogMessage($message);
        $this->assertFileExists($this->logFile, message: "Log file should be created.");
        $logContents = file_get_contents($this->logFile);
        $this->assertStringContainsString($message, $logContents, "Log message should be written to the log file.");
    }
}
