<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Sip;

use SensitiveParameter;

class Sip
{
    public function __construct(public string $login, #[SensitiveParameter]public string $password, public string $server, public int $port)
    {
    }

    public function equal(Sip $other): bool
    {
        return $this->login === $other->login && $this->password === $other->password && $this->server === $other->server && $this->port === $other->port;
    }
}