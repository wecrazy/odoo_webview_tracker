<?php
use PHPUnit\Framework\TestCase;

class ODOOTest extends TestCase
{
    protected $odoo;
    protected $logFile;

    protected function setUp(): void {
        $this->odoo = new odoo_api();
        $this->logFile = $GLOBALS['logFile'] ?? 'default_log_file.log'; // Provide a default if $GLOBALS['logFile'] is not set
    }

    protected function tearDown(): void {
        restore_error_handler();
        restore_exception_handler();
        
        if (file_exists($this->logFile)) {
            // unlink($this->logFile);
        }
    }

    public function testAuthSession() {
        $params = [
            "jsonrpc" => "2.0",
                "params" => [
                    "domain" => [
                        ["x_received_datetime_spk", ">=", "2024-09-11 00:00:00"],
                        ["x_received_datetime_spk", "<=", "2024-09-12 00:00:00"],
                        ["company_id", "=", "MTI"],
                        ["x_task_type", "ilike", "Replacement"],
                        ["stage_id", "!=", "Cancel"]
                    ],
                    "model" => "helpdesk.ticket",
                    "fields" => [
                        "name",
                        "id",
                        "stage_id",
                        "x_task_type",
                        "x_merchant_sn_edc",
                        "technician_id",
                        "complete_datetime_wo",
                        "x_wo_remark",
                    ],
                    "order" => "id asc"
                ]
        ];

        $result = $this->odoo->OdooAPI('GetData', $params);
        $this->assertIsArray($result, 'ODOO Result is array!');
    }
}
