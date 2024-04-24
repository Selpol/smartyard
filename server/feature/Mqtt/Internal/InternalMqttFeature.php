<?php declare(strict_types=1);

namespace Selpol\Feature\Mqtt\Internal;

use Selpol\Feature\Mqtt\MqttFeature;
use Selpol\Service\AuthService;
use Selpol\Service\RedisService;
use SensitiveParameter;

readonly class InternalMqttFeature extends MqttFeature
{
    public function checkUser(string $username, #[SensitiveParameter] string $password, string $clientId): bool
    {
        if ($username === config_get('mqtt.username'))
            return $password === config_get('mqtt.password');

        $success = $password === container(RedisService::class)->get('user:' . $clientId . ':ws');

        return $success && container(AuthService::class)->checkUserScope(intval($clientId), 'mqtt-access');
    }

    public function checkAdmin(string $username): bool
    {
        return $username === config_get('mqtt.username');
    }

    public function checkAcl(string $username, string $clientId, string $topic, int $acc): bool
    {
        if ($username === config_get('mqtt.username'))
            return true;

        if (array_key_exists($topic, self::TOPICS))
            return (self::TOPICS[$topic] & $acc) === $acc;

        if ($topic === 'task:' . $clientId)
            return (self::TOPICS['task'] & $acc) === $acc;

        return false;
    }
}