<?php declare(strict_types=1);

namespace Selpol\Service;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\ConfigurationInvalidException;
use PhpMqtt\Client\Exceptions\ConnectingToBrokerFailedException;
use PhpMqtt\Client\Exceptions\DataTransferException;
use PhpMqtt\Client\Exceptions\ProtocolNotSupportedException;
use PhpMqtt\Client\Exceptions\RepositoryException;
use PhpMqtt\Client\MqttClient;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Container\ContainerDisposeInterface;
use Throwable;

#[Singleton]
class MqttService implements ContainerDisposeInterface
{
    private MqttClient $client;

    /**
     * @throws ProtocolNotSupportedException
     */
    public function __construct()
    {
        $config = config_get('mqtt');

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

    public function task(string $title, string $action): void
    {
        $this->publish('task', ['title' => $title, 'action' => $action]);
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
            $config = config_get('mqtt');

            $this->client->connect((new ConnectionSettings())->setUsername($config['username'])->setPassword($config['password']));
        }
    }
}