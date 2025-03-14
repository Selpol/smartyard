<?php declare(strict_types=1);

namespace Selpol\Feature\Sip;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Feature\Feature;
use Selpol\Feature\Sip\Internal\InternalSipFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalSipFeature::class)]
readonly abstract class SipFeature extends Feature
{
    public abstract function sip(DeviceIntercom $intercom): ?SipServer;

    public abstract function stun(): bool|string;
}
