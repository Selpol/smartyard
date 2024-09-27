<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Relay
{
    public function __construct(public bool $lock, public int $openDuration)
    {
    }

    public function equal(Relay $other): bool
    {
        return $this->lock === $other->lock && $this->openDuration === $other->openDuration;
    }
}