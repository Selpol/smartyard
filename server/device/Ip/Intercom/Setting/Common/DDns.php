<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Common;

class DDns
{
    public function __construct(public bool $enable, public string $server, public int $port)
    {
    }

    public function equal(DDns $other): bool
    {
        return $this->enable === $other->enable && $this->server === $other->server && $this->port === $other->port;
    }
}