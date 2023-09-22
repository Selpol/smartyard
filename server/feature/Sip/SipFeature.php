<?php

namespace Selpol\Feature\Sip;

use Selpol\Feature\Feature;

abstract class SipFeature extends Feature
{
    abstract public function server(string $by, string|int|null $query = null): array;

    abstract public function stun(string|int $extension): bool|string;
}