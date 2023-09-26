<?php declare(strict_types=1);

namespace Selpol\Device\Ip;

use Selpol\Device\Device;
use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Http\Uri;
use Throwable;

abstract class IpDevice extends Device
{
    public string $login = 'root';
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

    public function asCamera(): ?CameraDevice
    {
        if ($this instanceof CameraDevice)
            return $this;

        return null;
    }

    public function asIntercom(): ?IntercomDevice
    {
        if ($this instanceof IntercomDevice)
            return $this;

        return null;
    }

    public function get(string $endpoint, array $headers = ['Content-Type' => 'application/json']): mixed
    {
        try {
            $response = $this->client()->get($this->uri . $endpoint, $headers + ['Authorization' => 'Basic ' . base64_encode($this->login . ':' . $this->password)]);

            return $response->getParsedBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function post(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json']): mixed
    {
        try {
            $response = $this->client()->post($this->uri . $endpoint, $body ? json_encode($body) : null, $headers + ['Authorization' => 'Basic ' . base64_encode($this->login . ':' . $this->password)]);

            return $response->getParsedBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function put(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json']): mixed
    {
        try {
            $response = $this->client()->put($this->uri . $endpoint, $body ? json_encode($body) : null, $headers + ['Authorization' => 'Basic ' . base64_encode($this->login . ':' . $this->password)]);

            return $response->getParsedBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function delete(string $endpoint, array $headers = ['Content-Type' => 'application/json']): mixed
    {
        try {
            $response = $this->client()->delete($this->uri . $endpoint, $headers + ['Authorization' => 'Basic ' . base64_encode($this->login . ':' . $this->password)]);

            return $response->getParsedBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }
}