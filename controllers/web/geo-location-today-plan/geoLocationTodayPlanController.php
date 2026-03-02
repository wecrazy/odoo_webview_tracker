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
use Symfony\Component\Yaml\Yaml;

class geoLocationTodayPlanController
{
    private $log;
    private $config;
    private $mainDB;
    private $projectTaskDB;
    private $error;
    private $api;
    private $tableTechnicians;
    private $tablePlannedToday;
    private $tableNewAssignUnplanJO;

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

        $this->tablePlannedToday = strtolower(date('F_d'));
        $this->tableTechnicians = $this->config['DATABASE']['TB_TECHNICIANS'];
        $this->tableNewAssignUnplanJO = $this->config['DATABASE_PROJECT_TASK']['TB_NEW_ASSIGNED_JO'];

        /*-----------------------
        |:                     :|
        |:  API: ODOO          :|
        |:                     :|
        -----------------------*/
        // $odooAPIPaths = [
        //     __DIR__ . '/api/odoo_api.php',
        //     dirname(__DIR__) . '/api/odoo_api.php',
        //     dirname(__DIR__, 2) . '/api/odoo_api.php'
        // ];

        // $pathFound = false;

        // foreach ($odooAPIPaths as $path) {
        //     if (file_exists($path)) {
        //         require_once $path;
        //         $pathFound = true;
        //         break;
        //     }
        // }

