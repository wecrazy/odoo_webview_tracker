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

class aes256cbc {
    private $config;
    private $iv;
    private $key;
    
    public function __construct() {
        $configPaths = [
            realpath(__DIR__ . '\..\config\conf.yaml'),
            realpath(__DIR__ . '\..\..\config\conf.yaml'),
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
        $this->key = $this->config['AES-256-CBC']['KEY'];
        $this->iv = $this->config['AES-256-CBC']['IV'];
    }
    
    public function encryptAES256CBC($data) {
        $key = substr(hash('sha256', $this->key, true), 0, 32);
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $this->iv);
        return base64_encode($encryptedData);
    }

    public function decryptAES256CBC($encryptData) {
        $key = substr(hash('sha256', $this->key, true), 0, 32);
        $decryptedData = openssl_decrypt(base64_decode($encryptData), 'aes-256-cbc', $key, 0, $this->iv);
        return $decryptedData;
    }
}