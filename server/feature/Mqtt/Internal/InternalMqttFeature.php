<?php declare(strict_types=1);

namespace Selpol\Feature\Mqtt\Internal;

use RedisException;
use Selpol\Feature\Mqtt\MqttFeature;
use Selpol\Service\RedisService;
use SensitiveParameter;

class InternalMqttFeature extends MqttFeature
{
    /**
     * @throws RedisException
     */
    public function checkUser(string $username, #[SensitiveParameter] string $password, string $clientId): bool
    {
        return $password === container(RedisService::class)->getConnection()->get('user:' . intval(substr($username, 1)) . ':ws');
    }

    public function checkAdmin(string $username): bool
    {
        return intval(substr($username, 1)) === 0;
    }

    public function checkAcl(string $username, string $clientId, string $topic, int $acc): bool
    {
        return true;
    }
}