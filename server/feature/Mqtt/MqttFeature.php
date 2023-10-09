<?php declare(strict_types=1);

namespace Selpol\Feature\Mqtt;

use Selpol\Feature\Feature;

abstract class MqttFeature extends Feature
{
    public abstract function getConfig(): array;

    public abstract function broadcast(string $topic, mixed $payload): mixed;
}