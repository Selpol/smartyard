<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Room
{
    public function __construct(public string $concierge, public string|int $sos)
    {
    }

    public function equal(Room $other): bool
    {
        return $this->concierge === $other->concierge && $this->sos === $other->sos;
    }
}