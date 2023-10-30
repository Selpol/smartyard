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
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function setLoginPassword(#[SensitiveParameter] string $password): static
    {
        $this->get('/cgi-bin/pwdgrp_cgi', ['action' => 'update', 'username' => $this->login, 'password' => $password, 'blockdoors' => 1]);

        return $this;
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