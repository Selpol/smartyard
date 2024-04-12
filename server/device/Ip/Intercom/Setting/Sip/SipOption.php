<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Sip;

class SipOption
{
    public int $callTimeout;
    public int $talkTimeout;

    public string $dtmf;

    public bool $echo;

    public function __construct(int $callTimeout, int $talkTimeout, string $dtmf, bool $echo)
    {
        $this->callTimeout = $callTimeout;
        $this->talkTimeout = $talkTimeout;

        $this->dtmf = $dtmf;

        $this->echo = $echo;
    }

    public function equal(SipOption $other): bool
    {
        return $this->callTimeout === $other->callTimeout && $this->talkTimeout === $other->talkTimeout && $this->dtmf === $other->dtmf && $this->echo = $other->echo;
    }
}