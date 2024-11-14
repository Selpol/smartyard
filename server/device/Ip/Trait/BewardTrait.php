<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Trait;

use Selpol\Device\Exception\DeviceException;
use SensitiveParameter;
use Throwable;

trait BewardTrait
{
    private array $intercomCgi;

    public function getIntercomCgi(): array
    {
        if (!isset($this->intercomCgi)) {
            $this->intercomCgi = $this->get('/cgi-bin/intercom_cgi', ['action' => 'get'], parse: ['type' => 'param']);
        }

        return $this->intercomCgi;
    }

    public function getSysInfo(): array
    {
        try {
            return $this->get('/cgi-bin/systeminfo_cgi', ['action' => 'get'], parse: ['type' => 'param']);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить информацию об устройстве', $throwable->getMessage(), previous: $throwable);
        }
    }

    public function setLoginPassword(#[SensitiveParameter] string $password): static
    {
        $this->get('/cgi-bin/pwdgrp_cgi', ['action' => 'update', 'username' => $this->login, 'password' => $password, 'blockdoors' => 1]);

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
}