<?php declare(strict_types=1);

namespace Selpol\Feature\Sip\Internal;

use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Feature\Sip\SipFeature;

readonly class InternalSipFeature extends SipFeature
{
    public function server(string $by, string|int|null $query = null): array
    {
        return match ($by) {
            "all" => SipServer::fetchAll(),
            default => [SipServer::fetch()]
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