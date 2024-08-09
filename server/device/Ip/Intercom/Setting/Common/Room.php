<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Room
{
    public string|int $concierge;
    public string|int $sos;

    public function __construct(string|int $concierge, string|int $sos)
    {
        $this->concierge = $concierge;
        $this->sos = $sos;
    }

    public function equal(Room $other): bool
    {
        return $this->concierge === $other->concierge && $this->sos === $other->sos;
    }
}