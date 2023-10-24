<?php declare(strict_types=1);

namespace Selpol\Device\Ip;

use Selpol\Device\Device;
use Selpol\Device\Exception\DeviceException;
use Selpol\Framework\Client\ClientOption;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;
use Throwable;

abstract class IpDevice extends Device
{
    public string $login = 'root';

    public string $password;

    protected ClientOption $clientOption;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password)
    {
        parent::__construct($uri->withUserInfo($this->login, $password));

        $this->password = $password;

        $this->clientOption = (new ClientOption())->basic($this->login, $this->password);
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

        try {
            $fp = stream_socket_client($url, timeout: 1);

            if ($fp) {
                fclose($fp);

                if (array_key_exists('DeviceID', $this->getSysInfo()))
                    return true;

                return false;
            }

            return false;
        } catch (Throwable) {
            return false;
        }
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
            $request = http()->createRequest('GET', $this->uri . $endpoint . (count($query) ? '?' . http_build_query($query) : ''));

            foreach ($headers as $header => $value)
                $request->withHeader($header, $value);

            $response = $this->client->send($request, $this->clientOption);

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
            $request = http()->createRequest('POST', $this->uri . $endpoint);

            foreach ($headers as $header => $value)
                $request->withHeader($header, $value);

            if ($body) {
                if (is_string($body))
                    $request->withBody(http()->createStream($body));
                else
                    $request->withBody(http()->createStream(json_encode($body)));
            }

            $response = $this->client->send($request, $this->clientOption);

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
            $request = http()->createRequest('PUT', $this->uri . $endpoint);

            foreach ($headers as $header => $value)
                $request->withHeader($header, $value);

            if ($body) {
                if (is_string($body))
                    $request->withBody(http()->createStream($body));
                else
                    $request->withBody(http()->createStream(json_encode($body)));
            }

            $response = $this->client->send($request, $this->clientOption);

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
            $request = http()->createRequest('DELETE', $this->uri . $endpoint);

            foreach ($headers as $header => $value)
                $request->withHeader($header, $value);

            $response = $this->client->send($request, $this->clientOption);

            return $this->response($response, $parse);
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    private function response(Response $response, bool $parse): mixed
    {
        if ($parse)
            return http()->getParsedBody($response->getBody(), $response->getHeader('Content-Type'));

        return $response->getBody()->getContents();
    }
}