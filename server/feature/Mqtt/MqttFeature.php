<?php declare(strict_types=1);

namespace Selpol\Feature\Mqtt;

use Selpol\Feature\Feature;
use Selpol\Feature\Mqtt\Internal\InternalMqttFeature;
use Selpol\Framework\Container\Attribute\Singleton;
use SensitiveParameter;

#[Singleton(InternalMqttFeature::class)]
readonly abstract class MqttFeature extends Feature
{
    public const ACL_NONE = 0;

    public const ACL_READ = 1 << 0;
    public const ACL_WRITE = 1 << 1;
    public const ACL_SUBSCRIBE = 1 << 2;

    public abstract function checkUser(string $username, #[SensitiveParameter] string $password, string $clientId): bool;

    public abstract function checkAdmin(string $username): bool;

    public abstract function checkAcl(string $username, string $clientId, string $topic, int $acc): bool;
}