<?php declare(strict_types=1);

namespace Selpol\Feature\Mqtt\Internal;

use Selpol\Feature\Mqtt\MqttFeature;
use Selpol\Service\RedisService;
use SensitiveParameter;

readonly class InternalMqttFeature extends MqttFeature
{
    public function checkUser(string $username, #[SensitiveParameter] string $password, string $clientId): bool
    {
        if ($username === config_get('mqtt.username'))
            return $password === config_get('mqtt.password');

        return $password === container(RedisService::class)->get('user:' . intval(substr($username, 1)) . ':ws');
    }

    public function checkAdmin(string $username): bool
    {
        return $username === config_get('mqtt.username');
    }

    public function checkAcl(string $username, string $clientId, string $topic, int $acc): bool
    {
        return true;
    }
}