<?php

declare(strict_types=1);

namespace Selpol\Feature\Intercom;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Feature;
use Selpol\Feature\Intercom\Internal\InternalIntercomFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalIntercomFeature::class)]
readonly abstract class IntercomFeature extends Feature
{
    public abstract function updatePassword(DeviceIntercom $interom, ?string $password): void;
}
