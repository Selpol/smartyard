<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting;

readonly class IntercomClean
{
    public int $unlockTime;

    public int $callTimeout;
    public int $talkTimeout;

    public string $sos;
    public string $concierge;

    public function __construct(int $unlockTime, int $callTimeout, int $talkTimeout, string $sos, string $concierge)
    {
        $this->unlockTime = $unlockTime;

        $this->callTimeout = $callTimeout;
        $this->talkTimeout = $talkTimeout;

        $this->sos = $sos;
        $this->concierge = $concierge;
    }
}