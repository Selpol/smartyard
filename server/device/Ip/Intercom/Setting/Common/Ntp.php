<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Ntp
{
    public function __construct(public string $server, public int $port, public string $timezone)
    {
    }

    public function equal(Ntp $other): bool
    {
        return $this->server === $other->server && $this->port === $other->port && $this->timezone === $other->timezone;
    }
}