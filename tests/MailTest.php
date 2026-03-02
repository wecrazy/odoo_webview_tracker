<?php
use PHPUnit\Framework\TestCase;

class MailTest extends TestCase
{
    protected $mail;
    protected $logFile;


    
    protected function setUp(): void {
        $this->mail = new send_mail();
        $this->logFile = $GLOBALS['logFile'];
    }
    protected function tearDown(): void {
        restore_error_handler();
        restore_exception_handler();
        
        if (file_exists($this->logFile)) {
            // unlink($this->logFile);
        }
    }

    public function testSendEmailReport() {
        $attach = null;
        $subject = 'test';
        $body = 'inibody';
        $result = $this->mail->sendEmailReport($subject, $body, $attach);

        $this->assertTrue($result, "The email should be sent successfully.");
    }
}
