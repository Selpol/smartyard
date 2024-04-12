<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class Stun
{
    public string $server;
    public int $port;

    public function __construct(string $server, int $port)
    {
        $this->server = $server;
        $this->port = $port;
    }

    public function equal(Stun $other): bool
    {
        return $this->server === $other->server && $this->port === $other->port;
    }
}