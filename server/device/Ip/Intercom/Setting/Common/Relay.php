<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Relay
{
    public bool $lock;

    public int $openDuration;

    public function __construct(bool $lock, int $openDuration)
    {
        $this->lock = $lock;

        $this->openDuration = $openDuration;
    }

    public function equal(Relay $other): bool
    {
        return $this->lock === $other->lock && $this->openDuration === $other->openDuration;
    }
}