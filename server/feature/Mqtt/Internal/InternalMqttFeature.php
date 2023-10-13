<?php declare(strict_types=1);

namespace Selpol\Feature\Mqtt\Internal;

use Selpol\Feature\Mqtt\MqttFeature;
use SensitiveParameter;

class InternalMqttFeature extends MqttFeature
{
    public function checkUser(string $username, #[SensitiveParameter] string $password, string $clientId): bool
    {
        return false;
    }

    public function checkAdmin(string $username): bool
    {
        return false;
    }

    public function checkAcl(string $username, string $clientId, string $topic, int $acc): bool
    {
        return false;
    }
}