        // if (!$pathFound) {
        //     throw new \Exception('API ODOO Path not found!');
        // }
        // $this->api = new odoo_api();
    }

    private function regional($name)
    {
        $parts = explode(' ', $name);
        $first_word = $parts[0];
        $cleaned_word = preg_replace('/[^a-zA-Z0-9]/', '', $first_word);
        return $cleaned_word;
    }

    public function getLastUpdateData()
    {
        try {
            $response = [];
            $lastUpdate = $this->projectTaskDB->query("SELECT MIN(data_on) as last_update FROM $this->tablePlannedToday")->fetchColumn();
            $result = null;
            if (!empty($lastUpdate)) {
                $response['original_datetime'] = $lastUpdate;
                $result = DateTime::createFromFormat('Y-m-d H:i:s', $lastUpdate)->format('d F Y H:i:s');
            }
            $response['status'] = 'success';
            $response['message'] = $result;

            return json_encode($response);
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

    public function getSACGroupList()
    {
        try {
            $listsData = $this->projectTaskDB->select($this->tablePlannedToday, [
                "sac_group"
            ], [
                "GROUP" => "sac_group",
                "ORDER" => ["sac_group" => "ASC"]
            ]);
            $response = [];
            foreach ($listsData as $data) {
                $response['tom_select_options'][] = [
                    'value' => $data['sac_group'],
                    'text' => "Group: " . $data['sac_group']
                ];
            }

            return json_encode($response);
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

    public function getTechnicianList()
    {
        try {
            $listsData = $this->projectTaskDB->select($this->tablePlannedToday, [
                "technician"
            ], [
                "GROUP" => "technician",
                "ORDER" => ["technician" => "ASC"]
            ]);
            $response = [];
            foreach ($listsData as $data) {
                $response['tom_select_options'][] = [
                    'value' => $data['technician'],
                    'text' => $data['technician']
                ];
            }

            return json_encode($response);
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

    public function getCompanyList()
    {
        try {
            $listsData = $this->projectTaskDB->select($this->tablePlannedToday, [
                "company"
            ], [
                "GROUP" => "company",
                "ORDER" => ["company" => "ASC"]
            ]);
            $response = [];
            foreach ($listsData as $data) {
                $response['tom_select_options'][] = [
                    'value' => $data['company'],
                    'text' => $data['company']
                ];
            }

            return json_encode($response);
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

    public function getStageList()
    {
        try {
            $listsData = $this->projectTaskDB->select($this->tablePlannedToday, [
                "stage"
            ], [
                "GROUP" => "stage",
                "ORDER" => ["stage" => "ASC"]
            ]);
            $response = [];
            foreach ($listsData as $data) {
                $response['tom_select_options'][] = [
                    'value' => $data['stage'],
                    'text' => $data['stage']
                ];
            }

            return json_encode($response);
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

    public function getTaskTypeList()
    {
        try {
            $listsData = $this->projectTaskDB->select($this->tablePlannedToday, [
                "task_type"
            ], [
                "GROUP" => "task_type",
                "ORDER" => ["task_type" => "ASC"]
            ]);
            $response = [];
            foreach ($listsData as $data) {
                $response['tom_select_options'][] = [
                    'value' => $data['task_type'],
                    'text' => $data['task_type']
                ];
            }

            return json_encode($response);
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }

    public function getGeoLocationTodayPlannedJO($sacGroup, $technician, $company, $stage, $taskType, $slaDeadlineRange)
    {
        try {
            $columns = [
                "technician",
                // "planned_date",
                "long",
                "lat",
                "wo_number",
                "stage",
                "company",
                "task_type",
                "last_stop",
                "sla_deadline",
                "sac_group",
                "ticket_name",
                "merchant_mid",
                "merchant_tid",
                "merchant_name"
            ];

            $whereConditions = [
                "AND" => [],
                "ORDER" => [
                    "technician" => "ASC",
                    "last_stop" => "ASC"
                ]
            ];

            if (isset($sacGroup) && !empty($sacGroup)) {
                $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                    "sac_group[~]" => $sacGroup
                ]);
            }

            if (isset($technician) && !empty($technician)) {
                $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                    "technician[~]" => $technician
                ]);
            }

            if (isset($company) && !empty($company)) {
                $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                    "company[~]" => $company
                ]);
            }

            if (isset($stage) && !empty($stage)) {
                $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                    "stage[~]" => $stage
                ]);
            }

            if (isset($taskType) && !empty($taskType)) {
                $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                    "task_type[~]" => $taskType
                ]);
            }

            if (isset($slaDeadlineRange) && !empty($slaDeadlineRange)) {
                list($slaStart, $slaEnd) = explode(' - ', $slaDeadlineRange);
                // $slaStart = date('Y-m-d H:i:s', strtotime($slaStart));
                // $slaEnd = date('Y-m-d H:i:s', strtotime($slaEnd));
                $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                    "sla_deadline[<>]" => [$slaStart, $slaEnd]
                ]);
            }

            $dataPlannedToday = $this->projectTaskDB->select($this->tablePlannedToday, $columns, $whereConditions);

            if (!empty($dataPlannedToday)) {
                $groupedByTechnician = [];
                foreach ($dataPlannedToday as $data) {
                    $groupedByTechnician[$data['technician']][] = $data;
                }

                $result = [];

                $lineCoords = [];
                $defaultLatitude = $this->config['APP']['MAPS']['GEO_LOCATION_TODAY_PLAN']['DEFAULT_LATITUDE'];
                $defaultLongitude = $this->config['APP']['MAPS']['GEO_LOCATION_TODAY_PLAN']['DEFAULT_LONGITUDE'];

                foreach ($groupedByTechnician as $technician => $records) {
                    // usort($records, function ($a, $b) {
                    //     return empty($a['last_stop']) ? 1 : (empty($b['last_stop']) ? -1 : strtotime($a['last_stop']) <=> strtotime($b['last_stop']));
                    // });

                    // $counter = 1;
                    // $dataCoordsTechnician = [];

                    // foreach ($records as &$record) {
                    //     if (isset($record['last_stop']) && !empty($record['last_stop'])) {
                    //         $record['number'] = $counter;
                    //         $counter++;

                    //         $dataCoordsTechnician[] = [
                    //             'lat' => !empty($record['lat']) && $record['lat'] != 0.0 ? $record['lat'] : $defaultLatitude,
                    //             'lng' => !empty($record['long']) && $record['long'] != 0.0 ? $record['long'] : $defaultLongitude
                    //         ];
                    //     } else {
                    //         $record['number'] = 0;
                    //     }
                    //     $result[] = $record;
                    // }

                    // if (count($dataCoordsTechnician) > 1) {
                    //     $lineCoords[] = $dataCoordsTechnician;
                    // }

                    // =================================================
                    $counter = 1;
                    $dataCoordsTechnician = [];

                    foreach ($records as &$record) {
                        if (isset($record['last_stop']) && !empty($record['last_stop'])) {
                            $record['number'] = $counter;
                            $counter++;

                            // Add latitude and longitude, using defaults if empty
                            $dataCoordsTechnician[] = [
                                'lat' => !empty($record['lat']) && $record['lat'] != 0.0 ? $record['lat'] : $defaultLatitude,
                                'lng' => !empty($record['long']) && $record['long'] != 0.0 ? $record['long'] : $defaultLongitude
                            ];
                        } else {
                            $record['number'] = 0; // Unassigned 'last_stop' records get 0
                        }
                        $result[] = $record;
                    }

                    // Only add to lineCoords if there are multiple coordinates for the technician
                    if (count($dataCoordsTechnician) > 1) {
                        $lineCoords[] = $dataCoordsTechnician;
                    }

                    // =================================================

                }

                // This is got error for lineCoords in JS !!
                // if (count($lineCoords) == 1) {
                //     // $lineCoords[] = [
                //     //     'lat' => $defaultLatitude,
                //     //     'lng' => $defaultLongitude
                //     // ];
                // }

                if (!empty($lineCoords)) {
                    $dataLineCoords = $lineCoords;
                } else {
                    $dataLineCoords = null;
                }

                $totalPlanToday = $this->projectTaskDB->count($this->tablePlannedToday, $whereConditions);

                // Cloned conditions
                $conditionForTotalNewPlan = $whereConditions;
                $conditionForUnvisitJO = $whereConditions;

                // Total New Plan
                if (!isset($stage) && empty($stage)) {
                    $conditionForTotalNewPlan['AND'] = array_merge($conditionForTotalNewPlan['AND'], [
                        "stage" => 'New'
                    ]);
                } else {
                    foreach ($conditionForTotalNewPlan['AND'] as $key => $condition) {
                        if (strpos($key, 'stage') !== false && strpos($key, '[~]') !== false) {
                            $conditionForTotalNewPlan['AND'][$key] = "stage = 'New'";
                            break;
                        }
                    }
                }
                $totalNewPlanToday = $this->projectTaskDB->count($this->tablePlannedToday, $conditionForTotalNewPlan);

                $conditionForUnvisitJO['AND'] = array_merge($conditionForUnvisitJO['AND'], [
                    "last_stop" => null
                ]);
                $totalUnvisitJOToday = $this->projectTaskDB->count($this->tablePlannedToday, $conditionForUnvisitJO);

                $newUnplanJO = null;
                if (!empty($result)) {
                    $columns = [
                        "technician",
                        "long",
                        "lat",
                        "wo_number",
                        "company",
                        "task_type",
                        "sla_deadline",
                        "sac_group",
                        "received_spk",
                        "ticket_name",
                        "merchant_mid",
                        "merchant_tid",
                        "merchant_name"
                    ];

                    $whereConditions = ["AND" => []];
                    $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                        "planned_date" => null
                    ]);
                    if (isset($sacGroup) && !empty($sacGroup)) {
                        $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                            "sac_group[~]" => $sacGroup
                        ]);
                    }

                    if (isset($technician) && !empty($technician)) {
                        $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                            "technician[~]" => $technician
                        ]);
                    }

                    if (isset($company) && !empty($company)) {
                        $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                            "company[~]" => $company
                        ]);
                    }

                    if (isset($taskType) && !empty($taskType)) {
                        $whereConditions['AND'] = array_merge($whereConditions['AND'], [
                            "task_type[~]" => $taskType
                        ]);
                    }

                    $unplanJOData = $this->projectTaskDB->select($this->tableNewAssignUnplanJO, $columns, $whereConditions);

                    if (!empty($unplanJOData)) {
                        $newUnplanJO = $unplanJOData;
                    }
                }

                $lastResult = [
                    'leaflet_data' => isset($result) ? $result : null,
                    'today_total_plan' => isset($totalPlanToday) ? $totalPlanToday : 0,
                    'today_new_plan' => isset($totalNewPlanToday) ? $totalNewPlanToday : 0,
                    'total_unvisit_jo' => isset($totalUnvisitJOToday) ? $totalUnvisitJOToday : 0,
                    'line_coords' => $dataLineCoords,
                    'unplan_jo' => isset($newUnplanJO) ? $newUnplanJO : null,
                    'total_unplan_jo' => !empty($newUnplanJO) ? count($newUnplanJO) : 0
                ];

                return json_encode($lastResult);
            } else {
                return "No data found!";
            }
        } catch (Exception $e) {
            $this->log->createLogMessage($e->getMessage());
            return $e->getMessage();
        }
    }
}

