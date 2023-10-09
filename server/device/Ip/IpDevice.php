<?php declare(strict_types=1);

namespace Selpol\Device\Ip;

use Selpol\Device\Device;
use Selpol\Device\Exception\DeviceException;
use Selpol\Http\Response;
use Selpol\Http\Uri;
use Throwable;

abstract class IpDevice extends Device
{
    public string $login = 'root';
    public string $password;

    protected array $requestOptions;

    public function __construct(Uri $uri, string $password)
    {
        parent::__construct($uri->withUserInfo($this->login, $password));

        $this->password = $password;

        $this->requestOptions = ['basic' => $this->login . ':' . $this->password];
    }

    public function ping(): bool
    {
        $url = $this->uri->getHost();

        if ($this->uri->getPort() === null) {
            $url .= ':' . match (strtolower($this->uri->getScheme())) {
                    'http' => 80,
                    'https' => 443,
                    default => 22
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
        return $this;
    }

    public function get(string $endpoint, array $query = [], array $headers = ['Content-Type' => 'application/json'], bool $parse = true): mixed
    {
        if (!str_starts_with($endpoint, '/'))
            $endpoint = '/' . $endpoint;

        try {
            $response = $this->client()->get($this->uri . $endpoint . (count($query) ? '?' . http_build_query($query) : ''), $headers, $this->requestOptions);

            return $this->response($response, $parse);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function post(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json'], bool $parse = true): mixed
    {
        if (!str_starts_with($endpoint, '/'))
            $endpoint = '/' . $endpoint;

        try {
            $response = $this->client()->post($this->uri . $endpoint, $body ? (is_string($body) ? $body : json_encode($body)) : null, $headers, $this->requestOptions);

            return $this->response($response, $parse);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function put(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json'], bool $parse = true): mixed
    {
        if (!str_starts_with($endpoint, '/'))
            $endpoint = '/' . $endpoint;

        try {
            $response = $this->client()->put($this->uri . $endpoint, $body ? (is_string($body) ? $body : json_encode($body)) : null, $headers, $this->requestOptions);

            return $this->response($response, $parse);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function delete(string $endpoint, array $headers = ['Content-Type' => 'application/json'], bool $parse = true): mixed
    {
        if (!str_starts_with($endpoint, '/'))
            $endpoint = '/' . $endpoint;

        try {
            $response = $this->client()->delete($this->uri . $endpoint, $headers, $this->requestOptions);

            return $this->response($response, $parse);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    private function response(Response $response, bool $parse): mixed
    {
        if ($parse)
            return $response->getParsedBody();

        if ($response->hasBody())
            return $response->getBody()->getContents();

        return '';
    }
}