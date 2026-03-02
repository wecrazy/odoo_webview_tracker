<?php

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

use Medoo\Medoo;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\Yaml\Yaml;

class assignedJOController
{
    private $log;
    private $config;
    private $mainDB;
    private $projectTaskDB;
    private $projectTaskDBLastMonth;
    private $error;
    private $api;
    private $tableNewAssignedJO;
    private $tableTechnicians;

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
        $this->mainDB = databaseController::getInstance()->getConnection();
        $this->projectTaskDB = databaseController::getInstance()->getProjectTaskConnection();
        $this->projectTaskDBLastMonth = databaseController::getInstance()->getProjectTaskConnectionLastMonth();

        $this->tableNewAssignedJO = $this->config['DATABASE_PROJECT_TASK']['TB_NEW_ASSIGNED_JO'];
        $this->tableTechnicians = $this->config['DATABASE']['TB_TECHNICIANS'];
        try {
            $this->projectTaskDB->create($this->tableNewAssignedJO, [
                "id" => ["INT", "NOT NULL", "AUTO_INCREMENT"],
                "technician" => ["VARCHAR(150)", "NOT NULL"],
                "planned_date" => ["DATETIME"],
                "long" => ["TEXT"],
                "lat" => ["TEXT"],
                "wo_number" => ["VARCHAR(100)", "NOT NULL"],
                "company" => ["VARCHAR(50)", "NOT NULL"],
                "task_type" => ["VARCHAR(100)"],
                "sla_deadline" => ["DATETIME"],
                "sac_group" => ["VARCHAR(50)", "NOT NULL"],
                "data_on" => ["DATETIME", "DEFAULT CURRENT_TIMESTAMP"],
                "received_spk" => ["DATETIME"],
                "ticket_id" => ["VARCHAR(50)"],
                "ticket_name" => ["TEXT"],
                "merchant_mid" => ["TEXT"],
                "merchant_tid" => ["TEXT"],
                "merchant_name" => ["TEXT"],
                "PRIMARY KEY (<id>)"
            ]);
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
        }

        /*-----------------------
        |:                     :|
        |:  API: ODOO          :|
        |:                     :|
        -----------------------*/
        $odooAPIPaths = [
            __DIR__ . '/api/odoo_api.php',
            dirname(__DIR__) . '/api/odoo_api.php',
            dirname(__DIR__, 2) . '/api/odoo_api.php'
        ];

        $pathFound = false;

