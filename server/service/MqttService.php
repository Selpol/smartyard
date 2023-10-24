<?php declare(strict_types=1);

namespace Selpol\Service;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\ConfigurationInvalidException;
use PhpMqtt\Client\Exceptions\ConnectingToBrokerFailedException;
use PhpMqtt\Client\Exceptions\DataTransferException;
use PhpMqtt\Client\Exceptions\ProtocolNotSupportedException;
use PhpMqtt\Client\MqttClient;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Container\ContainerDisposeInterface;
use Throwable;

#[Singleton]
readonly class MqttService implements ContainerDisposeInterface
{
    private MqttClient $client;

    /**
     * @throws ProtocolNotSupportedException
     */
    public function __construct()
    {
        $config = config('mqtt');

        $this->client = new MqttClient($config['host'], intval($config['port']));
    }

    public function publish(string $topic, mixed $data): void
    {
        try {
            $this->connect();

            $this->client->publish($topic, json_encode($data));
        } catch (Throwable $throwable) {
            file_logger('mqtt')->error($throwable);
        }
    }

    public function task(string $uuid, string $title, string $action, mixed $data): void
    {
        $this->publish('task', ['uuid' => $uuid, 'title' => $title, 'action' => $action, 'data' => $data]);
    }

    /**
     * @throws DataTransferException
     */
    public function dispose(): void
    {
        if ($this->client->isConnected())
            $this->client->disconnect();
    }

    /**
     * @throws ConfigurationInvalidException
     * @throws ConnectingToBrokerFailedException
     */
    private function connect(): void
    {
        if (!$this->client->isConnected()) {
            $config = config('mqtt');

            $this->client->connect((new ConnectionSettings())->setUsername($config['username'])->setPassword($config['password']));
        }
    }
}