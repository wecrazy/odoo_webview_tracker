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

class techLoginController
{
    private $log;
    private $config;
    private $medooDB;
    private $tableTechnicians;
    private $tableDatatables;
    private $error;
    private $api;

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
        $this->medooDB = databaseController::getInstance()->getConnection();

        $this->tableTechnicians = $this->config['DATABASE']['TB_TECHNICIANS'];
        $this->tableDatatables = $this->config['DATABASE']['TB_DATATABLES'];

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

    public function getListActiveTechnicians()
    {
        $params = [
            'jsonrpc' => $this->config['ODOO_API']['JSONRPC'],
            'params' => [
                'domain' => [
                    ['active', '=', true],
                    ['name', '!=', 'Tes Dev Mfjr']
                ],
                'model' => 'fs.technician',
                'fields' => [
                    'name',
                    'id',
                    'download_ids',
                    'login_ids',
                    'technician_latitude',
                    'technician_longitude'
                ],
                'order' => 'id asc'
            ]
        ];

        $Technicians = $this->api->OdooAPI('GetData', $params);

        if (!empty($Technicians)) {
            $idsDownload = [];
            $idsLogin = [];
            $technicianList = [];
            $longitudes = [];
            $latitudes = [];

            foreach ($Technicians as $index => $data) {
                $id = isset($data['id']) ? $data['id'] : '';
                $techName = isset($data['name']) ? $data['name'] : '';
                if (empty($techName)) {
                    $techName = 'Unknown';
                }
                $downloadID = isset($data['download_ids']) ? $data['download_ids'] : '';
                if (empty($downloadID)) {
                    $idsDownload[] = null;
                } else {
                    rsort($downloadID);
                    $idsDownload[] = $downloadID[0];
                }
                $loginID = isset($data['login_ids']) ? $data['login_ids'] : '';
                if (empty($loginID)) {
                    $idsLogin[] = null;
                } else {
                    rsort($loginID);
                    $idsLogin[] = $loginID[0];
                }

                if (!isset($technicianList[$id])) {
                    $technicianList[$id] = $techName;
                }

                $longitude = isset($data['technician_longitude']) ? $data['technician_longitude'] : '';
                if (!isset($longitudes[$techName])) {
                    $longitudes[$techName] = $longitude;
                }
                $latitude = isset($data['technician_latitude']) ? $data['technician_latitude'] : '';
                if (!isset($latitudes[$techName])) {
                    $latitudes[$techName] = $latitude;
                }
            }

            if (!empty($idsDownload) && !empty($idsLogin)) {
                $loginData = $this->getLastLoginTechnicians($idsLogin);
                $downloadData = $this->getLastDownloadTechnicians($idsDownload);

                try {
                    $this->medooDB->pdo->beginTransaction();
                    $this->medooDB->update($this->tableDatatables, [
                        'status' => 'Refresh'
                    ], ['id' => 'dt_technicians']);
                    $this->medooDB->pdo->commit();

                    $this->medooDB->pdo->beginTransaction();
                    $this->medooDB->delete($this->tableTechnicians, []);
                    $this->medooDB->pdo->commit();

                    $this->medooDB->pdo->beginTransaction();
                    $sql = "INSERT INTO $this->tableTechnicians (
                        id, technician, region_group, last_login, last_download, `status`, longitude, latitude, first_upload
                    ) VALUES ";

                    $placeholders = [];
                    $bindValues = [];

                    foreach ($technicianList as $id => $name) {
                        $group = $this->regional($name);
                        $lastLogin = isset($loginData[$name]) ? $loginData[$name] : null;
                        $lastDownload = isset($downloadData[$name]) ? $downloadData[$name] : null;
                        if (!empty($lastLogin)) {
                            $lastLogin = DateTime::createFromFormat('d-m-Y H:i:s', $lastLogin)->format('Y-m-d H:i:s');
                        }

                        if (!empty($lastDownload)) {
                            $lastDownload = DateTime::createFromFormat('d-m-Y H:i:s', $lastDownload)->format('Y-m-d H:i:s');
                        }

                        $epoch_login = !empty($lastLogin) ? strtotime($lastLogin) : null;
                        $epoch_download = !empty($lastDownload) ? strtotime($lastDownload) : null;

                        $epoch_warningLogin = strtotime(date('Y-m-d 00:00:00'));
                        $epoch_warningDownload = strtotime(date('Y-m-d 00:00:00'));

                        $status = '';

                        if (is_null($epoch_login) && is_null($epoch_download)) {
                            $status = 'This Technician Never Login!';
                        } else {
                            if (!is_null($epoch_login)) {
                                if ($epoch_login < $epoch_warningLogin) {
                                    if (!is_null($epoch_download) && $epoch_download > $epoch_warningDownload) {
                                        $status = 'Login';
                                    } else {
                                        $status = 'This technician did not log in today.';
                                    }
                                } else {
                                    $status = 'Login';
                                }
                            } else {
                                $status = 'Technician never logged in, but has a download history.';
                            }
                        }

                        $techLon = isset($longitudes[$name]) ? $longitudes[$name] : null;
                        $techLat = isset($latitudes[$name]) ? $latitudes[$name] : null;

                        $dataFirstUpload = $this->getFirstUploadTechnician($name);
                        $techFirstUpload = !empty($dataFirstUpload) ? $dataFirstUpload : null;

                        $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?)';
                        $bindValues[] = $id;
                        $bindValues[] = $name;
                        $bindValues[] = $group;
                        $bindValues[] = $lastLogin;
                        $bindValues[] = $lastDownload;
                        $bindValues[] = $status;
                        $bindValues[] = $techLon;
                        $bindValues[] = $techLat;
                        $bindValues[] = $techFirstUpload;
                    }

                    $sql .= implode(', ', $placeholders);

                    $stmt = $this->medooDB->pdo->prepare($sql);
                    $stmt->execute($bindValues);
                    $this->medooDB->pdo->commit();

                    $this->medooDB->pdo->beginTransaction();
                    $this->medooDB->update($this->tableDatatables, [
                        'status' => 'Not Refresh'
                    ], ['id' => 'dt_technicians']);
                    $this->medooDB->pdo->commit();

                    $response = [
                        'status' => 'success',
                        'message' => 'Table Technicians Success Updated! @' . date('d F Y H:i:s')
                    ];
                    return json_encode($response);
                } catch (Exception $e) {
                    $this->log->createLogMessage($e->getMessage());
                    $this->medooDB->pdo->rollBack();
                    return null;
                }
            } else {
                $this->log->createLogMessage('Empty ids Download & Login!!');
                return null;
            }
        } else {
            $this->log->createLogMessage('Empty odoo data for request all list active technicians!');
            return null;
        }
    }

    private function getLastLoginTechnicians($id)
    {
        if (empty($id)) {
            $this->log->createLogMessage('Empty ID to search last login!');
            return null;
        } else {
            $loginParams = [
                'jsonrpc' => $this->config['ODOO_API']['JSONRPC'],
                'params' => [
                    'domain' => [
                        ['id', '=', $id]
                    ],
                    'model' => 'technician.login',
                    'fields' => [
                        'login_time',
                        'technician_id'
                    ],
                    'order' => 'id asc'
                ]
            ];
            $loginData = $this->api->OdooAPI('GetData', $loginParams);
            if (!empty($loginData)) {
                $dataOfLogin = [];
                foreach ($loginData as $index => $data) {
                    $loginTime = isset($data['login_time']) ? $data['login_time'] : '';
                    $technician = isset($data['technician_id'][1]) ? $data['technician_id'][1] : '';
                    if (empty($technician)) {
                        $technician = 'Unknown';
                    }
                    if (!isset($dataOfLogin[$technician])) {
                        $dataOfLogin[$technician] = $loginTime;
                    }
                }
                if (!empty($dataOfLogin)) {
                    return $dataOfLogin;
                } else {
                    $this->log->createLogMessage('Empty data of login!');
                    return null;
                }
            } else {
                $this->log->createLogMessage("Empty data for last login id: $id");
                return null;
            }
        }
    }

    private function getLastDownloadTechnicians($id)
    {
        if (empty($id)) {
            $this->log->createLogMessage('Empty ID to search last download!');
            return null;
        } else {
            $downloadParams = [
                'jsonrpc' => $this->config['ODOO_API']['JSONRPC'],
                'params' => [
                    'domain' => [
                        ['id', '=', $id]
                    ],
                    'model' => 'technician.download',
                    'fields' => [
                        'download_time',
                        'technician_id'
                    ],
                    'order' => 'id asc'
                ]
            ];
            $downloadData = $this->api->OdooAPI('GetData', $downloadParams);
            if (!empty($downloadData)) {
                $dataOfDownload = [];
                foreach ($downloadData as $index => $data) {
                    $downloadTime = isset($data['download_time']) ? $data['download_time'] : '';
                    $technician = isset($data['technician_id'][1]) ? $data['technician_id'][1] : '';
                    if (empty($technician)) {
                        $technician = 'Unknown';
                    }
                    if (!isset($dataOfDownload[$technician])) {
                        $dataOfDownload[$technician] = $downloadTime;
                    }
                }
                if (!empty($dataOfDownload)) {
                    return $dataOfDownload;
                } else {
                    $this->log->createLogMessage('Empty data of download!');
                    return null;
                }
            } else {
                $this->log->createLogMessage("Empty data for last download id: $id");
                return null;
            }
        }
    }

    public function getLastUpdateTechnicians()
    {
        try {
            $lastUpdate = $this->medooDB->pdo->query("SELECT MIN(last_update) as last_update FROM $this->tableTechnicians")->fetchColumn();
            $result = null;
            if (!empty($lastUpdate)) {
                $result = DateTime::createFromFormat('Y-m-d H:i:s', $lastUpdate)->format('d F Y H:i:s');
            }
            return $result;
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            return json_encode($response);
        }
    }

    private function getFirstUploadTechnician($technician)
    {
        if (empty($technician)) {
            $this->log->createLogMessage('Empty technician to search first upload JO!');
            return null;
        } else {
            $dateStart = date('Y-m-d 00:00:00');
            $dateEnd = date('Y-m-d 23:59:59');

            $dateStartParam = new DateTime($dateStart);
            $dateStartParam->modify("-7 hours");

            $dateEndParam = new DateTime($dateEnd);
            $dateEndParam->modify("-7 hours");

            $uploadParams = [
                'jsonrpc' => $this->config['ODOO_API']['JSONRPC'],
                'params' => [
                    'domain' => [
                        ['active', '=', true],
                        ['planned_date_begin', '>=', $dateStart],
                        ['planned_date_begin', '<=', $dateEnd],
                        // ['planned_date_begin', '>=', $dateStartParam->format("Y-m-d H:i:s")],
                        // ['planned_date_begin', '<=', $dateEndParam->format("Y-m-d H:i:s")],
                        ['technician_id', '=', $technician],
                        ['timesheet_timer_last_stop', '!=', false],
                        ['stage_id', '!=', ["New", "Open Pending"]],
                    ],
                    'model' => 'project.task',
                    'fields' => [
                        'timesheet_timer_last_stop',
                    ],
                    'order' => 'id asc'
                ]
            ];
            $uploadData = $this->api->OdooAPI('GetData', $uploadParams);
            if (!empty($uploadData)) {
                $dateUpload = [];
                foreach ($uploadData as $index => $data) {
                    $uploadDatetime = isset($data['timesheet_timer_last_stop']) ? $data['timesheet_timer_last_stop'] : null;
                    if (empty($uploadDatetime)) {
                        continue;
                    } else {
                        $dateUpload[] = $uploadDatetime;
                    }
                }

                if (empty($dateUpload)) {
                    return null;
                } else {
                    sort($dateUpload);
                    $oldestDatetime = $dateUpload[0];
                    $dateTime = new DateTime($oldestDatetime);
                    $dateTime->modify('+7 hours');
                    return $dateTime->format('Y-m-d H:i:s');
                }
            } else {
                $this->log->createLogMessage('Empty data of tech first upload data!');
                return null;
            }
        }
    }

    public function getDatatables()
    {
        $response = [
            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ];

        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

        $order = isset($_POST['order']) ? $_POST['order'] : [];
        $columns = isset($_POST['columns']) ? $_POST['columns'] : [];

        // $orderBy = ['last_login' => 'ASC'];

        $columnMap = [
            1 => 'id',
            2 => 'technician',
            3 => 'region_group',
            4 => 'last_login',
            5 => 'last_download',
            6 => 'status',
            7 => 'first_upload'
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

        $totalRecords = $this->medooDB->count($this->tableTechnicians);
        $response['recordsTotal'] = $totalRecords;

        $searchConditions = [];
        if (!empty($searchValue)) {
            $searchConditions = [
                'OR' => [
                    'technician[~]' => $searchValue,
                    'region_group[~]' => $searchValue,
                    // 'last_login[~]' => $searchValue,
                    // 'last_download[~]' => $searchValue,
                    'status[~]' => $searchValue
                ]
            ];
        }

        $totalFilteredRecords = $this->medooDB->count($this->tableTechnicians, $searchConditions);
        $response['recordsFiltered'] = $totalFilteredRecords;

        // $data = $this->medooDB->select($this->tableTechnicians, '*', [
        $data = $this->medooDB->select($this->tableTechnicians, [
            "id",
            "technician",
            "region_group",
            "last_login",
            "last_download",
            "status",
            "first_upload"
        ], [
            'LIMIT' => [$start, $length],
            'ORDER' => $orderBy ? $orderBy : ['last_login' => 'ASC'],
        ] + $searchConditions);

        foreach ($data as $row) {
            $response['data'][] = [
                'id' => $row['id'],
                'technician' => $row['technician'],
                'region_group' => $row['region_group'],
                'last_login' => $row['last_login'],
                'last_download' => $row['last_download'],
                'status' => $row['status'],
                'first_upload' => $row['first_upload'],
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

    public function generateExcelTechnicians()
    {
        $excelFile = $this->initExcelFile();
        if (file_exists($excelFile)) {
            if (is_readable($excelFile)) {
                try {
                    $dataDB = $this->medooDB->select($this->tableTechnicians, [
                        'technician',
                        'region_group',
                        'last_login',
                        'last_download',
                        'status',
                        'first_upload'
                    ]);

                    if ($dataDB === false) {
                        return null;
                    } elseif (empty($dataDB)) {
                        return null;
                    } else {
                        $spreadSheet = new Spreadsheet();
                        $lastUpdate = $this->getLastUpdateTechnicians();
                        $sheetName = DateTime::createFromFormat('d F Y H:i:s', $lastUpdate)->format('dMY_H-i-s');
                        $sheet = $spreadSheet->getActiveSheet();
                        $sheet->setTitle($sheetName);

                        // Headers
                        $row = 1;
                        $bold = true;
                        $bgColor = '047d24';
                        $fontFamily = 'Arial';
                        $fontSize = 10;
                        $cols = [
                            'technician' => 'A',
                            'region_group' => 'B',
                            'last_download' => 'C',
                            'last_login' => 'D',
                            'status' => 'E',
                            'first_upload' => 'F',
                        ];
                        $headers = [
                            ['A', 'Technician', 25],
                            ['B', 'Group', 16],
                            ['C', 'Last Download', 20],
                            ['D', 'Last Login', 20],
                            ['E', 'Status', 30],
                            ['F', 'First Upload', 20],
                        ];
                        foreach ($headers as $head) {
                            $sheet
                                ->setCellValue($head[0] . $row, $head[1])
                                ->getColumnDimension($head[0])
                                ->setWidth($head[2]);
                            $sheet->getStyle($head[0] . $row)->getFont()
                                ->setName($fontFamily)
                                ->setSize($fontSize)
                                ->setColor(new Color(Color::COLOR_WHITE))
                                ->setBold($bold);
                            $sheet->getStyle($head[0] . $row)->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setARGB($bgColor);
                            $sheet->getStyle($head[0] . $row)->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                ->setVertical(Alignment::VERTICAL_CENTER);
                            $sheet->getStyle($head[0] . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                        }

                        // Rows data
                        $row = 2;
                        $fontSize = 8;

                        foreach ($dataDB as $index => $data) {
                            foreach ($data as $key => $value) {
                                switch ($value) {
                                    case 'This technician did not log in today.':
                                    case 'This Technician Never Login!':
                                    case 'Technician never logged in, but has a download history.':
                                        $color = 'FF0000';
                                        break;
                                    default:
                                        $color = '000000';
                                        break;
                                }
                                $sheet->setCellValue($cols[$key] . $row, $value);
                                $sheet->getStyle($cols[$key] . $row)->getFont()
                                    ->setSize($fontSize)
                                    ->setColor(new Color($color))
                                    ->setName($fontFamily);
                                $sheet->getStyle($cols[$key] . $row)->getAlignment()
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                    ->setVertical(Alignment::VERTICAL_CENTER);
                                $sheet->getStyle($cols[$key] . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                            }
                            $row++;
                        }

                        $sheet->setAutoFilter("A1:F1");

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
    }
}

if (isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $techLoginInstance = new techLoginController();

        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            switch ($action) {
                case 'get-all-technicians':
                    $response = $techLoginInstance->getListActiveTechnicians();
                    echo $response;
                    break;
                case 'get-last-update':
                    $lastUpdate = $techLoginInstance->getLastUpdateTechnicians();
                    echo $lastUpdate;
                    break;
                case 'generate-excel':
                    $excelFileURL = $techLoginInstance->generateExcelTechnicians();
                    echo $excelFileURL;
                    break;
                case 'get-datatables':
                    $techLoginInstance->getDatatables();
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
        $techLoginInstance = new techLoginController();

        $error = new errorController();
        $errorCode = 400;
        $errorPage = $error->getPathError($errorCode, null);
        http_response_code($errorCode);
        header("Location: $errorPage");
        exit();
    }
}
