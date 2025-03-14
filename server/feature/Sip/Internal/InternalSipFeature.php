<?php declare(strict_types=1);

namespace Selpol\Feature\Sip\Internal;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Feature\Sip\SipFeature;

readonly class InternalSipFeature extends SipFeature
{
    public function sip(DeviceIntercom $intercom): ?SipServer
    {
        return SipServer::fetch(criteria()->equal('internal_ip', $intercom->server));
    }

    public function stun(): bool|string
    {
        $stuns = config_get('feature.sip.stuns');

        if ($stuns) {
            return $stuns[rand(0, count($stuns) - 1)];
        }

        return false;
    }
}
