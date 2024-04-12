<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Room
{
    public int $concierge;
    public int $sos;

    public function __construct(int $concierge, int $sos)
    {
        $this->concierge = $concierge;
        $this->sos = $sos;
    }

    public function equal(Room $other): bool
    {
        return $this->concierge === $other->concierge && $this->sos === $other->sos;
    }
}