        foreach ($odooAPIPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $pathFound = true;
                break;
            }
        }

        if (!$pathFound) {
            throw new \Exception('API ODOO Path not found!');
        }
        $this->api = new odoo_api();

    }

    private function regional($name)
    {
        $parts = explode(' ', $name);
        $first_word = $parts[0];
        $cleaned_word = preg_replace('/[^a-zA-Z0-9]/', '', $first_word);
        return $cleaned_word;
    }

    private function plus7Hours($datetime)
    {
        $newDatetimeValue = null;
        $newDatetime = new DateTime($datetime);
        if ($newDatetime && $newDatetime->format('Y-m-d H:i:s') === $datetime) {
            $newDatetime->modify('+7 hours');
            $newDatetimeValue = $newDatetime->format('Y-m-d H:i:s');
        }
        return $newDatetimeValue;
    }

    public function getNewAssignedJO()
    {
        $companyAllowedString = $this->config['ODOO_DATA']['COMPANY_ALLOWED'];
        $companyAllowedArray = array_map('intval', explode(',', $companyAllowedString));

        $IDtechnicians = [];
        $dataTech = $this->mainDB->select($this->tableTechnicians, ["id"]);
        foreach ($dataTech as $data) {
            $IDtechnicians[] = isset($data['id']) ? $data['id'] : null;
        }

        $createdOnStart = date('2024-01-01 00:00:00');
        $createdOnEnd = date('Y-12-31 23:59:59');

        $params = [
            "jsonrpc" => $this->config["ODOO_API"]["JSONRPC"],
            "params" => [
                "domain" => [
                    ["active", "=", true],
                    ["stage_id", "=", "New"],
                    ["technician_id", "=", $IDtechnicians],
                    ["create_date", ">=", $createdOnStart],
                    ["create_date", "<=", $createdOnEnd],
                    ["company_id", "=", $companyAllowedArray]
                ],
                "model" => "project.task",
                "fields" => [
                    "id",
                    "technician_id",
                    "planned_date_begin",
                    "x_latitude",
                    "x_longitude",
                    "x_longitude",
                    "x_no_task",
                    "company_id",
                    "x_task_type",
                    "x_sla_deadline",
                    // "timesheet_timer_last_stop",
                    // "write_uid",
                    // "write_date",
                    "x_received_datetime_spk",
                    "helpdesk_ticket_id",
                    "x_cimb_master_mid",
                    "x_cimb_master_tid",
                    "x_merchant"
                ],
                "order" => "id asc"
            ]
        ];

        $ODOOResponse = $this->api->OdooAPI('GetData', $params);
        if (!empty($ODOOResponse)) {
            try {
                $this->projectTaskDB->pdo->beginTransaction();
                $this->projectTaskDB->delete($this->tableNewAssignedJO, []);
                $this->projectTaskDB->pdo->commit();

                $this->projectTaskDB->pdo->beginTransaction();

                $sql = "INSERT INTO `$this->tableNewAssignedJO` (
                    id, technician, planned_date, `long`, `lat`, wo_number, company, task_type, sla_deadline, sac_group, received_spk, ticket_id, ticket_name, merchant_mid, merchant_tid, merchant_name
                ) VALUES ";

                $placeholders = [];
                $bindValues = [];

                foreach ($ODOOResponse as $index => $data) {
                    $id = isset($data['id']) ? $data['id'] : '';
                    $technician = isset($data['technician_id'][1]) ? $data['technician_id'][1] : '';
                    if (empty($technician)) {
                        $technician = 'Unknown';
                    }
                    $sacGroup = $this->regional($technician);
                    $plannedDate = isset($data['planned_date_begin']) ? $data['planned_date_begin'] : '';
                    if (empty($plannedDate)) {
                        $plannedDate = null;
                    } else {
                        $plannedDate = $this->plus7Hours($plannedDate);
                    }
                    $latitude = isset($data['x_latitude']) ? $data['x_latitude'] : '';
                    if (empty($latitude)) {
                        $latitude = null;
                    }
                    $longitude = isset($data['x_longitude']) ? $data['x_longitude'] : '';
                    if (empty($longitude)) {
                        $longitude = null;
                    }
                    $woNumber = isset($data['x_no_task']) ? $data['x_no_task'] : '';
                    $company = isset($data['company_id'][1]) ? $data['company_id'][1] : '';
                    $taskType = isset($data['x_task_type']) ? $data['x_task_type'] : '';
                    $slaDeadline = isset($data['x_sla_deadline']) ? $data['x_sla_deadline'] : '';
                    if (empty($slaDeadline)) {
                        $slaDeadline = null;
                    } else {
                        $slaDeadline = $this->plus7Hours($slaDeadline);
                    }
                    $receivedSpk = isset($data['x_received_datetime_spk']) ? $data['x_received_datetime_spk'] : null;
                    if (empty($receivedSpk)) {
                        $receivedSpk = null;
                    } else {
                        $receivedSpk = $this->plus7Hours($receivedSpk);
                    }
                    $ticketID = isset($data['helpdesk_ticket_id'][0]) ? $data['helpdesk_ticket_id'][0] : null;
                    $ticketName = isset($data['helpdesk_ticket_id'][1]) ? $data['helpdesk_ticket_id'][1] : null;
                    if (!empty($ticketID) && !empty($ticketName)) {
                        $ticketName = str_replace(" (#$ticketID)", '', $ticketName);
                    }
                    $merchantMID = isset($data['x_cimb_master_mid']) ? $data['x_cimb_master_mid'] : null;
                    $merchantTID = isset($data['x_cimb_master_tid']) ? $data['x_cimb_master_tid'] : null;
                    $merchantName = isset($data['x_merchant']) ? $data['x_merchant'] : null;

                    $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                    $bindValues[] = $id;
                    $bindValues[] = $technician;
                    $bindValues[] = $plannedDate;
                    $bindValues[] = $longitude;
                    $bindValues[] = $latitude;
                    $bindValues[] = $woNumber;
                    $bindValues[] = $company;
                    $bindValues[] = $taskType;
                    $bindValues[] = $slaDeadline;
                    $bindValues[] = $sacGroup;
                    $bindValues[] = $receivedSpk;
                    $bindValues[] = $ticketID;
                    $bindValues[] = $ticketName;
                    $bindValues[] = $merchantMID;
                    $bindValues[] = $merchantTID;
                    $bindValues[] = $merchantName;
                }

                $sql .= implode(', ', $placeholders);

                $stmt = $this->projectTaskDB->pdo->prepare($sql);
                $stmt->execute($bindValues);

                $this->projectTaskDB->pdo->commit();

                $response = [
                    'status' => 'success',
                    'message' => 'Success update New Assigned JO for Technicians @' . date('Y-m-d H:i:s')
                ];
                return json_encode($response);
            } catch (Exception $e) {
                $this->log->createLogMessage($e->getMessage());
                $this->projectTaskDB->pdo->rollBack();
                return null;
            }
        } else {
            $msg = "Empty odoo response for new assigned jo planned!";
            $this->log->createLogMessage($msg);
            return $msg;
        }
    }

    public function getLastUpdateNewAssignedJOData()
    {
        $response = [];
        try {
            $lastUpdate = $this->projectTaskDB->pdo->query("SELECT MIN(data_on) as last_update FROM $this->tableNewAssignedJO")->fetchColumn();
            $result = null;
            if (!empty($lastUpdate)) {
                $result = DateTime::createFromFormat('Y-m-d H:i:s', $lastUpdate)->format('d F Y H:i:s');
            }
            $response['status'] = 'success';
            $response['message'] = $result;
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            $response['status'] = 'error';
            $response['message'] = $e->getMessage();
        }
        return json_encode($response);
    }

    public function getDatatables()
    {
        $response = [
            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ];

        $start = isset($_POST['start']) ? intval(value: $_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 7;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

        $order = isset($_POST['order']) ? $_POST['order'] : [];
        $columns = isset($_POST['columns']) ? $_POST['columns'] : [];

        // $orderBy = ['id' => 'ASC'];

        $columnMap = [
            1 => 'id',
            2 => 'technician',
            3 => 'sac_group',
            4 => 'planned_date',
            5 => 'wo_number',
            6 => 'company',
            7 => 'task_type',
            8 => 'sla_deadline',
            9 => 'data_on'
        ];

        if (!empty($order)) {
            $orderBy = [];
            foreach ($order as $ord) {
                $columnIndex = intval($ord['column']);
                $dir = $ord['dir'] === 'desc' ? 'DESC' : 'ASC';

                if (isset($columnMap[$columnIndex])) {
                    $orderBy[$columnMap[$columnIndex]] = $dir;
                }
            }
        }

        $totalRecords = $this->projectTaskDB->count($this->tableNewAssignedJO);
        $response['recordsTotal'] = $totalRecords;

        $searchConditions = [];
        if (!empty($searchValue)) {
            $searchConditions = [
                'OR' => [
                    'technician[~]' => $searchValue,
                    'wo_number[~]' => $searchValue,
                    'company[~]' => $searchValue,
                    'task_type[~]' => $searchValue,
                    'sac_group[~]' => $searchValue
                ]
            ];
        }

        $totalFilteredRecords = $this->projectTaskDB->count($this->tableNewAssignedJO, $searchConditions);
        $response['recordsFiltered'] = $totalFilteredRecords;

        // $data = $this->projectTaskDB->select($this->tableNewAssignedJO, '*', [
        $data = $this->projectTaskDB->select($this->tableNewAssignedJO, [
            "id",
            "technician",
            "planned_date",
            "sac_group",
            "wo_number",
            "company",
            "task_type",
            "sla_deadline",
            "data_on"
        ], [
                'LIMIT' => [$start, $length],
                'ORDER' => $orderBy ? $orderBy : ['id' => 'ASC'],
            ] + $searchConditions);

        foreach ($data as $row) {
            $response['data'][] = [
                'id' => $row['id'],
                'technician' => $row['technician'],
                'planned_date' => $row['planned_date'],
                'sac_group' => $row['sac_group'],
                'wo_number' => $row['wo_number'],
                'company' => $row['company'],
                'task_type' => $row['task_type'],
                'sla_deadline' => $row['sla_deadline'],
                'data_on' => $row['data_on']
            ];
        }

        echo json_encode($response);
    }

    private function initExcelFile()
    {
        $publicFilesDir = [
            __DIR__ . '/../public/files',
            __DIR__ . '/../../public/files',
            __DIR__ . '/../../../public/files',
        ];

        $publicFilesPath = null;
        $randomID = md5(uniqid(rand(1, 100), true));

        foreach ($publicFilesDir as $dir) {
            if (is_dir($dir)) {
                $publicFilesPath = $dir . '/' . date('Y-m-d');
            }
        }

        if ($publicFilesPath !== null) {
            if (!is_dir($publicFilesPath)) {
                mkdir($publicFilesPath, 0777, true);
            }

            $excelFile = $publicFilesPath . "/$randomID.xlsx";
            if (!file_exists($excelFile)) {
                touch($excelFile);
            }
            return $excelFile;
        } else {
            $this->log->createLogMessage('Not found public files!');
            return null;
        }
    }

    public function generateExcelNewJOLeft()
    {
        try {
            $lastUpdateNewAssignJO = $this->projectTaskDB->min($this->tableNewAssignedJO, "data_on");
            if ($lastUpdateNewAssignJO) {
                $lastUpdateTime = DateTime::createFromFormat('Y-m-d H:i:s', $lastUpdateNewAssignJO)->format('H.i');
                $excelFile = $this->initExcelFile();
                if (file_exists($excelFile)) {
                    if (is_readable($excelFile)) {
                        $dbData = $this->projectTaskDB->select($this->tableNewAssignedJO, [
                            // "id",
                            "technician",
                            "sac_group",
                            "planned_date",
                            "wo_number",
                            "company",
                            "task_type",
                            "sla_deadline",
                            "data_on"
                        ]);

                        if (!empty($dbData)) {
                            $spreadSheet = new Spreadsheet();
                            $sheet = $spreadSheet->getActiveSheet();
                            $sheet->setTitle('New Left JO @' . $lastUpdateTime);

                            /**
                             * @MasterData
                             */
                            // ---------------------------------------------------------------------------------------------------
                            $headerMapping = [
                                'technician' => 'Technician',
                                'sac_group' => 'SAC Group',
                                'planned_date' => 'JO Planned At',
                                'wo_number' => 'Work Order Number',
                                'company' => 'Company',
                                'task_type' => 'Task Type',
                                'sla_deadline' => 'SLA Deadline',
                                'data_on' => 'ODOO Data Get On',
                            ];
                            $row = 1;
                            $colTitles = array_keys($dbData[0]);
                            $col = 'A';
                            foreach ($colTitles as $header) {
                                $displayHeader = isset($headerMapping[$header]) ? $headerMapping[$header] : $header;
                                $sheet->setCellValue($col . $row, $displayHeader);
                                $col++;
                            }
                            $row++;

                            foreach ($dbData as $data) {
                                $col = 'A';
                                foreach ($data as $value) {
                                    $sheet->setCellValue($col . $row, $value);
                                    $col++;
                                }
                                $row++;
                            }

                            foreach (range('A', $col) as $columnID) {
                                $sheet->getColumnDimension($columnID)->setAutoSize(true);
                            }
                            // ---------------------------------------------------------------------------------------------------

                            /**
                             * @PivotData
                             */
                            // continue here to create the pivot !!!!!!!!!!!!!!!!

                            $writer = IOFactory::createWriter($spreadSheet, 'Xlsx');
                            $writer->save($excelFile);

                            $normalizedPath = str_replace('\\', '/', $excelFile);

                            if (preg_match('/\/\d{4}-\d{2}-\d{2}\/[^\/]+\.xlsx$/', $normalizedPath, $matches)) {
                                $finalExcelURLToDownload = 'public/files' . $matches[0];
                                $result = [
                                    'status' => 'success',
                                    'link' => $finalExcelURLToDownload
                                ];
                                return json_encode($result);
                            } else {
                                return null;
                            }
                        } else {
                            $this->log->createLogMessage("No data found for Engineers New Left JO.");
                            // return null;
                            return "No data found for Engineers New Left JO.";
                        }
                    } else {
                        $this->log->createLogMessage("File: $excelFile is not readable");
                        return null;
                    }
                } else {
                    $this->log->createLogMessage("File: $excelFile is not exists!");
                    return null;
                }
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return null;
        }
    }

    public function getListTechniciansInconsistentAndNotWork()
    {
        try {
            // =================================================================================================================================================
            // // OLD Static Declare Days
            // // $tbToday = strtolower(date('F_d'));
            // $daystoget = 2;
            // $tbYesterday = strtolower(date('F_d', strtotime('-1 day')));
            // $tbMin2Days = strtolower(date('F_d', strtotime('-2 days')));

            // $existing_tables = [];
            // $tables_to_check = [$tbYesterday, $tbMin2Days];

            // foreach ($tables_to_check as $table) {
            //     $tableCheck = $this->projectTaskDB->query(
            //         "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
            //         [
            //             ":table" => $table,
            //         ]
            //     )->fetchColumn();

            //     if ($tableCheck) {
            //         $existing_tables[] = $table;
            //     } else {
            //         $this->log->createLogMessage("$table is not exists in DB");
            //     }
            // }
            // =================================================================================================================================================
            $daystoget = $this->config['DATABASE_PROJECT_TASK']['DAYS_ASSIGNED'];
            $existing_tables = [];

            $tables_to_check = [];
            for ($i = 1; $i <= $daystoget; $i++) {
                $tableName = strtolower(date('F_d', strtotime("-$i days")));
                $tables_to_check[] = $tableName;
            }

            foreach ($tables_to_check as $table) {
                $tableCheck = $this->projectTaskDB->query(
                    "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
                    [
                        ":table" => $table,
                    ]
                )->fetchColumn();

                if ($tableCheck) {
                    $existing_tables[] = $table;
                } else {
                    $this->log->createLogMessage("Table: $table does not exist in DB");
                }
            }

            $technicianNamesFromTables = [];

            if (!empty($existing_tables)) {
                foreach ($existing_tables as $table) {
                    $stmt = $this->projectTaskDB->pdo->prepare("SELECT DISTINCT technician FROM `$table`");
                    $stmt->execute();
                    $techData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $uniqueTechnicians = array_unique(array_column($techData, 'technician'));
                    $technicianNamesFromTables[] = $uniqueTechnicians;
                }

                $existingTechnicians = array_reduce($technicianNamesFromTables, function ($carry, $technicians) {
                    if (empty($carry)) {
                        return $technicians;
                    }
                    return array_intersect($carry, $technicians);
                }, []);

                sort($existingTechnicians);

                $notWorkTechnicians = [];
                $inconsistentTechnicians = [];
                foreach ($existingTechnicians as $tech) {
                    $countTables = count($existing_tables);
                    $notWorkTimes = 0;
                    $inconsistentStatus = null;
                    foreach ($existing_tables as $table) {
                        $historyJO = $this->projectTaskDB->select($table, [
                            "total_jo" => Medoo::raw("COUNT(*)"),
                            "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                        ], [
                            "technician" => $tech
                        ]);
                        if ($historyJO) {
                            $totalJO = isset($historyJO[0]['total_jo']) ? $historyJO[0]['total_jo'] : 0;
                            $joNew = isset($historyJO[0]['total_new']) ? $historyJO[0]['total_new'] : 0;
                            if ($totalJO === $joNew) {
                                $notWorkTimes++;
                            }
                        }
                    }
                    if ($notWorkTimes > 0 && $notWorkTimes < $countTables) {
                        $inconsistentStatus = true;
                    }

                    if ($inconsistentStatus) {
                        $inconsistentTechnicians[] = $tech;
                    }

                    if ($notWorkTimes === $countTables) {
                        $notWorkTechnicians[] = $tech;
                    }
                }

                if (empty($notWorkTechnicians) && empty($inconsistentTechnicians)) {
                    $this->log->createLogMessage("Empty data for not work & inconsistent technicians!");
                    return null;
                } else {
                    $result = [
                        'NotWork' => $notWorkTechnicians,
                        'Inconsistent' => $inconsistentTechnicians
                    ];
                    return $result;
                }
            } else {
                return null;
            }

        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return null;
        }

    }

    public function getDatatablesInconsistentTechnicians()
    {
        $technicianList = $this->getListTechniciansInconsistentAndNotWork();
        if (!empty($technicianList)) {
            $inconsistentTechnicians = isset($technicianList['Inconsistent']) ? $technicianList['Inconsistent'] : null;
            if (empty($inconsistentTechnicians)) {
                return null;
            } else {
                try {
                    $daystoget = $this->config['DATABASE_PROJECT_TASK']['DAYS_ASSIGNED'];
                    $existing_tables = [];

                    $tables_to_check = [];
                    for ($i = 1; $i <= $daystoget; $i++) {
                        $tableName = strtolower(date('F_d', strtotime("-$i days")));
                        $tables_to_check[] = $tableName;
                    }

                    foreach ($tables_to_check as $table) {
                        $tableCheck = $this->projectTaskDB->query(
                            "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
                            [
                                ":table" => $table,
                            ]
                        )->fetchColumn();

                        $tableCheckLastMonth = $this->projectTaskDBLastMonth->query(
                            "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
                            [
                                ":table" => $table,
                            ]
                        )->fetchColumn();

                        if ($tableCheck) {
                            $existing_tables[] = $table;
                        } else {
                            $this->log->createLogMessage("Table: $table does not exist in DB");
                        }

                        if ($tableCheckLastMonth) {
                            $existing_tables[] = $table;
                        }
                    }

                    if (empty($existing_tables)) {
                        return null;
                    } else {
                        sort($existing_tables);

                        $response = [
                            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
                            'recordsTotal' => 0,
                            'recordsFiltered' => 0,
                            'data' => []
                        ];

                        $start = isset($_POST['start']) ? intval(value: $_POST['start']) : 0;
                        $length = isset($_POST['length']) ? intval($_POST['length']) : 7;
                        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

                        $order = isset($_POST['order']) ? $_POST['order'] : [];
                        $columns = isset($_POST['columns']) ? $_POST['columns'] : [];

                        $orderBy = ['technician' => 'ASC'];

                        $columnMap = [
                            0 => 'technician'
                        ];

                        if (!empty($order)) {
                            $orderBy = [];
                            foreach ($order as $ord) {
                                $columnIndex = intval($ord['column']);
                                $dir = $ord['dir'] === 'desc' ? 'DESC' : 'ASC';

                                if (isset($columnMap[$columnIndex])) {
                                    $orderBy[$columnMap[$columnIndex]] = $dir;
                                }
                            }
                        }

                        $commonConditions = [
                            "technician" => $inconsistentTechnicians
                        ];
                        $filteredConditions = array_merge($commonConditions, [
                            'OR' => [
                                "technician[~]" => $searchValue
                            ]
                        ]);

                        /**
                         * @TotalRecords
                         */
                        $finalResult = [];
                        foreach ($existing_tables as $table) {
                            $inconsistentTechniciansData = $this->projectTaskDB->select($table, [
                                "technician",
                                "total_jo" => Medoo::raw("COUNT(*)"),
                                "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                            ], [
                                "AND" => $commonConditions,
                                "GROUP" => "technician",
                                "ORDER" => $orderBy
                            ]);
                            foreach ($inconsistentTechniciansData as $data) {
                                $technician = isset($data['technician']) ? $data['technician'] : '';
                                $totalJO = isset($data['total_jo']) ? $data['total_jo'] : 0;
                                $totalNew = isset($data['total_new']) ? $data['total_new'] : 0;

                                if (!isset($finalResult[$technician])) {
                                    $finalResult[$technician] = [
                                        'technician' => $technician,
                                        'data' => []
                                    ];
                                }
                                $finalResult[$technician]['data'][] = $totalJO;
                                $finalResult[$technician]['data'][] = $totalNew;

                            }
                        }
                        $finalOutput = [];
                        foreach ($finalResult as $tech) {
                            $finalOutput[] = array_merge([$tech['technician']], $tech['data']);
                        }
                        $response['recordsTotal'] = count($finalOutput);

                        /**
                         * @TotalFiltered
                         */
                        $finalResult = [];
                        foreach ($existing_tables as $table) {
                            $inconsistentTechniciansData = $this->projectTaskDB->select($table, [
                                "technician",
                                "total_jo" => Medoo::raw("COUNT(*)"),
                                "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                            ], [
                                    "AND" => $filteredConditions,
                                    "GROUP" => "technician",
                                    "ORDER" => $orderBy,
                                ] + $filteredConditions);
                            foreach ($inconsistentTechniciansData as $data) {
                                $technician = isset($data['technician']) ? $data['technician'] : '';
                                $totalJO = isset($data['total_jo']) ? $data['total_jo'] : 0;
                                $totalNew = isset($data['total_new']) ? $data['total_new'] : 0;

                                if (!isset($finalResult[$technician])) {
                                    $finalResult[$technician] = [
                                        'technician' => $technician,
                                        'data' => []
                                    ];
                                }
                                $finalResult[$technician]['data'][] = $totalJO;
                                $finalResult[$technician]['data'][] = $totalNew;

                            }
                        }
                        $finalOutput = [];
                        foreach ($finalResult as $tech) {
                            $finalOutput[] = array_merge([$tech['technician']], $tech['data']);
                        }
                        $response['recordsFiltered'] = count($finalOutput);

                        /**
                         * @recordsData
                         */
                        $finalResult = [];
                        foreach ($existing_tables as $table) {
                            $inconsistentTechniciansData = $this->projectTaskDB->select($table, [
                                "technician",
                                "total_jo" => Medoo::raw("COUNT(*)"),
                                "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                            ], [
                                "AND" => $filteredConditions,
                                "GROUP" => "technician",
                                "ORDER" => $orderBy,
                                "LIMIT" => [$start, $length]
                            ]);
                            foreach ($inconsistentTechniciansData as $data) {
                                $technician = isset($data['technician']) ? $data['technician'] : '';
                                $totalJO = isset($data['total_jo']) ? $data['total_jo'] : 0;
                                $totalNew = isset($data['total_new']) ? $data['total_new'] : 0;

                                if (!isset($finalResult[$technician])) {
                                    $finalResult[$technician] = [
                                        'technician' => $technician,
                                        'data' => []
                                    ];
                                }
                                $finalResult[$technician]['data'][] = $totalJO;
                                $finalResult[$technician]['data'][] = $totalNew;

                            }
                        }

                        $finalOutput = [];
                        foreach ($finalResult as $tech) {
                            $finalOutput[] = array_merge([$tech['technician']], $tech['data']);
                        }

                        $response['data'] = $finalOutput;
                        return json_encode($response);
                    }
                } catch (Exception $e) {
                    $this->log->createLogMessage($e->getMessage());
                    return $e->getMessage();
                }
            }
        } else {
            return null;
        }
    }

    public function getDatatablesNonWorkingTechnicians()
    {
        $technicianList = $this->getListTechniciansInconsistentAndNotWork();
        if (!empty($technicianList)) {
            $notworkTechnicians = isset($technicianList['NotWork']) ? $technicianList['NotWork'] : null;
            if (empty($notworkTechnicians)) {
                return null;
            } else {
                try {
                    $daystoget = $this->config['DATABASE_PROJECT_TASK']['DAYS_ASSIGNED'];
                    $existing_tables = [];

                    $tables_to_check = [];
                    for ($i = 1; $i <= $daystoget; $i++) {
                        $tableName = strtolower(date('F_d', strtotime("-$i days")));
                        $tables_to_check[] = $tableName;
                    }

                    foreach ($tables_to_check as $table) {
                        $tableCheck = $this->projectTaskDB->query(
                            "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
                            [
                                ":table" => $table,
                            ]
                        )->fetchColumn();

                        if ($tableCheck) {
                            $existing_tables[] = $table;
                        } else {
                            $this->log->createLogMessage("Table: $table does not exist in DB");
                        }
                    }

                    if (empty($existing_tables)) {
                        return null;
                    } else {
                        sort($existing_tables);

                        $response = [
                            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
                            'recordsTotal' => 0,
                            'recordsFiltered' => 0,
                            'data' => []
                        ];

                        $start = isset($_POST['start']) ? intval(value: $_POST['start']) : 0;
                        $length = isset($_POST['length']) ? intval($_POST['length']) : 7;
                        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

                        $order = isset($_POST['order']) ? $_POST['order'] : [];
                        $columns = isset($_POST['columns']) ? $_POST['columns'] : [];

                        $orderBy = ['technician' => 'ASC'];

                        $columnMap = [
                            0 => 'technician'
                        ];

                        if (!empty($order)) {
                            $orderBy = [];
                            foreach ($order as $ord) {
                                $columnIndex = intval($ord['column']);
                                $dir = $ord['dir'] === 'desc' ? 'DESC' : 'ASC';

                                if (isset($columnMap[$columnIndex])) {
                                    $orderBy[$columnMap[$columnIndex]] = $dir;
                                }
                            }
                        }

                        $commonConditions = [
                            "technician" => $notworkTechnicians
                        ];
                        $filteredConditions = array_merge($commonConditions, [
                            'OR' => [
                                "technician[~]" => $searchValue
                            ]
                        ]);

                        /**
                         * @TotalRecords
                         */
                        $finalResult = [];
                        foreach ($existing_tables as $table) {
                            $inconsistentTechniciansData = $this->projectTaskDB->select($table, [
                                "technician",
                                "total_jo" => Medoo::raw("COUNT(*)"),
                                "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                            ], [
                                "AND" => $commonConditions,
                                "GROUP" => "technician",
                                "ORDER" => $orderBy
                            ]);
                            foreach ($inconsistentTechniciansData as $data) {
                                $technician = isset($data['technician']) ? $data['technician'] : '';
                                $totalJO = isset($data['total_jo']) ? $data['total_jo'] : 0;
                                $totalNew = isset($data['total_new']) ? $data['total_new'] : 0;

                                if (!isset($finalResult[$technician])) {
                                    $finalResult[$technician] = [
                                        'technician' => $technician,
                                        'data' => []
                                    ];
                                }
                                $finalResult[$technician]['data'][] = $totalJO;
                                $finalResult[$technician]['data'][] = $totalNew;

                            }
                        }
                        $finalOutput = [];
                        foreach ($finalResult as $tech) {
                            $finalOutput[] = array_merge([$tech['technician']], $tech['data']);
                        }
                        $response['recordsTotal'] = count($finalOutput);

                        /**
                         * @TotalFiltered
                         */
                        $finalResult = [];
                        foreach ($existing_tables as $table) {
                            $inconsistentTechniciansData = $this->projectTaskDB->select($table, [
                                "technician",
                                "total_jo" => Medoo::raw("COUNT(*)"),
                                "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                            ], [
                                    "AND" => $filteredConditions,
                                    "GROUP" => "technician",
                                    "ORDER" => $orderBy,
                                ] + $filteredConditions);
                            foreach ($inconsistentTechniciansData as $data) {
                                $technician = isset($data['technician']) ? $data['technician'] : '';
                                $totalJO = isset($data['total_jo']) ? $data['total_jo'] : 0;
                                $totalNew = isset($data['total_new']) ? $data['total_new'] : 0;

                                if (!isset($finalResult[$technician])) {
                                    $finalResult[$technician] = [
                                        'technician' => $technician,
                                        'data' => []
                                    ];
                                }
                                $finalResult[$technician]['data'][] = $totalJO;
                                $finalResult[$technician]['data'][] = $totalNew;

                            }
                        }
                        $finalOutput = [];
                        foreach ($finalResult as $tech) {
                            $finalOutput[] = array_merge([$tech['technician']], $tech['data']);
                        }
                        $response['recordsFiltered'] = count($finalOutput);

                        /**
                         * @recordsData
                         */
                        $finalResult = [];
                        foreach ($existing_tables as $table) {
                            $inconsistentTechniciansData = $this->projectTaskDB->select($table, [
                                "technician",
                                "total_jo" => Medoo::raw("COUNT(*)"),
                                "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                            ], [
                                "AND" => $filteredConditions,
                                "GROUP" => "technician",
                                "ORDER" => $orderBy,
                                "LIMIT" => [$start, $length]
                            ]);
                            foreach ($inconsistentTechniciansData as $data) {
                                $technician = isset($data['technician']) ? $data['technician'] : '';
                                $totalJO = isset($data['total_jo']) ? $data['total_jo'] : 0;
                                $totalNew = isset($data['total_new']) ? $data['total_new'] : 0;

                                if (!isset($finalResult[$technician])) {
                                    $finalResult[$technician] = [
                                        'technician' => $technician,
                                        'data' => []
                                    ];
                                }
                                $finalResult[$technician]['data'][] = $totalJO;
                                $finalResult[$technician]['data'][] = $totalNew;

                            }
                        }

                        $finalOutput = [];
                        foreach ($finalResult as $tech) {
                            $finalOutput[] = array_merge([$tech['technician']], $tech['data']);
                        }

                        $response['data'] = $finalOutput;
                        return json_encode($response);
                    }
                } catch (Exception $e) {
                    $this->log->createLogMessage($e->getMessage());
                    return $e->getMessage();
                }
            }
        } else {
            return null;
        }
    }

    public function generateHTMLTableInconsistentTechnicians()
    {
        try {
            $daystoget = $this->config['DATABASE_PROJECT_TASK']['DAYS_ASSIGNED'];
            $existing_tables = [];

            $tables_to_check = [];
            for ($i = 1; $i <= $daystoget; $i++) {
                $tableName = strtolower(date('F_d', strtotime("-$i days")));
                $tables_to_check[] = $tableName;
            }

            foreach ($tables_to_check as $table) {
                $tableCheck = $this->projectTaskDB->query(
                    "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
                    [
                        ":table" => $table,
                    ]
                )->fetchColumn();

                if ($tableCheck) {
                    $existing_tables[] = $table;
                } else {
                    $this->log->createLogMessage("Table: $table does not exist in DB");
                }
            }

            if (!empty($existing_tables)) {
                sort($existing_tables);

                $dateKeys = [];
                $theadContent = <<<EOD
                    <tr>
                        <th rowspan="2">Technicians</th>
                EOD;

                foreach ($existing_tables as $table) {
                    $timestamp = strtotime(str_replace('_', ' ', $table));
                    $date = date('d M Y', $timestamp);
                    $theadContent .= "<th class='text-center' colspan='2'>$date</th>";
                    $dateKeys[] = $table;
                }
                $theadContent .= <<<EOD
                    </tr>
                    <tr>
                EOD;
                foreach ($existing_tables as $table) {
                    $theadContent .= "
                        <th class='text-center'>Total JO</th>
                        <th class='text-center'>New JO Left</th>
                    ";
                }
                $theadContent .= "</tr>";

                $html = <<<EOD
                    <div class="card-datatable table-responsive">
                        <table class="datatables-inconsistent-technicians table table-bordered">
                            <thead>
                                {$theadContent}
                            </thead>
                        </table>
                    </div>
                EOD;

                $days = [];
                $month = '';
                $year = date('Y');

                foreach ($existing_tables as $date) {
                    $parts = explode('_', $date);
                    $month = ucfirst($parts[0]);
                    $day = (int) $parts[1];

                    $days[] = $day;
                }

                sort($days);
                $dayRange = min($days) . ' - ' . max($days);
                $tooltipRange = "Data on: <br>" . $dayRange . ' ' . $month . ' ' . $year;

                return json_encode([
                    'table' => $html,
                    'dates' => $dateKeys,
                    'tooltip_range' => $tooltipRange
                ]);
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

    public function generateHTMLTableNonWorkingTechnicians()
    {
        try {
            $daystoget = $this->config['DATABASE_PROJECT_TASK']['DAYS_ASSIGNED'];
            $existing_tables = [];

            $tables_to_check = [];
            for ($i = 1; $i <= $daystoget; $i++) {
                $tableName = strtolower(date('F_d', strtotime("-$i days")));
                $tables_to_check[] = $tableName;
            }

            foreach ($tables_to_check as $table) {
                $tableCheck = $this->projectTaskDB->query(
                    "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
                    [
                        ":table" => $table,
                    ]
                )->fetchColumn();

                if ($tableCheck) {
                    $existing_tables[] = $table;
                } else {
                    $this->log->createLogMessage("Table: $table does not exist in DB");
                }
            }

            if (!empty($existing_tables)) {
                sort($existing_tables);

                $dateKeys = [];
                $theadContent = <<<EOD
                        <tr>
                            <th rowspan="2">Technicians</th>
                    EOD;

                foreach ($existing_tables as $table) {
                    $timestamp = strtotime(str_replace('_', ' ', $table));
                    $date = date('d M Y', $timestamp);
                    $theadContent .= "<th class='text-center' colspan='2'>$date</th>";
                    $dateKeys[] = $table;
                }
                $theadContent .= <<<EOD
                        </tr>
                        <tr>
                    EOD;
                foreach ($existing_tables as $table) {
                    $theadContent .= "
                            <th class='text-center'>Total JO</th>
                            <th class='text-center'>New JO Left</th>
                        ";
                }
                $theadContent .= "</tr>";

                $html = <<<EOD
                        <div class="card-datatable table-responsive">
                            <table class="datatables-non-working-technicians table table-bordered">
                                <thead>
                                    {$theadContent}
                                </thead>
                            </table>
                        </div>
                    EOD;

                $days = [];
                $month = '';
                $year = date('Y');

                foreach ($existing_tables as $date) {
                    $parts = explode('_', $date);
                    $month = ucfirst($parts[0]);
                    $day = (int) $parts[1];

                    $days[] = $day;
                }

                sort($days);
                $dayRange = min($days) . ' - ' . max($days);
                $tooltipRange = "Data on: <br>" . $dayRange . ' ' . $month . ' ' . $year;

                return json_encode([
                    'table' => $html,
                    'dates' => $dateKeys,
                    'tooltip_range' => $tooltipRange
                ]);
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

    public function generateExcelInconsistentTechnicians()
    {
        try {
            $daystoget = $this->config['DATABASE_PROJECT_TASK']['DAYS_ASSIGNED'];
            $existing_tables = [];

            $tables_to_check = [];
            for ($i = 1; $i <= $daystoget; $i++) {
                $tableName = strtolower(date('F_d', strtotime("-$i days")));
                $tables_to_check[] = $tableName;
            }

            foreach ($tables_to_check as $table) {
                $tableCheck = $this->projectTaskDB->query(
                    "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
                    [
                        ":table" => $table,
                    ]
                )->fetchColumn();

                if ($tableCheck) {
                    $existing_tables[] = $table;
                } else {
                    $this->log->createLogMessage("Table: $table does not exist in DB");
                }
            }
            if (empty($existing_tables)) {
                $this->log->createLogMessage("Empty existing table for report inconsistent technician");
                return null;
            } else {
                $technicianList = $this->getListTechniciansInconsistentAndNotWork();
                if (!empty($technicianList)) {
                    $inconsistentTechnicians = isset($technicianList['Inconsistent']) ? $technicianList['Inconsistent'] : null;
                    if (empty($inconsistentTechnicians)) {
                        $this->log->createLogMessage("Empty or not set inconsistent key in technicians list data");
                        return null;
                    } else {
                        $excelFile = $this->initExcelFile();
                        if (file_exists($excelFile)) {
                            if (is_readable($excelFile)) {
                                sort($existing_tables);
                                $spreadSheet = new spreadSheet();
                                $sheetMasterData = $spreadSheet->getActiveSheet();
                                $sheetMasterData->setTitle('Inconsistent Technicians');

                                $row = 1;
                                $rowSub = 2;
                                $col = 'A';

                                $sheetMasterData->setCellValue($col . $row, 'Technicians');
                                $sheetMasterData->mergeCells('A1:A2');

                                foreach ($existing_tables as $table) {
                                    $col++;
                                    $startMergeCol = $col;

                                    $sheetMasterData->setCellValue($col . $rowSub, 'Total JO');

                                    $tableData = isset($table) ? $table : '';
                                    $tableData = isset($table) ? $table : '';
                                    $dateObject = DateTime::createFromFormat('F_d', strtolower($tableData));
                                    $date = $dateObject ? $dateObject->format('d M Y') : '';

                                    $sheetMasterData->setCellValue($col . $row, $date);

                                    $col++;
                                    $endMergeCol = $col;
                                    $sheetMasterData->mergeCells($startMergeCol . $row . ':' . $endMergeCol . $row);
                                    $sheetMasterData->setCellValue($col . $rowSub, 'New JO Left');
                                }

                                foreach (range('A', $col) as $columnID) {
                                    $sheetMasterData->getColumnDimension($columnID)->setAutoSize(true);
                                }

                                // Rows Data
                                $finalResult = [];
                                foreach ($existing_tables as $table) {
                                    $inconsistentTechniciansData = $this->projectTaskDB->select($table, [
                                        "technician",
                                        "total_jo" => Medoo::raw("COUNT(*)"),
                                        "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                                    ], [
                                        "AND" => [
                                            "technician" => $inconsistentTechnicians
                                        ],
                                        "GROUP" => "technician",
                                        "ORDER" => [
                                            "technician" => "ASC"
                                        ],
                                    ]);
                                    foreach ($inconsistentTechniciansData as $data) {
                                        $technician = isset($data['technician']) ? $data['technician'] : '';
                                        $totalJO = isset($data['total_jo']) ? $data['total_jo'] : 0;
                                        $totalNew = isset($data['total_new']) ? $data['total_new'] : 0;

                                        if (!isset($finalResult[$technician])) {
                                            $finalResult[$technician] = [
                                                'technician' => $technician,
                                                'data' => []
                                            ];
                                        }
                                        $finalResult[$technician]['data'][] = $totalJO;
                                        $finalResult[$technician]['data'][] = $totalNew;

                                    }
                                }

                                $row = 3;
                                foreach ($finalResult as $data) {
                                    $col = 'A';
                                    $sheetMasterData->setCellValue($col . $row, $data['technician']);
                                    $col++;
                                    foreach ($data['data'] as $value) {
                                        $sheetMasterData->setCellValue($col . $row, $value);
                                        $col++;
                                    }
                                    $row++;
                                }


                                $writer = IOFactory::createWriter($spreadSheet, 'Xlsx');
                                $writer->save($excelFile);

                                $normalizedPath = str_replace('\\', '/', $excelFile);

                                if (preg_match('/\/\d{4}-\d{2}-\d{2}\/[^\/]+\.xlsx$/', $normalizedPath, $matches)) {
                                    $finalExcelURLToDownload = 'public/files' . $matches[0];
                                    $result = [
                                        'status' => 'success',
                                        'link' => $finalExcelURLToDownload
                                    ];
                                    return json_encode($result);
                                } else {
                                    return null;
                                }
                            } else {
                                $this->log->createLogMessage("File: $excelFile is not readable");
                                return null;
                            }
                        } else {
                            $this->log->createLogMessage("File: $excelFile is not exists!");
                            return null;
                        }
                    }
                } else {
                    $this->log->createLogMessage("Empty technicians list data");
                    return null;
                }
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return null;
        }
    }

    public function generateExcelNonWorkingTechnicians()
    {
        try {
            $daystoget = $this->config['DATABASE_PROJECT_TASK']['DAYS_ASSIGNED'];
            $existing_tables = [];

            $tables_to_check = [];
            for ($i = 1; $i <= $daystoget; $i++) {
                $tableName = strtolower(date('F_d', strtotime("-$i days")));
                $tables_to_check[] = $tableName;
            }

            foreach ($tables_to_check as $table) {
                $tableCheck = $this->projectTaskDB->query(
                    "SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_name = :table AND table_schema = DATABASE()",
                    [
                        ":table" => $table,
                    ]
                )->fetchColumn();

                if ($tableCheck) {
                    $existing_tables[] = $table;
                } else {
                    $this->log->createLogMessage("Table: $table does not exist in DB");
                }
            }
            if (empty($existing_tables)) {
                $this->log->createLogMessage("Empty existing table for report not work technician");
                return null;
            } else {
                $technicianList = $this->getListTechniciansInconsistentAndNotWork();
                if (!empty($technicianList)) {
                    $notWorkTechnicians = isset($technicianList['NotWork']) ? $technicianList['NotWork'] : null;
                    if (empty($notWorkTechnicians)) {
                        $this->log->createLogMessage("Empty or not set not work key in technicians list data");
                        return null;
                    } else {
                        $excelFile = $this->initExcelFile();
                        if (file_exists($excelFile)) {
                            if (is_readable($excelFile)) {
                                sort($existing_tables);
                                $spreadSheet = new spreadSheet();
                                $sheetMasterData = $spreadSheet->getActiveSheet();
                                $sheetMasterData->setTitle('Not Work Technicians');

                                $row = 1;
                                $rowSub = 2;
                                $col = 'A';

                                $sheetMasterData->setCellValue($col . $row, 'Technicians');
                                $sheetMasterData->mergeCells('A1:A2');

                                foreach ($existing_tables as $table) {
                                    $col++;
                                    $startMergeCol = $col;

                                    $sheetMasterData->setCellValue($col . $rowSub, 'Total JO');

                                    $tableData = isset($table) ? $table : '';
                                    $tableData = isset($table) ? $table : '';
                                    $dateObject = DateTime::createFromFormat('F_d', strtolower($tableData));
                                    $date = $dateObject ? $dateObject->format('d M Y') : '';

                                    $sheetMasterData->setCellValue($col . $row, $date);

                                    $col++;
                                    $endMergeCol = $col;
                                    $sheetMasterData->mergeCells($startMergeCol . $row . ':' . $endMergeCol . $row);
                                    $sheetMasterData->setCellValue($col . $rowSub, 'New JO Left');
                                }

                                foreach (range('A', $col) as $columnID) {
                                    $sheetMasterData->getColumnDimension($columnID)->setAutoSize(true);
                                }

                                // Rows Data
                                $finalResult = [];
                                foreach ($existing_tables as $table) {
                                    $notworkTechniciansData = $this->projectTaskDB->select($table, [
                                        "technician",
                                        "total_jo" => Medoo::raw("COUNT(*)"),
                                        "total_new" => Medoo::raw("COUNT(CASE WHEN stage = 'New' THEN 1 END)")
                                    ], [
                                        "AND" => [
                                            "technician" => $notWorkTechnicians
                                        ],
                                        "GROUP" => "technician",
                                        "ORDER" => [
                                            "technician" => "ASC"
                                        ],
                                    ]);
                                    foreach ($notworkTechniciansData as $data) {
                                        $technician = isset($data['technician']) ? $data['technician'] : '';
                                        $totalJO = isset($data['total_jo']) ? $data['total_jo'] : 0;
                                        $totalNew = isset($data['total_new']) ? $data['total_new'] : 0;

                                        if (!isset($finalResult[$technician])) {
                                            $finalResult[$technician] = [
                                                'technician' => $technician,
                                                'data' => []
                                            ];
                                        }
                                        $finalResult[$technician]['data'][] = $totalJO;
                                        $finalResult[$technician]['data'][] = $totalNew;

                                    }
                                }

                                $row = 3;
                                foreach ($finalResult as $data) {
                                    $col = 'A';
                                    $sheetMasterData->setCellValue($col . $row, $data['technician']);
                                    $col++;
                                    foreach ($data['data'] as $value) {
                                        $sheetMasterData->setCellValue($col . $row, $value);
                                        $col++;
                                    }
                                    $row++;
                                }


                                $writer = IOFactory::createWriter($spreadSheet, 'Xlsx');
                                $writer->save($excelFile);

                                $normalizedPath = str_replace('\\', '/', $excelFile);

                                if (preg_match('/\/\d{4}-\d{2}-\d{2}\/[^\/]+\.xlsx$/', $normalizedPath, $matches)) {
                                    $finalExcelURLToDownload = 'public/files' . $matches[0];
                                    $result = [
                                        'status' => 'success',
                                        'link' => $finalExcelURLToDownload
                                    ];
                                    return json_encode($result);
                                } else {
                                    return null;
                                }
                            } else {
                                $this->log->createLogMessage("File: $excelFile is not readable");
                                return null;
                            }
                        } else {
                            $this->log->createLogMessage("File: $excelFile is not exists!");
                            return null;
                        }
                    }
                } else {
                    $this->log->createLogMessage("Empty technicians list data");
                    return null;
                }
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return null;
        }
    }

    // public function getInactiveTechnicians()
    // {
    //     fix this !! 
    //     try {
    //         $activeTechnicians = [];
    //         $activeTechniciansData = $this->mainDB->select($this->tableTechnicians, ["technician"], [
    //             "GROUP" => "technician"
    //         ]);
    //         foreach ($activeTechniciansData as $data) {
    //             $activeTechnicians[] = $data['technician'];
    //         }

    //         $dataNEWJOLeftNotPlanned = $this->projectTaskDB->select($this->tableNewAssignedJO, [
    //             "technician",
    //             "planned_date"
    //         ], [
    //             "AND" => [
    //                 // "planned_date" => null,
    //                 "technician[~]" => $activeTechnicians
    //             ],
    //             "GROUP" => "technician"
    //         ]);
    //         print_r($dataNEWJOLeftNotPlanned);
    //         die;

    //         return null;
    //     } catch (Exception $e) {
    //         $this->log->createLogMessage($e->getMessage());
    //         return null;
    //     }
    // }
}

if (isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $assignedJOInstance = new assignedJOController();
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            switch ($action) {
                case 'get-new-assigned-jo':
                    $response = $assignedJOInstance->getNewAssignedJO();
                    echo $response;
                    break;
                case 'get-last-update':
                    $lastUpdate = $assignedJOInstance->getLastUpdateNewAssignedJOData();
                    echo $lastUpdate;
                    break;
                case 'generate-excel-new-jo-left':
                    $excelFileURL = $assignedJOInstance->generateExcelNewJOLeft();
                    echo $excelFileURL;
                    break;
                case 'generate-excel-inconsistent-technicians':
                    $excelFileURL = $assignedJOInstance->generateExcelInconsistentTechnicians();
                    echo $excelFileURL;
                    break;
                case 'generate-excel-non-working-technicians':
                    $excelFileURL = $assignedJOInstance->generateExcelNonWorkingTechnicians();
                    echo $excelFileURL;
                    break;
                case 'get-datatables':
                    $assignedJOInstance->getDatatables();
                    break;
                case 'generate-table-inconsistent-technicians':
                    $html = $assignedJOInstance->generateHTMLTableInconsistentTechnicians();
                    echo $html;
                    break;
                case 'generate-table-non-working-technicians':
                    $html = $assignedJOInstance->generateHTMLTableNonWorkingTechnicians();
                    echo $html;
                    break;
                case 'get-datatables-inconsistent-technicians':
                    $data = $assignedJOInstance->getDatatablesInconsistentTechnicians();
                    echo $data;
                    break;
                case 'get-datatables-non-working-technicians':
                    $data = $assignedJOInstance->getDatatablesNonWorkingTechnicians();
                    echo $data;
                    break;
                // case 'get-inactive-technicians':
                //     $data = $assignedJOInstance->getInactiveTechnicians();
                //     echo $data;
                //     break;
                default:
                    $error = new errorController();
                    $errorCode = 400;
                    $errorPage = $error->getPathError($errorCode, null);
                    http_response_code($errorCode);
                    header("Location: $errorPage");
                    exit();
                // break;
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
        $assignedJOInstance = new assignedJOController();

        $error = new errorController();
        $errorCode = 400;
        $errorPage = $error->getPathError($errorCode, null);
        http_response_code($errorCode);
        header("Location: $errorPage");
        exit();
    }
}