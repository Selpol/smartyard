<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Syslog
{
    public function __construct(public string $server, public int $port)
    {
    }

    public function equal(Syslog $other): bool
    {
        return $this->server === $other->server && $this->port === $other->port;
    }
}