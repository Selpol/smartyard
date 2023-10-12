<?php

namespace Selpol\Feature\Sip;

use Selpol\Feature\Feature;
use Selpol\Feature\Sip\Internal\InternalSipFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalSipFeature::class)]
abstract class SipFeature extends Feature
{
    abstract public function server(string $by, string|int|null $query = null): array;

    abstract public function stun(string|int $extension): bool|string;
}