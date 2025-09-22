<?php

declare(strict_types=1);

namespace Selpol\Feature\Intercom;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Feature;
use Selpol\Feature\Intercom\Internal\InternalIntercomFeature;
use Selpol\Framework\Container\Attribute\Singleton;
use SensitiveParameter;

#[Singleton(InternalIntercomFeature::class)]
readonly abstract class IntercomFeature extends Feature
{
    public abstract function updatePassword(DeviceIntercom $interom, #[SensitiveParameter] ?string $password): void;

    /**
     * @return IntercomApproved[]
     */
    public abstract function getApproveds(): array;

    public abstract function approved(string $ip, string $title, string $name, ?string $model, #[SensitiveParameter] ?string $password, string $server, ?int $dvrServerId, ?int $frsServerId, ?int $addressHouseId, float $lat, float $lon): void;

    public abstract function addApproved(IntercomApproved $approved): void;

    public abstract function removeApproved(string $ip): void;
}
