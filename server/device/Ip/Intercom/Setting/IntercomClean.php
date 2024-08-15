<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting;

readonly class IntercomClean
{
    public function __construct(public int $unlockTime, public int $callTimeout, public int $talkTimeout, public string $sos, public string $concierge)
    {
    }
}