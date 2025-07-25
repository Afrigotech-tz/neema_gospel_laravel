<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Illuminate\Support\Facades\Log;

// Define missing socket constants for Windows compatibility
if (!defined('SOCKET_EAGAIN')) {
    define('SOCKET_EAGAIN', 11);
}
if (!defined('SOCKET_EWOULDBLOCK')) {
    define('SOCKET_EWOULDBLOCK', 11);
}
if (!defined('SOCKET_EINTR')) {
    define('SOCKET_EINTR', 4);
}

class RabbitMQService
{
    protected $connection;
    protected $channel;

    public function __construct()
    {
        $this->connect();
    }

    protected function connect()
    {
        try {
            // Check if sockets extension is available
            if (!extension_loaded('sockets')) {
                Log::warning('PHP sockets extension is not loaded. Some features may be limited.');
            }

            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.username'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost'),
                false, // insist
                'AMQPLAIN', // login method
                null, // login response
                'en_US', // locale
                config('rabbitmq.connection_timeout', 30),
                config('rabbitmq.read_write_timeout', 30),
                null, // context
                config('rabbitmq.keepalive', false), // Disabled for Windows compatibility
                config('rabbitmq.heartbeat', 20)
            );
            $this->channel = $this->connection->channel();
        } catch (\Exception $e) {
            Log::error('RabbitMQ connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function publish($exchange, $routingKey, $message, $persistent = true)
    {
        try {
            $msg = new AMQPMessage(json_encode($message), [
                'delivery_mode' => $persistent ? AMQPMessage::DELIVERY_MODE_PERSISTENT : AMQPMessage::DELIVERY_MODE_NON_PERSISTENT,
                'content_type' => 'application/json',
            ]);

            $this->channel->exchange_declare(
                $exchange,
                'topic',
                false,
                true,
                false
            );

            $this->channel->basic_publish($msg, $exchange, $routingKey);

            Log::info('Message published to RabbitMQ', [
                'exchange' => $exchange,
                'routing_key' => $routingKey,
                'message' => $message
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to publish message to RabbitMQ', [
                'exchange' => $exchange,
                'routing_key' => $routingKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function consume($queue, $callback)
    {
        try {
            $this->channel->queue_declare(
                $queue,
                false,
                true,
                false,
                false
            );

            $this->channel->basic_qos(null, 1, null);
            // Wrap the callback to pass channel and message for ack/nack
            $wrappedCallback = function ($message) use ($callback) {
                $channel = $this->channel;
                $callback($channel, $message);
            };

            $this->channel->basic_consume($queue, '', false, false, false, false, $wrappedCallback);

            Log::info("Waiting for messages on queue: {$queue}");

            while (count($this->channel->callbacks)) {
                try {
                    $this->channel->wait(null, false, config('rabbitmq.consumer_timeout', 60));
                } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                    Log::info('Consumer timeout, checking connection...');
                    if (!$this->connection || !$this->connection->isConnected()) {
                        Log::warning('Connection lost, attempting to reconnect...');
                        $this->reconnect();
                        return $this->consume($queue, $callback);
                    }
                    continue;
                } catch (\PhpAmqpLib\Exception\AMQPConnectionClosedException $e) {
                    Log::warning('Connection closed, attempting to reconnect...');
                    $this->reconnect();
                    return $this->consume($queue, $callback);
                } catch (\Exception $e) {
                    Log::error('Error in RabbitMQ consumer: ' . $e->getMessage());
                    throw $e;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in RabbitMQ consumer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function reconnect()
    {
        try {
            $this->close();
            sleep(5); // Wait before reconnecting
            $this->connect();
            Log::info('RabbitMQ reconnected successfully');
        } catch (\Exception $e) {
            Log::error('Failed to reconnect to RabbitMQ: ' . $e->getMessage());
            throw $e;
        }
    }

    public function isConnected()
    {
        return $this->connection && $this->connection->isConnected();
    }

    public function setupExchangesAndQueues()
    {
        try {
            // Declare exchanges
            foreach (config('rabbitmq.exchanges') as $exchange) {
                $this->channel->exchange_declare(
                    $exchange['name'],
                    $exchange['type'],
                    false,
                    $exchange['durable'],
                    $exchange['auto_delete']
                );
            }

            // Declare queues
            foreach (config('rabbitmq.queues') as $queue) {
                $this->channel->queue_declare(
                    $queue['name'],
                    false,
                    $queue['durable'],
                    false,
                    $queue['auto_delete']
                );
            }

            // Bind queues to exchanges
            foreach (config('rabbitmq.bindings') as $queueName => $binding) {
                $this->channel->queue_bind(
                    config('rabbitmq.queues.' . $queueName . '.name'),
                    $binding['exchange'],
                    $binding['routing_key']
                );
            }

            Log::info('RabbitMQ exchanges and queues setup completed');
        } catch (\Exception $e) {
            Log::error('Failed to setup RabbitMQ exchanges and queues: ' . $e->getMessage());
            throw $e;
        }
    }

    public function close()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function __destruct()
    {
        $this->close();
    }


}
