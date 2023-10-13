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

    /**
     * @param string $topic
     * @param mixed $data
     * @throws ConfigurationInvalidException
     * @throws ConnectingToBrokerFailedException
     * @throws DataTransferException
     * @throws RepositoryException
     */
    public function publish(string $topic, mixed $data): void
    {
        $this->connect();

        $this->client->publish($topic, json_encode($data));
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