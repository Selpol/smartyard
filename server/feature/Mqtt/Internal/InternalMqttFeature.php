<?php declare(strict_types=1);

namespace Selpol\Feature\Mqtt\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Mqtt\MqttFeature;
use Selpol\Service\ClientService;

class InternalMqttFeature extends MqttFeature
{
    public function getConfig(): array
    {
        $config = config('feature.mqtt');

        return [
            'endpoint' => $config['endpoint'],

            'username' => $config['username'],
            'password' => $config['password']
        ];
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function broadcast(string $topic, mixed $payload): mixed
    {
        return container(ClientService::class)->post(
            config('feature.mqtt.agent'),
            json_encode(['topic' => $topic, 'payload' => $payload]),
            ['Content-Type: application/json; charset=utf-8', 'Accept: application/json; charset=utf-8']
        )->getParsedBody();
    }
}