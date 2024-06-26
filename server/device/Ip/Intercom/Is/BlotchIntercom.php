<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Trait\BlotchTrait;

class BlotchIntercom extends IntercomDevice
{
    use BlotchTrait;

    public function addRfid(string $code, int $apartment): void
    {
        $this->post('/key_add', http_build_query(['KEY' => $code, 'FLAT' => $apartment]), ['Content-Type' => 'application/x-www-form-urlencoded'], false);
    }

    public function removeRfid(string $code, int $apartment): void
    {
        $this->post('/key_del', http_build_query(['KEY' => $code, 'FLAT' => $apartment]), ['Content-Type' => 'application/x-www-form-urlencoded'], false);
    }

    public function setSip(string $login, string $password, string $server, int $port): static
    {
        $this->post('/fields_update', http_build_query([
            'DELAYS.SIP_CALL_DELAY' => 5000,
            'DELAYS.SIP_RING_DELAY' => 30000,
            'DELAYS.SIP_TALK_DELAY' => 120000,
            'DOMAIN' => $server,
            'PORT' => $port,
            'NAME' => $login,
            'PASSWORD' => $password
        ]), ['Content-Type' => 'application/x-www-form-urlencoded'], false);

        return $this;
    }

    public function open(int $value): void
    {
        parent::open($value); // TODO: Change the autogenerated stub
    }

    public function call(int $apartment): void
    {
        $this->get('/test_call/' . $apartment);
    }
}