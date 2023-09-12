<?php

namespace Selpol\Device\Ip;

use Selpol\Device\Device;
use Selpol\Device\DeviceException;
use Selpol\Http\Uri;
use Throwable;

abstract class IpDevice extends Device
{
    public string $login;
    public string $password;

    public function __construct(Uri $uri, string $password)
    {
        parent::__construct($uri->withUserInfo($this->login, $password));

        $this->password = $password;
    }

    public function ping(): bool
    {
        $url = $this->uri->getHost();

        if ($this->uri->getPort() === null) {
            $url .= ':' . match (strtolower($this->uri->getScheme())) {
                    'http' => 80,
                    'https' => 443,
                    default => 22,
                };
        } else $url .= ':' . $this->uri->getPort();

        $fp = @stream_socket_client($url, timeout: 1);

        if ($fp) {
            fclose($fp);

            if (array_key_exists('DeviceID', $this->getSysInfo()))
                return true;

            return false;
        }

        return false;
    }

    public function getSysInfo(): array
    {
        throw new DeviceException($this);
    }

    public function setNtp(string $server, int $port, string $timezone = 'Europe/Moscow'): static
    {
        throw new DeviceException($this);
    }

    public function setAdminPassword(string $password): static
    {
        throw new DeviceException($this);
    }

    protected function get(string $endpoint, array $headers = ['Content-Type' => 'application/json']): mixed
    {
        try {
            $response = $this->client()->get($this->uri . $endpoint, $headers);

            return $response->getParsedBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    protected function post(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json']): mixed
    {
        try {
            $response = $this->client()->post($this->uri . $endpoint, $body ? json_encode($body) : null, $headers);

            return $response->getParsedBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    protected function put(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json']): mixed
    {
        try {
            $response = $this->client()->put($this->uri . $endpoint, $body ? json_encode($body) : null, $headers);

            return $response->getParsedBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    protected function delete(string $endpoint, array $headers = ['Content-Type' => 'application/json']): mixed
    {
        try {
            $response = $this->client()->delete($this->uri . $endpoint, $headers);

            return $response->getParsedBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }
}