if (isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $geoLocationPlanTodayInstance = new geoLocationTodayPlanController();
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            switch ($action) {
                case 'get-last-update':
                    $lastUpdate = $geoLocationPlanTodayInstance->getLastUpdateData();
                    echo $lastUpdate;
                    break;
                case 'get-sac-group':
                    $lists = $geoLocationPlanTodayInstance->getSACGroupList();
                    echo $lists;
                    break;
                case 'get-technician-list':
                    $lists = $geoLocationPlanTodayInstance->getTechnicianList();
                    echo $lists;
                    break;
                case 'get-company-list':
                    $lists = $geoLocationPlanTodayInstance->getCompanyList();
                    echo $lists;
                    break;
                case 'get-stage-list':
                    $lists = $geoLocationPlanTodayInstance->getStageList();
                    echo $lists;
                    break;
                case 'get-task-type-list':
                    $lists = $geoLocationPlanTodayInstance->getTaskTypeList();
                    echo $lists;
                    break;
                case 'get-geo-location-planned-today':
                    $formData = isset($_POST['form_data']) ? $_POST['form_data'] : null;
                    if (empty($formData)) {
                        echo "Data request not found !";
                    } else {
                        $decodedFormData = urldecode($formData);
                        $formDataArray = [];
                        parse_str($decodedFormData, $formDataArray);

                        $sacGroup = null;
                        $technician = null;
                        $company = null;
                        $stage = null;
                        $taskType = null;
                        $slaDeadlineRange = null;

                        if (empty($formDataArray)) {
                            echo "Wrong form data! Cannot access the main data to process!";
                        } else {
                            if (isset($formDataArray['sac_group']) && count(array_values($formDataArray['sac_group'])) > 0) {
                                $sacGroup = array_values($formDataArray['sac_group']);
                            }
                            if (isset($formDataArray['technician']) && count(array_values($formDataArray['technician'])) > 0) {
                                $technician = array_values($formDataArray['technician']);
                            }
                            if (isset($formDataArray['company']) && count(array_values($formDataArray['company'])) > 0) {
                                $company = array_values($formDataArray['company']);
                            }
                            if (isset($formDataArray['stage']) && count(array_values($formDataArray['stage'])) > 0) {
                                $stage = array_values($formDataArray['stage']);
                            }
                            if (isset($formDataArray['task_type']) && count(array_values($formDataArray['task_type'])) > 0) {
                                $taskType = array_values($formDataArray['task_type']);
                            }

                            if (isset($formDataArray['sla_deadlines'])) {
                                if ($formDataArray['sla_deadlines'] === 'Select SLA Deadlines . . .') {
                                    $slaDeadlineRange = null;
                                } else {
                                    $slaDeadlineRange = $formDataArray['sla_deadlines'];
                                }
                            }

                            $geoJSON = $geoLocationPlanTodayInstance->getGeoLocationTodayPlannedJO($sacGroup, $technician, $company, $stage, $taskType, $slaDeadlineRange);
                            echo $geoJSON;
                        }
                    }
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
        $geoLocationPlanTodayInstance = new geoLocationTodayPlanController();

        $error = new errorController();
        $errorCode = 400;
        $errorPage = $error->getPathError($errorCode, null);
        http_response_code($errorCode);
        header("Location: $errorPage");
        exit();
    }
}
