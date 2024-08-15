<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Sip;

class SipOption
{
    public function __construct(public int $callTimeout, public int $talkTimeout, public array $dtmf, public bool $echo)
    {
    }

    public function equal(SipOption $other): bool
    {
        return $this->callTimeout === $other->callTimeout && $this->talkTimeout === $other->talkTimeout && $this->dtmf === $other->dtmf && $this->echo === $other->echo;
    }
}