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
                "data_on" => ["DATETIME", "DEFAULT CURRENT_TIMESTAMP"],
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
        // // delete this soon!! 
        // $planStartFormatted = "2024-10-15 00:00:00";
        // $planEndFormatted = "2024-10-15 23:59:59";

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
                    "timesheet_timer_last_stop"
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
                    id, technician, planned_date, `long`, `lat`, wo_number, stage, company, task_type, last_stop, sla_deadline, sac_group
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

                    $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
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
            'draw' => intval($_POST['draw']),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ];

        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

        // Handle ordering
        $order = isset($_POST['order']) ? $_POST['order'] : [];
        $columns = isset($_POST['columns']) ? $_POST['columns'] : [];

        // Default order by last_login ASC if no custom order is provided
        $orderBy = ['id' => 'ASC'];

        // Map DataTables columns to your database columns
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

        // Process the order array if provided
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

        // Get the total records count
        $totalRecords = $this->projectTaskDB->count($this->todayPlanTable);
        $response['recordsTotal'] = $totalRecords;

        // Prepare search conditions
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

        // Get the total records after filtering
        $totalFilteredRecords = $this->projectTaskDB->count($this->todayPlanTable, $searchConditions);
        $response['recordsFiltered'] = $totalFilteredRecords;

        // Fetch filtered data with pagination and ordering
        $data = $this->projectTaskDB->select($this->todayPlanTable, '*', [
            'LIMIT' => [$start, $length],
            'ORDER' => $orderBy,  // Apply ordering
        ] + $searchConditions);

        // Populate the response data
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

        // Return the JSON response
        echo json_encode($response);
    }

    private function initExcelTodayJOPlanned()
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
            $dateTimeGetNewLeftJO = date('dFY h.i A');
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
                $excelFile = $this->initExcelTodayJOPlanned();

                if (file_exists($excelFile)) {
                    if (is_readable($excelFile)) {
                        try {
                            $technicians = [];
                            $dbTechnicians = $this->mainDB->select($this->tableTechnicians, ['technician']);
                            foreach ($dbTechnicians as $data) {
                                $technicians[] = isset($data['technician']) ? $data['technician'] : null;
                            }

                            $spreadSheet = new Spreadsheet();
                            $sheetMasterData = $spreadSheet->getActiveSheet();
                            $JOPlannedDatetime = DateTime::createFromFormat('d F Y H:i:s', $lastUpdateJO)->format('dFY h.i A');
                            $sheetMasterData->setTitle('JO Plan @' . $JOPlannedDatetime);

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
                                        $col++; // Move to next column
                                    }
                                    $row++;
                                }

                                foreach (range('A', $col) as $columnID) {
                                    $sheetMasterData->getColumnDimension($columnID)->setAutoSize(true);
                                }
                                // ----------------------------------------------------------------------------------------------------------------------

                                // newLeftJOTech
                                $this->getNewLeftJOTechnicians($technicians, $spreadSheet);

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
}

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
            case 'get-datatables':
                $todayJOPlanInstance->getDatatables();
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