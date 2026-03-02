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

class todayJOPlanController
{
    private $log;
    private $config;
    private $mainDB;
    private $projectTaskDB;
    private $error;
    private $api;
    private $todayPlanTable;
    private $tableTechnicians;
    private $tableNewAssignedJO;

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
        $this->tableNewAssignedJO = $this->config['DATABASE_PROJECT_TASK']['TB_NEW_ASSIGNED_JO'];

        $this->todayPlanTable = strtolower(date('F_d'));
        $this->tableTechnicians = $this->config['DATABASE']['TB_TECHNICIANS'];
        try {
            $this->projectTaskDB->create($this->todayPlanTable, [
                "id" => ["INT", "NOT NULL", "AUTO_INCREMENT"],
                "technician" => ["VARCHAR(150)", "NOT NULL"],
                "planned_date" => ["DATETIME"],
                "long" => ["TEXT"],
                "lat" => ["TEXT"],
                "wo_number" => ["VARCHAR(100)", "NOT NULL"],
                "stage" => ["VARCHAR(50)", "NOT NULL"],
                "company" => ["VARCHAR(50)", "NOT NULL"],
                "task_type" => ["VARCHAR(100)", "NOT NULL"],
                "last_stop" => ["DATETIME"],
                "sla_deadline" => ["DATETIME"],
                "sac_group" => ["VARCHAR(50)", "NOT NULL"],
                "last_update_by" => ["VARCHAR(100)"],
                "last_update_on" => ["DATETIME"],
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

    public function getPlannedJOToday()
    {
        $companyAllowedString = $this->config['ODOO_DATA']['COMPANY_ALLOWED'];
        $companyAllowedArray = array_map('intval', explode(',', $companyAllowedString));

        $IDtechnicians = [];
        $dataTech = $this->mainDB->select($this->tableTechnicians, ["id"]);
        foreach ($dataTech as $data) {
            $IDtechnicians[] = isset($data['id']) ? $data['id'] : 'Unknown';
        }

        $planStart = new DateTime(date('Y-m-d 00:00:00'));
        $planEnd = new DateTime(date('Y-m-d 23:59:59'));

        $planStart->modify('-7 hours');
        $planEnd->modify('-7 hours');
        $planStartFormatted = $planStart->format('Y-m-d H:i:s');
        $planEndFormatted = $planEnd->format('Y-m-d H:i:s');

        $params = [
            "jsonrpc" => $this->config["ODOO_API"]["JSONRPC"],
            "params" => [
                "domain" => [
                    ["active", "=", true],
                    ["planned_date_begin", ">=", $planStartFormatted],
                    ["planned_date_begin", "<=", $planEndFormatted],
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
                    "stage_id",
                    "company_id",
                    "x_task_type",
                    "x_sla_deadline",
                    "timesheet_timer_last_stop",
                    "write_uid",
                    "write_date",
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
                $this->projectTaskDB->delete($this->todayPlanTable, []);
                $this->projectTaskDB->pdo->commit();

                $this->projectTaskDB->pdo->beginTransaction();

                $sql = "INSERT INTO `$this->todayPlanTable` (
                    id, technician, planned_date, `long`, `lat`, wo_number, stage, company, task_type, last_stop, sla_deadline, sac_group, last_update_by, last_update_on, received_spk, ticket_id, ticket_name, merchant_mid, merchant_tid, merchant_name
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
                    $stage = isset($data['stage_id'][1]) ? $data['stage_id'][1] : '';
                    $company = isset($data['company_id'][1]) ? $data['company_id'][1] : '';
                    $taskType = isset($data['x_task_type']) ? $data['x_task_type'] : '';
                    $slaDeadline = isset($data['x_sla_deadline']) ? $data['x_sla_deadline'] : '';
                    if (empty($slaDeadline)) {
                        $slaDeadline = null;
                    } else {
                        $slaDeadline = $this->plus7Hours($slaDeadline);
                    }
                    $lastStop = isset($data['timesheet_timer_last_stop']) ? $data['timesheet_timer_last_stop'] : '';
                    if (empty($lastStop)) {
                        $lastStop = null;
                    } else {
                        $lastStop = $this->plus7Hours($lastStop);
                    }
                    $lastUpdateBy = isset($data['write_uid'][1]) ? $data['write_uid'][1] : null;
                    if (empty($lastUpdateBy)) {
                        $lastUpdateBy = null;
                    }
                    $lastUpdateOn = isset($data['write_date']) ? $data['write_date'] : null;
                    if (empty($lastUpdateOn)) {
                        $lastUpdateOn = null;
                    } else {
                        $lastUpdateOn = $this->plus7Hours($lastUpdateOn);
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

                    $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                    $bindValues[] = $id;
                    $bindValues[] = $technician;
                    $bindValues[] = $plannedDate;
                    $bindValues[] = $longitude;
                    $bindValues[] = $latitude;
                    $bindValues[] = $woNumber;
                    $bindValues[] = $stage;
                    $bindValues[] = $company;
                    $bindValues[] = $taskType;
                    $bindValues[] = $lastStop;
                    $bindValues[] = $slaDeadline;
                    $bindValues[] = $sacGroup;
                    $bindValues[] = $lastUpdateBy;
                    $bindValues[] = $lastUpdateOn;
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
                    'message' => 'Success update Technicians Today JO Planned @' . date('Y-m-d H:i:s')
                ];
                return json_encode($response);
            } catch (Exception $e) {
                $this->log->createLogMessage($e->getMessage());
                $this->projectTaskDB->pdo->rollBack();
                return null;
            }
        } else {
            $msg = "Empty odoo response for today planned JO!";
            $this->log->createLogMessage($msg);
            return $msg;
        }
    }

    public function getLastUpdatePlannedJOToday()
    {
        $response = [];
        try {
            $lastUpdate = $this->projectTaskDB->pdo->query("SELECT MIN(data_on) as last_update FROM $this->todayPlanTable")->fetchColumn();
            $result = null;
            if (!empty($lastUpdate)) {
                $result = DateTime::createFromFormat('Y-m-d H:i:s', $lastUpdate)->format('d F Y H:i:s');
            }
            $response['status'] = 'success';
            $response['message'] = $result;

            $tooltipLateUploadJO = "JO Uploaded after " . $this->config['ODOO_DATA']['LATE_UPLOAD_OVER'];
            $response['tooltip_late_upload_jo'] = $tooltipLateUploadJO;

            $tooltipOverPlanJO = "JO Planned For Technician > " . $this->config['ODOO_DATA']['MAX_PLANNED'] . ' JO';
            $response['tooltip_over_plan_jo'] = $tooltipOverPlanJO;
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            $response['status'] = 'error';
            $response['message'] = $e->getMessage();
        }
        return json_encode($response);
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

        $orderBy = ['id' => 'ASC'];

        $columnMap = [
            0 => 'id',
            1 => 'technician',
            2 => 'sac_group',
            3 => 'planned_date',
            4 => 'wo_number',
            5 => 'stage',
            6 => 'company',
            7 => 'task_type',
            8 => 'last_stop',
            9 => 'sla_deadline',
            10 => 'data_on'
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

        $totalRecords = $this->projectTaskDB->count($this->todayPlanTable);
        $response['recordsTotal'] = $totalRecords;

        $searchConditions = [];
        if (!empty($searchValue)) {
            $searchConditions = [
                'OR' => [
                    'technician[~]' => $searchValue,
                    'wo_number[~]' => $searchValue,
                    'stage[~]' => $searchValue,
                    'company[~]' => $searchValue,
                    'task_type[~]' => $searchValue,
                    'sac_group[~]' => $searchValue
                ]
            ];
        }

        $totalFilteredRecords = $this->projectTaskDB->count($this->todayPlanTable, $searchConditions);
        $response['recordsFiltered'] = $totalFilteredRecords;

        // $data = $this->projectTaskDB->select($this->todayPlanTable, '*', [
        $data = $this->projectTaskDB->select($this->todayPlanTable, [
            "id",
            "technician",
            "planned_date",
            "sac_group",
            "wo_number",
            "stage",
            "company",
            "task_type",
            "last_stop",
            "sla_deadline",
            "data_on"
        ], [
                'LIMIT' => [$start, $length],
                'ORDER' => $orderBy,
            ] + $searchConditions);

        foreach ($data as $row) {
            $response['data'][] = [
                'id' => $row['id'],
                'technician' => $row['technician'],
                'planned_date' => $row['planned_date'],
                'sac_group' => $row['sac_group'],
                'wo_number' => $row['wo_number'],
                'stage' => $row['stage'],
                'company' => $row['company'],
                'task_type' => $row['task_type'],
                'last_stop' => $row['last_stop'],
                'sla_deadline' => $row['sla_deadline'],
                'data_on' => $row['data_on']
            ];
        }

        echo json_encode($response);
    }

    public function getDatatablesOverPlannedJO(): void
    {
        $overPlannedParam = $this->config['ODOO_DATA']['MAX_PLANNED'];

        $response = [
            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ];

        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 7;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

        $order = isset($_POST['order']) ? $_POST['order'] : [];
        $columns = isset($_POST['columns']) ? $_POST['columns'] : [];

        // Define a whitelist of allowed columns for ordering
        $allowedColumns = ['technician', 'new_count', 'done_count', 'open_pending_count', 'cancel_count', 'verified_count', 'grand_total'];
        $orderColumnIndex = isset($order[0]['column']) && isset($columns[$order[0]['column']]) ? $order[0]['column'] : 1;
        $orderColumn = isset($columns[$orderColumnIndex]['data']) && in_array($columns[$orderColumnIndex]['data'], $allowedColumns)
            ? $columns[$orderColumnIndex]['data']
            : 'technician';

        // Ensure the order direction is either ASC or DESC
        $orderDir = isset($order[0]['dir']) && in_array(strtoupper($order[0]['dir']), ['ASC', 'DESC']) ? strtoupper($order[0]['dir']) : 'ASC';

        $searchConditions = "";
        if (!empty($searchValue)) {
            $searchConditions = " WHERE technician LIKE :search";
        }

        $sqlCount = "SELECT COUNT(*) AS total_count FROM (
                    SELECT technician, COUNT(*) AS grand_total
                    FROM {$this->todayPlanTable}
                    $searchConditions
                    GROUP BY technician
                    HAVING grand_total > {$overPlannedParam}
                ) AS temp";

        $stmtCount = $this->projectTaskDB->pdo->prepare($sqlCount);

        if (!empty($searchValue)) {
            $stmtCount->bindValue(':search', "%{$searchValue}%", PDO::PARAM_STR);
        }
        $stmtCount->execute();
        $totalFilteredRecords = $stmtCount->fetchColumn();
        $response['recordsTotal'] = $totalFilteredRecords;
        $response['recordsFiltered'] = $totalFilteredRecords;

        $sql = "SELECT technician, 
                SUM(CASE WHEN LOWER(stage) = 'new' THEN 1 ELSE 0 END) AS new_count,
                SUM(CASE WHEN LOWER(stage) = 'done' THEN 1 ELSE 0 END) AS done_count,
                SUM(CASE WHEN LOWER(stage) = 'open pending' THEN 1 ELSE 0 END) AS open_pending_count,
                SUM(CASE WHEN LOWER(stage) = 'cancel' THEN 1 ELSE 0 END) AS cancel_count,
                SUM(CASE WHEN LOWER(stage) = 'verified' THEN 1 ELSE 0 END) AS verified_count,
                COUNT(*) AS grand_total
            FROM {$this->todayPlanTable}
            $searchConditions
            GROUP BY technician
            HAVING grand_total > {$overPlannedParam}
            ORDER BY {$orderColumn} {$orderDir}
            LIMIT ?, ?";

        $stmt = $this->projectTaskDB->pdo->prepare($sql);
        $stmt->bindValue(1, $start, PDO::PARAM_INT);
        $stmt->bindValue(2, $length, PDO::PARAM_INT);
        if (!empty($searchValue)) {
            $stmt->bindValue(':search', "%{$searchValue}%", PDO::PARAM_STR);
        }
        $stmt->execute();
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($technicians as $row) {
            $response['data'][] = [
                'technician' => $row['technician'],
                'new_count' => $row['new_count'],
                'done_count' => $row['done_count'],
                'open_pending_count' => $row['open_pending_count'],
                'cancel_count' => $row['cancel_count'],
                'verified_count' => $row['verified_count'],
                'grand_total' => $row['grand_total']
            ];
        }

        echo json_encode($response);
    }

    public function getDatatablesLateUploadJO()
    {
        $lateUploadParam = date('Y-m-d ') . $this->config['ODOO_DATA']['LATE_UPLOAD_OVER'];
        $response = [
            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ];

        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 7;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

        $order = isset($_POST['order']) ? $_POST['order'] : [];
        $columns = isset($_POST['columns']) ? $_POST['columns'] : [];

        $orderBy = ['id' => 'ASC'];

        $columnMap = [
            0 => "id",
            1 => "technician",
            2 => "wo_number",
            3 => "task_type",
            4 => "last_stop",
            5 => "last_update_on",
            6 => "data_on"
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

        $conditions = [
            "AND" => [
                "last_stop[!]" => "",
                "last_stop[>=]" => $lateUploadParam,
                "OR" => [
                    "last_update_on[>=]" => $lateUploadParam,
                    "last_update_by[!~]" => ["%Admin%", "%Verifikasi%", "%Technical%"]
                ]
            ]
        ];

        if (!empty($searchValue)) {
            $searchConditions = [
                'OR' => [
                    'technician[~]' => $searchValue,
                    'wo_number[~]' => $searchValue,
                    'task_type[~]' => $searchValue,
                    'last_stop[~]' => $searchValue,
                    'last_update_on[~]' => $searchValue,
                    'data_on[~]' => $searchValue
                ]
            ];

            $conditions = array_merge($conditions, $searchConditions);
        }

        try {
            $this->projectTaskDB->pdo->beginTransaction();

            $totalFilteredRecords = $this->projectTaskDB->count($this->todayPlanTable, $conditions);
            $response['recordsTotal'] = $totalFilteredRecords;

            $data = $this->projectTaskDB->select($this->todayPlanTable, [
                "id",
                "technician",
                "wo_number",
                "task_type",
                "last_stop",
                "last_update_on",
                "data_on"
            ], array_merge($conditions, [
                    'LIMIT' => [$start, $length],
                    'ORDER' => $orderBy
                ]));

            $this->projectTaskDB->pdo->commit();

            $response['recordsFiltered'] = $totalFilteredRecords;

            foreach ($data as $row) {
                $response['data'][] = [
                    'id' => $row['id'],
                    'technician' => $row['technician'],
                    'wo_number' => $row['wo_number'],
                    'task_type' => $row['task_type'],
                    'last_stop' => $row['last_stop'],
                    'last_update' => $row['last_update_on'],
                    'data_on' => $row['data_on'],
                ];
            }

        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            $this->projectTaskDB->pdo->rollBack();
            $response['error'] = 'Database error: ' . $e->getMessage();
        }

        echo json_encode($response);
    }

    public function getDatatablesUnplannedTechnicians()
    {
        try {
            $techniciansPlannedToday = $this->projectTaskDB->select($this->todayPlanTable, ["technician"], [
                'GROUP' => 'technician'
            ]);

            if ($techniciansPlannedToday) {
                $listTechPlanToday = array_column($techniciansPlannedToday, 'technician');

                $unplannedTechToday = $this->projectTaskDB->select($this->tableNewAssignedJO, [
                    "technician"
                ], [
                    "AND" => [
                        "technician[!]" => $listTechPlanToday,
                        "planned_date" => null
                    ],
                    "GROUP" => "technician"
                ]);

                if ($unplannedTechToday) {
                    // $listUnplannedTechToday = array_column($unplannedTechToday, 'technician');

                    $response = [
                        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
                    ];

                    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
                    $length = isset($_POST['length']) ? intval($_POST['length']) : 7;
                    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

                    $order = isset($_POST['order']) ? $_POST['order'] : [];
                    $columns = isset($_POST['columns']) ? $_POST['columns'] : [];

                    // $orderBy = ["technician" => 'ASC'];

                    $columnMap = [
                        1 => "technician",
                        2 => "count"
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
                        "planned_date" => null,
                        "technician[!]" => $listTechPlanToday,
                    ];

                    $totalCountData = $this->projectTaskDB->select($this->tableNewAssignedJO, [
                        "count" => Medoo::raw("COUNT(*)"),
                        "technician"
                    ], [
                        "AND" => $commonConditions,
                        "GROUP" => "technician"
                    ]);

                    $response['recordsTotal'] = count($totalCountData);

                    $filteredConditions = array_merge($commonConditions, [
                        "technician[~]" => $searchValue
                    ]);

                    $filteredCountData = $this->projectTaskDB->select($this->tableNewAssignedJO, [
                        "count" => Medoo::raw("COUNT(*)"),
                        "technician"
                    ], [
                        "AND" => $filteredConditions,
                        "GROUP" => "technician"
                    ]);

                    $response['recordsFiltered'] = count($filteredCountData);

                    $unplannedDataToday = $this->projectTaskDB->select($this->tableNewAssignedJO, [
                        "technician",
                        "count" => Medoo::raw("COUNT(*)")
                    ], [
                        "AND" => $filteredConditions,
                        "GROUP" => "technician",
                        // "ORDER" => $orderBy,
                        "ORDER" => $orderBy ? $orderBy : ["technician" => "ASC"],
                        "LIMIT" => [$start, $length]
                    ]);

                    foreach ($unplannedDataToday as $row) {
                        $response['data'][] = [
                            'technician' => $row['technician'],
                            'count' => $row['count']
                        ];
                    }

                    return json_encode($response);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
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

    public function getNewLeftJOTechnicians($technicians, $spreadSheet)
    {
        if (!empty($technicians)) {
            $dateTimeGetNewLeftJO = date('dMY h.i A');
            $newLeftJO = [];
            foreach ($technicians as $tech) {
                if (!isset($newLeftJO[$tech])) {
                    $newLeftJO[$tech] = [
                        'new_planned_today' => 0,
                        'new_planned_not_today' => 0,
                        'new_assigned' => 0
                    ];
                }

                $companyAllowedString = $this->config['ODOO_DATA']['COMPANY_ALLOWED'];
                $companyAllowedArray = array_map('intval', explode(',', $companyAllowedString));

                $params = [
                    "jsonrpc" => $this->config["ODOO_API"]["JSONRPC"],
                    "params" => [
                        "domain" => [
                            ["active", "=", true],
                            ["stage_id", "=", "New"],
                            ["technician_id", "=", $tech],
                            ["company_id", "=", $companyAllowedArray]
                        ],
                        "model" => "project.task",
                        "fields" => [
                            "id",
                            "planned_date_begin"
                        ],
                        "order" => "id asc"
                    ]
                ];

                $ODOOResponse = $this->api->OdooAPI('GetData', $params);
                if (!empty($ODOOResponse)) {
                    foreach ($ODOOResponse as $index => $data) {
                        $plannedDateData = isset($data['planned_date_begin']) ? $data['planned_date_begin'] : null;
                        if (!empty($plannedDateData)) {
                            $newplanDate = new DateTime($plannedDateData);
                            $newplanDate->modify('+7 hours');
                            $plannedDate = $newplanDate->format('Y-m-d');
                            $plannedTime = $newplanDate->format('H:i:s');

                            if ($plannedDate === date('Y-m-d')) {
                                $newLeftJO[$tech]['new_planned_today']++;
                            } else {
                                $newLeftJO[$tech]['new_planned_not_today']++;
                            }
                        } else {
                            $newLeftJO[$tech]['new_assigned']++;
                        }
                    }
                }
            }

            if (!empty($newLeftJO)) {
                $sheetPivot = $spreadSheet->createSheet();
                $sheetPivot->setTitle('New @' . $dateTimeGetNewLeftJO);

                // Headers
                $row = 1;
                $headers = [
                    ['A', 'Technician'],
                    ['B', 'New JO Plan Today'],
                    ['C', 'New JO Plan Not Today'],
                    ['D', 'New JO Not Plan'],
                ];
                foreach ($headers as $head) {
                    $sheetPivot->setCellValue($head[0] . $row, $head[1]);
                }
                foreach (range('A', 'D') as $col) {
                    $sheetPivot->getColumnDimension($col)->setAutoSize(true);
                }

                // Rows Data
                $row = 2;
                foreach ($technicians as $tech) {
                    $sheetPivot->setCellValue('A' . $row, $tech);
                    $sheetPivot->setCellValue('B' . $row, $newLeftJO[$tech]['new_planned_today']);
                    $sheetPivot->setCellValue('C' . $row, $newLeftJO[$tech]['new_planned_not_today']);
                    $sheetPivot->setCellValue('D' . $row, $newLeftJO[$tech]['new_assigned']);
                    $row++;
                }
            }
        }
    }

    public function generateExcelTodayJOPlanned()
    {
        $plannedJOToday = $this->getPlannedJOToday();
        if ($plannedJOToday) {
            $JSONlastUpdateJOData = $this->getLastUpdatePlannedJOToday();
            $lastUpdateJOData = json_decode($JSONlastUpdateJOData, true);
            if ($lastUpdateJOData) {
                $lastUpdateJO = isset($lastUpdateJOData['message']) ? $lastUpdateJOData['message'] : null;
                $excelFile = $this->initExcelFile();

                if (file_exists($excelFile)) {
                    if (is_readable($excelFile)) {
                        try {
                            $technicians = [];
                            $dbTechnicians = $this->mainDB->select($this->tableTechnicians, ['technician']);
                            foreach ($dbTechnicians as $data) {
                                $technicians[] = isset($data['technician']) ? $data['technician'] : null;
                            }

                            $dbJOPlannedTOday = $this->projectTaskDB->select($this->todayPlanTable, [
                                "technician",
                                "sac_group",
                                "planned_date",
                                "wo_number",
                                "stage",
                                "company",
                                "task_type",
                                "last_stop",
                                "sla_deadline",
                                "data_on"
                            ]);
                            if ($dbJOPlannedTOday) {
                                /**
                                 * Sheet Master Data
                                 */
                                // ----------------------------------------------------------------------------------------------------------------------
                                $spreadSheet = new Spreadsheet();
                                $sheetMasterData = $spreadSheet->getActiveSheet();
                                $JOPlannedDatetime = DateTime::createFromFormat('d F Y H:i:s', $lastUpdateJO)->format('dMY h.i A');
                                $sheetMasterData->setTitle('JO Plan @' . $JOPlannedDatetime);
                                $headerMapping = [
                                    'technician' => 'Technician',
                                    'sac_group' => 'SAC Group',
                                    'planned_date' => 'Planned Date',
                                    'wo_number' => 'Work Order Number',
                                    'stage' => 'Job Stage',
                                    'company' => 'Company',
                                    'task_type' => 'Task Type',
                                    'last_stop' => 'Timesheet Last Stop',
                                    'sla_deadline' => 'SLA Deadline',
                                    'data_on' => 'Data Last Update On'
                                ];
                                $row = 1;
                                $colTitles = array_keys($dbJOPlannedTOday[0]);

                                $col = 'A';
                                foreach ($colTitles as $header) {
                                    $displayHeader = isset($headerMapping[$header]) ? $headerMapping[$header] : $header;
                                    $sheetMasterData->setCellValue($col . $row, $displayHeader);
                                    $col++;
                                }

                                $row++;

                                foreach ($dbJOPlannedTOday as $DBdata) {
                                    $col = 'A';
                                    foreach ($DBdata as $data) {
                                        $sheetMasterData->setCellValue($col . $row, $data);
                                        $col++;
                                    }
                                    $row++;
                                }

                                foreach (range('A', $col) as $columnID) {
                                    $sheetMasterData->getColumnDimension($columnID)->setAutoSize(true);
                                }
                                // ----------------------------------------------------------------------------------------------------------------------

                                // // newLeftJOTech
                                // $this->getNewLeftJOTechnicians($technicians, $spreadSheet);

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
                                return null;
                            }
                        } catch (Exception $e) {
                            $this->log->createLogMessage($e->getMessage());
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
            } else {
                return null;
            }
        } else {
            return 'Cannot get data engineers planned today!';
        }
    }

    public function generateExcelOverPlannedJO()
    {
        try {
            $JSONlastUpdateJOData = $this->getLastUpdatePlannedJOToday();
            $lastUpdateJOData = json_decode($JSONlastUpdateJOData, associative: true);
            if ($lastUpdateJOData) {
                $lastUpdateJO = isset($lastUpdateJOData['message']) ? $lastUpdateJOData['message'] : null;
                $JOPlannedDatetime = DateTime::createFromFormat('d F Y H:i:s', $lastUpdateJO)->format('dMY h.i A');
                $excelFile = $this->initExcelFile();
                if (file_exists($excelFile)) {
                    if (is_readable($excelFile)) {
                        $spreadSheet = new Spreadsheet();
                        $sheet = $spreadSheet->getActiveSheet();
                        $sheet->setTitle('Over JO @' . $JOPlannedDatetime);

                        $bold = true;
                        $bgColor = '047d24';
                        $fontFamily = 'Arial';
                        $fontSize = 10;
                        // Headers
                        $mergedHeaders = [
                            'A1:A2',
                            'B1:F1',
                            'G1:G2'
                        ];
                        $headers = [
                            ['A1', 'Technician'],
                            ['B1', 'JO Plan Stage'],
                            ['B2', 'New'],
                            ['C2', 'Done'],
                            ['D2', 'Open Pending'],
                            ['E2', 'Cancel'],
                            ['F2', 'Verified'],
                            ['G1', 'Grand Total Planned JO'],
                        ];
                        foreach ($headers as $head) {
                            $sheet->setCellValue($head[0], $head[1]);
                            $sheet->getStyle($head[0])->getFont()
                                ->setName($fontFamily)
                                ->setSize($fontSize)
                                ->setColor(new Color(Color::COLOR_RED))
                                ->setBold($bold);
                            // $sheet->getStyle($head[0])->getFill()
                            //     ->setFillType(Fill::FILL_SOLID)
                            //     ->getStartColor()
                            //     ->setARGB($bgColor);
                            $sheet->getStyle($head[0])->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                ->setVertical(Alignment::VERTICAL_CENTER);
                            $sheet->getStyle($head[0])->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                        }
                        foreach ($mergedHeaders as $merged) {
                            $sheet->mergeCells($merged);
                            $sheet->getStyle($merged)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                        }

                        foreach (range('A', 'G') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }

                        // Rows Data
                        $overPlannedParam = $this->config['ODOO_DATA']['MAX_PLANNED'];
                        $this->projectTaskDB->pdo->beginTransaction();
                        $sql = "SELECT technician, 
                                    SUM(CASE WHEN LOWER(stage) = 'new' THEN 1 ELSE 0 END) AS new_count,
                                    SUM(CASE WHEN LOWER(stage) = 'done' THEN 1 ELSE 0 END) AS done_count,
                                    SUM(CASE WHEN LOWER(stage) = 'open pending' THEN 1 ELSE 0 END) AS open_pending_count,
                                    SUM(CASE WHEN LOWER(stage) = 'cancel' THEN 1 ELSE 0 END) AS cancel_count,
                                    SUM(CASE WHEN LOWER(stage) = 'verified' THEN 1 ELSE 0 END) AS verified_count,
                                    COUNT(*) AS grand_total
                                FROM {$this->todayPlanTable}
                                GROUP BY technician
                                HAVING grand_total > {$overPlannedParam}";

                        $stmt = $this->projectTaskDB->pdo->prepare($sql);
                        $stmt->execute();
                        $this->projectTaskDB->pdo->commit();
                        $dbData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($dbData)) {
                            $row = 3;
                            foreach ($dbData as $index => $data) {
                                $col = 'A';
                                foreach ($data as $value) {
                                    $sheet->setCellValue($col . $row, $value);
                                    $sheet->getStyle($col . $row)->getAlignment()
                                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                        ->setVertical(Alignment::VERTICAL_CENTER);
                                    $sheet->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

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
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->projectTaskDB->pdo->rollBack();
            $this->log->createLogMessage($e->getMessage());
            return null;
        }
    }

    public function generateExcelLateUploadJO()
    {
        try {
            $lateUploadParam = date('Y-m-d ') . $this->config['ODOO_DATA']['LATE_UPLOAD_OVER'];
            $JSONlastUpdateJOData = $this->getLastUpdatePlannedJOToday();
            $lastUpdateJOData = json_decode($JSONlastUpdateJOData, associative: true);
            if ($lastUpdateJOData) {
                $lastUpdateJO = isset($lastUpdateJOData['message']) ? $lastUpdateJOData['message'] : null;
                $JOPlannedDatetime = DateTime::createFromFormat('d F Y H:i:s', $lastUpdateJO)->format('dMY h.i A');
                $excelFile = $this->initExcelFile();
                if (file_exists($excelFile)) {
                    if (is_readable($excelFile)) {
                        $whereConditions = [
                            "AND" => [
                                "last_stop[!]" => "",
                                "last_stop[>=]" => $lateUploadParam,
                                "OR" => [
                                    "last_update_on[>=]" => $lateUploadParam,
                                    "last_update_by[!~]" => ["%Admin%", "%Verifikasi%", "%Technical%"]
                                ]
                            ]
                        ];
                        $this->projectTaskDB->pdo->beginTransaction();
                        $dbData = $this->projectTaskDB->select($this->todayPlanTable, [
                            // "id",
                            "technician",
                            "sac_group",
                            "wo_number",
                            "task_type",
                            "last_stop",
                            "last_update_on",
                            "last_update_by",
                            "data_on"
                        ], $whereConditions);
                        $this->projectTaskDB->pdo->commit();

                        if (!empty($dbData)) {
                            $spreadSheet = new Spreadsheet();
                            $sheet = $spreadSheet->getActiveSheet();
                            $JOPlannedTime = DateTime::createFromFormat('d F Y H:i:s', $lastUpdateJO)->format('H.i');
                            $sheet->setTitle('Late Upload JO @' . $JOPlannedTime);

                            $headerMapping = [
                                'technician' => 'Technician',
                                'sac_group' => 'SAC Group',
                                'wo_number' => 'Work Order Number',
                                'task_type' => 'Task Type',
                                'last_stop' => 'Timesheet Last Stop JO',
                                'last_update_on' => 'Last Update JO On',
                                'last_update_by' => 'Last Update JO By',
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
                            $this->log->createLogMessage("Empty data in DB for Report Engineers Late Upload JO");
                            // return null;
                            return "No data found for Engineers Late Upload JO.";
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
            $this->projectTaskDB->pdo->rollBack();
            return null;
        }
    }

    public function generateExcelUnplannedJOToday()
    {
        try {
            $JSONlastUpdateJOData = $this->getLastUpdatePlannedJOToday();
            $lastUpdateJOData = json_decode($JSONlastUpdateJOData, true);
            if ($lastUpdateJOData) {
                $lastUpdateJO = isset($lastUpdateJOData['message']) ? $lastUpdateJOData['message'] : null;
            } else {
                $this->log->createLogMessage("Empty last update for Excel Unplanned JO Today");
                return null;
            }

            $techniciansPlannedToday = $this->projectTaskDB->select($this->todayPlanTable, ["technician"], [
                'GROUP' => 'technician'
            ]);
            if ($techniciansPlannedToday) {
                $listTechPlanToday = array_column($techniciansPlannedToday, 'technician');
                $unplannedTechToday = $this->projectTaskDB->select($this->tableNewAssignedJO, [
                    "technician"
                ], [
                    "AND" => [
                        "technician[!]" => $listTechPlanToday,
                        "planned_date" => null
                    ],
                    "GROUP" => "technician"
                ]);
                if ($unplannedTechToday) {
                    $unplannedTechDataToday = $this->projectTaskDB->select($this->tableNewAssignedJO, [
                        "technician",
                        "sac_group",
                        "wo_number",
                        "company",
                        "task_type",
                        "sla_deadline"
                    ], [
                        "AND" => [
                            "planned_date" => null,
                            "technician[!]" => $listTechPlanToday
                        ],
                        "ORDER" => [
                            "sac_group" => "ASC"
                        ]
                    ]);
                    if ($unplannedTechDataToday) {
                        $excelFile = $this->initExcelFile();
                        if (file_exists($excelFile)) {
                            if (is_readable($excelFile)) {
                                $spreadSheet = new Spreadsheet();
                                $sheetMasterData = $spreadSheet->getActiveSheet();
                                $lastUpdate = DateTime::createFromFormat('d F Y H:i:s', $lastUpdateJO)->format('dMY h.i A');
                                $sheetTitle = 'Unplanned@' . $lastUpdate;
                                $sheetMasterData->setTitle($sheetTitle);

                                $headerMapping = [
                                    'technician' => 'Technician',
                                    'sac_group' => 'SAC Group',
                                    'wo_number' => 'Work Order Number',
                                    'company' => 'Company',
                                    'task_type' => 'Task Type',
                                    'sla_deadline' => 'SLA Deadline'
                                ];
                                $row = 1;
                                $colTitles = array_keys($unplannedTechDataToday[0]);
                                $col = 'A';
                                foreach ($colTitles as $header) {
                                    $displayHeader = isset($headerMapping[$header]) ? $headerMapping[$header] : $header;
                                    $sheetMasterData->setCellValue($col . $row, $displayHeader);
                                    $col++;
                                }

                                $row++;

                                foreach ($unplannedTechDataToday as $DBdata) {
                                    $col = 'A';
                                    foreach ($DBdata as $data) {
                                        $sheetMasterData->setCellValue($col . $row, $data);
                                        $col++;
                                    }
                                    $row++;
                                }
                                foreach (range('A', $col) as $columnID) {
                                    $sheetMasterData->getColumnDimension($columnID)->setAutoSize(true);
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
                }
            }

            return null;
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

}

if (isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $todayJOPlanInstance = new todayJOPlanController();
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            switch ($action) {
                case 'get-today-planned-jo':
                    $response = $todayJOPlanInstance->getPlannedJOToday();
                    echo $response;
                    break;
                case 'get-last-update':
                    $lastUpdate = $todayJOPlanInstance->getLastUpdatePlannedJOToday();
                    echo $lastUpdate;
                    break;
                case 'generate-excel-today-jo-planned':
                    $excelFileURL = $todayJOPlanInstance->generateExcelTodayJOPlanned();
                    echo $excelFileURL;
                    break;
                case 'generate-excel-over-planned-jo':
                    $excelFileURL = $todayJOPlanInstance->generateExcelOverPlannedJO();
                    echo $excelFileURL;
                    break;
                case 'generate-excel-late-upload-jo':
                    $excelFileURL = $todayJOPlanInstance->generateExcelLateUploadJO();
                    echo $excelFileURL;
                    break;
                case 'generate-excel-unplanned-jo':
                    $excelFileURL = $todayJOPlanInstance->generateExcelUnplannedJOToday();
                    echo $excelFileURL;
                    break;
                case 'get-datatables':
                    $todayJOPlanInstance->getDatatables();
                    break;
                case 'get-datatables-over-planned-jo':
                    $todayJOPlanInstance->getDatatablesOverPlannedJO();
                    break;
                case 'get-datatables-late-upload-jo':
                    $todayJOPlanInstance->getDatatablesLateUploadJO();
                    break;
                case 'get-datatables-unplanned-technicians':
                    $data = $todayJOPlanInstance->getDatatablesUnplannedTechnicians();
                    echo $data;
                    break;
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
        $todayJOPlanInstance = new todayJOPlanController();

        $error = new errorController();
        $errorCode = 400;
        $errorPage = $error->getPathError($errorCode, null);
        http_response_code($errorCode);
        header("Location: $errorPage");
        exit();
    }
}