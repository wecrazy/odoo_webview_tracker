<?php

require 'vendor/autoload.php'; // Load Composer's autoloader

use Predis\Client;

// Connect to Redis
$client = new Client();

// Test connection
if ($client->ping()) {
    echo "Connected to Redis\n";
} else {
    echo "Failed to connect to Redis\n";
}

// Set and get a value
$client->set('foo', 'bar');
echo $client->get('foo'); // Outputs: bar
