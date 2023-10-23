<?php

namespace Selpol\Feature\Sip\Internal;

use Selpol\Entity\Repository\Sip\SipServerRepository;
use Selpol\Feature\Sip\SipFeature;

class InternalSipFeature extends SipFeature
{
    public function server(string $by, string|int|null $query = null): array
    {
        return match ($by) {
            "all" => container(SipServerRepository::class)->fetchAll(),
            default => [container(SipServerRepository::class)->fetch()]
        };
    }

    public function stun(string|int $extension): bool|string
    {
        $stuns = config_get('feature.sip.stuns');

        if ($stuns)
            return $stuns[rand(0, count($stuns) - 1)];

        return false;
    }
}