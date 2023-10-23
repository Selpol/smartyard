<?php

namespace Selpol\Feature\Sip;

use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Feature\Feature;
use Selpol\Feature\Sip\Internal\InternalSipFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalSipFeature::class)]
readonly abstract class SipFeature extends Feature
{
    /**
     * @param string $by
     * @param string|int|null $query
     * @return SipServer[]
     */
    abstract public function server(string $by, string|int|null $query = null): array;

    abstract public function stun(string|int $extension): bool|string;
}