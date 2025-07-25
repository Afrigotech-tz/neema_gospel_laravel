<?php
// Quick diagnostic script for RabbitMQ setup
echo "=== RabbitMQ Setup Diagnostic ===\n\n";

// Check PHP sockets extension
echo "1. Checking PHP sockets extension...\n";
if (extension_loaded('sockets')) {
    echo "✅ PHP sockets extension is loaded\n";
} else {
    echo "❌ PHP sockets extension is NOT loaded\n";
    echo "   Please enable extension=sockets in your php.ini\n";
}

// Check socket functions
echo "\n2. Checking socket functions...\n";
$functions = ['socket_import_stream', 'socket_create', 'socket_connect'];
foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "✅ $function exists\n";
    } else {
        echo "❌ $function does not exist\n";
    }
}

// Check RabbitMQ configuration
echo "\n3. Checking RabbitMQ configuration...\n";
$config = include 'config/rabbitmq.php';
echo "Host: " . $config['host'] . "\n";
echo "Port: " . $config['port'] . "\n";
echo "Keepalive: " . ($config['keepalive'] ? 'enabled' : 'disabled') . "\n";

// Test RabbitMQ connection
echo "\n4. Testing RabbitMQ connection...\n";
try {
    $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
        $config['host'],
        $config['port'],
        $config['username'],
        $config['password'],
        $config['vhost'],
        false,
        'AMQPLAIN',
        null,
        'en_US',
        30,
        30,
        null,
        false, // keepalive disabled for Windows
        20
    );
    echo "✅ RabbitMQ connection successful\n";
    $connection->close();
} catch (\Exception $e) {
    echo "❌ RabbitMQ connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
