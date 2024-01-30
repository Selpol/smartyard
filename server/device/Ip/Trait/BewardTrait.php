<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Trait;

use Selpol\Device\Exception\DeviceException;
use SensitiveParameter;
use Throwable;

trait BewardTrait
{
    public function getSysInfo(): array
    {
        try {
            $response = $this->get('/cgi-bin/systeminfo_cgi', ['action' => 'get'], parse: false);

            return $this->parseParamValueHelp($response);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить информацию об устройстве', $throwable->getMessage(), previous: $throwable);
        }
    }

    public function setLoginPassword(#[SensitiveParameter] string $password): static
    {
        $this->get('/cgi-bin/pwdgrp_cgi', ['action' => 'update', 'username' => $this->login, 'password' => $password, 'blockdoors' => 1]);

        return $this;
    }

    public function setDDns(bool $value, array $options = []): static
    {
        if (!$value)
            $this->get('/webs/netDDNSCfgEx', ['provider' => 0]);

        return $this;
    }

    public function setUPnP(bool $value): static
    {
        $this->get('/webs/netUPNPCfgEx', ['cksearch' => $value ? 1 : 0]);

        return $this;
    }

    public function call(int $apartment): void
    {
        $this->get('/cgi-bin/diag_cgi', ['action' => 'call', 'Apartment' => $apartment]);
    }

    public function callStop(): void
    {
        $this->get('/cgi-bin/diag_cgi', ['action' => 'cancel']);
    }

    public function reboot(): void
    {
        $this->get('/webs/btnHitEx', ['flag' => 21]);
    }

    public function reset(): void
    {
        $this->get('/cgi-bin/factorydefault_cgi');
    }

    protected function parseParamValueHelp(string $response): array
    {
        $return = [];

        $result = explode(PHP_EOL, $response);

        foreach ($result as $item) {
            $value = explode('=', trim($item));

            $return[trim($value[0])] = array_key_exists(1, $value) ? trim($value[1]) : true;
        }

        return $return;
    }
}