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

use Symfony\Component\Yaml\Yaml;

class odoo_api {
    private $log;

    private $config;

    public function __construct() {
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

        $configPaths = [
            realpath(__DIR__ . '/../config/conf.yaml'),
            realpath(__DIR__ . '/../../config/conf.yaml'),
        ];

        $configFile = null;
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $configFile = $path;
                break;
            }
        }

        if ($configFile === null) {
            throw new \Exception("Configuration file conf.yaml not found in any of the specified locations.");
        }

        $this->config = Yaml::parseFile($configFile);
    }

    private function post_req($data_req, $headers_req, $url_req, $cookies) {
        $maxRetries = $this->config['ODOO_API']['MAX_RETRY'];
        $retryDelay = $this->config['ODOO_API']['RETRY_DELAY'];
        $attempts = 0;
        $success = false;
        $response = null;

        while ($attempts < $maxRetries && !$success) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_req);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_req);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_req));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $cookiesPath = 'cookies.txt';
            if (!file_exists($cookiesPath)) {
                touch($cookiesPath);
            }

            if ($cookies == 1) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiesPath);
            }
            if ($cookies == 2) {
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiesPath);
            }
            
            $coba = file_get_contents(filename: $cookiesPath);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        
            $response = curl_exec($ch);
        
            if (curl_errno($ch)) {
                $error_message = "cURL Error: " . curl_error($ch);
                $this->log->createLogMessage($error_message);
                error_log($error_message, 0);
                $attempts++;
                if ($attempts < $maxRetries) {
                    sleep($retryDelay);
                }
            } else {
                $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header      = substr($response, 0, $header_size);
                $success = true;
            }
            
            curl_close($ch);
        }

        if (!$success) {
            // throw new Exception("Failed to post to $url_req after $maxRetries attempts.");
            $this->log->createLogMessage("Failed to post to $url_req after $maxRetries attempts.");
        }

        return $response;
    }
    private function ODOOResponseStatus($response, $param) {
        if (!empty($response)) {
            if (!empty($response["result"])) {
                if (isset($response['result']['message'])) {
                    if ($response['result']['message'] == '') {
                        $this->log->createLogMessage("Something went wrong! Your data cannot be get");
                        return false;
                    }
                    else {
                        $message = json_encode($response['result'], JSON_PRETTY_PRINT);
                        $this->log->createLogMessage($message);
                        return false;
                    }
                }
                else {
                    return true;
                }
            }
            else {
                $this->log->createLogMessage("No data found! Try to fix your request!, from param: \n" . json_encode($param, JSON_PRETTY_PRINT));
                return false;
            }
        } else {
            $this->log->createLogMessage("Got empty response from ODOO!");
            return false;
        }
    }

    // private function ODOOUpdateDataStatus($response) {
    //     if (!empty($response)) {
    //         if (!empty($response["result"])) {
    //             if (isset($response["result"]["message"])) {
    //                 if (($response["result"]["message"] == "Success") && 
    //                     ($response["result"]["status"] == 200) && 
    //                     ($response["result"]["success"] == 1 || $response["result"]["success"] === true) && 
    //                     ($response["result"]["response"] == 1 || $response["result"]["response"] === true)) 
    //                 {
    //                     return true;
    //                 } else {
    //                     $this->log->createLogMessage("Sorry, got bad response : /n" . json_encode($response, JSON_PRETTY_PRINT));
    //                     return false;        
    //                 }
    //             } else {
    //                 $this->log->createLogMessage("Oops! No Message found of the result response : /n" . json_encode($response, JSON_PRETTY_PRINT));
    //                 return false;    
    //             }
    //         } else {
    //             $this->log->createLogMessage("Something went wrong! The result response is not get!!");
    //             return false;    
    //         }
    //     } else {
    //         $this->log->createLogMessage("Got empty response while update data to ODOO");
    //         return false;
    //     }
    // }

    public function OdooAPI($request, $parameters) {
        $url_session    = $this->config['ODOO_API']['URL_SESSION'];
        $url_getData    = $this->config['ODOO_API']['URL_GETDATA'];
    
        $login = array(
            'db' 		=> $this->config['ODOO_API']['DB'],
            'login'		=> $this->config['ODOO_API']['LOGIN'],
            'password' 	=> $this->config['ODOO_API']['PASSWORD']
        );
    
        $data_login = array(
            'jsonrpc' => $this->config['ODOO_API']['JSONRPC'],
            'params'  => $login
        );
    
        $headers_login = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data_login)),
        );

        $headers_params = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($parameters)),
        );
    
        $response1 = $this->post_req($data_login,$headers_login,$url_session,1);
        // return $response1;
    
        $response2 = null;

        if (isset($request)) {
            switch ($request) {
                case 'GetData':
                    $response2 = $this->post_req($parameters, $headers_params, $url_getData, 2);
                    $json_res = json_decode($response2, true);
                    if ($this->ODOOResponseStatus($json_res, $parameters)) {
                        return $json_res["result"];
                    }
                    break;

                // case 'UpdateData':
                //     $response2 = $this->post_req($parameters, $headers_params, $url_updateData, 2);
                //     $json_res = json_decode($response2, true);
                //     if ($this->ODOOUpdateDataStatus($json_res, $parameters)) {
                //         // return $json_res["result"];
                //         return true;
                //     }
                //     break;
    
                // case 'CreateData':
                //     $response2 = post_req($parameters, $headers_params, $url_createData, 2);
                //     break;
    
            } 
        }
    } 

}