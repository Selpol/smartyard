<?php

namespace Selpol\Feature\Sip\Internal;

use Selpol\Feature\Sip\SipFeature;

class InternalSipFeature extends SipFeature
{
    public function server(string $by, string|int|null $query = null): array
    {
        $servers = config('feature.sip.servers');

        return match ($by) {
            "all" => $servers,
            default => $servers[0]
        };
    }

    public function stun(string|int $extension): bool|string
    {
        $stuns = config('feature.sip.stuns');

        if ($stuns)
            return $stuns[rand(0, count($stuns) - 1)];

        return false;
    }
}