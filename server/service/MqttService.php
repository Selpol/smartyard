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
use Selpol\Runner\TaskRunner;
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

    public function task(string $uuid, string $title, string $action, ?int $uid, mixed $data): void
    {
        $this->publish($uid !== null ? ('task:' . $uid) : 'task', ['uuid' => $uuid, 'title' => $title, 'action' => $action, 'data' => $data]);
    }

    public function table(string $uuid, string $title, array $headers, array $values, ?int $uid): void
    {
        $this->publish($uid !== null ? ('table:' . $uid) : $uid, ['uuid' => $uuid, 'title' => $title, 'headers' => $headers, 'values' => $values]);
    }

    /**
     * @throws DataTransferException
     */
    public function dispose(): void
    {
        if ($this->client->isConnected()) {
            $this->client->disconnect();
        }
    }

    /**
     * @throws ConfigurationInvalidException
     * @throws ConnectingToBrokerFailedException
     */
    private function connect(): void
    {
        if (!$this->client->isConnected()) {
            $config = config('mqtt');

            $keepAliveInternal = kernel()->getRunner() instanceof TaskRunner ? 3600 : 10;

            $this->client->connect((new ConnectionSettings())->setUsername($config['username'])->setPassword($config['password'])->setKeepAliveInterval($keepAliveInternal)->setReconnectAutomatically(true));
        }
    }
}