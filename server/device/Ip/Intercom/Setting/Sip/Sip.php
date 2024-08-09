<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Sip;

use SensitiveParameter;

class Sip
{
    public string $login;
    public string $password;

    public string $server;
    public int $port;

    public function __construct(string $login, #[SensitiveParameter] string $password, string $server, int $port)
    {
        $this->login = $login;
        $this->password = $password;

        $this->server = $server;
        $this->port = $port;
    }

    public function equal(Sip $other): bool
    {
        return $this->login === $other->login && $this->password === $other->password && $this->server === $other->server && $this->port === $other->port;
    }
}