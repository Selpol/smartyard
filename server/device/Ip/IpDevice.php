<?php

declare(strict_types=1);

namespace Selpol\Device\Ip;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Selpol\Device\Device;
use Selpol\Device\Exception\DeviceException;
use Selpol\Framework\Client\ClientOption;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;
use Throwable;

abstract class IpDevice extends Device
{
    public string $login = 'root';

    public string $password;

    public bool $debug;

    public int $timeout;
    public int $prepare;

    protected ClientOption $clientOption;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password)
    {
        parent::__construct($uri);

        $this->password = trim($password);

        $this->clientOption = (new ClientOption())->basic($this->login, $this->password);

        $this->debug = config_get('debug', false);

        $this->timeout = 0;
        $this->prepare = 1;

        $this->setLogger(file_logger('ip'));
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function pingIcmp(float $timeout = 1): bool
    {
        $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
        $socket = socket_create(AF_INET, SOCK_RAW, getprotobyname('icmp'));

        if (!$socket) {
            return false;
        }

        $timeout_seconds = floor($timeout);
        $timeout_microseconds = ($timeout - $timeout_seconds) * 1000000;
        $timeout_array = ['sec' => $timeout_seconds, 'usec' => $timeout_microseconds];

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout_array);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout_array);

        socket_sendto($socket, $package, strlen($package), 0, $this->uri->getHost(), 0);

        if (socket_recvfrom($socket, $response, 1024, 0, $from, $port)) {
            return true;
        }

        return false;
    }

    public function pingRaw(): bool
    {
        $url = $this->uri->getHost();

        if ($this->uri->getPort() === null) {
            $url .= ':' . match (strtolower($this->uri->getScheme())) {
                'http' => 80,
                'https' => 443,
                default => 22
            };
        } else {
            $url .= ':' . $this->uri->getPort();
        }

        $reporting = error_reporting();

        try {
            error_reporting(E_ERROR);

            $fp = stream_socket_client($url, $code, $message, timeout: 1);

            if ($fp) {
                fclose($fp);

                return true;
            }

            error_reporting($reporting);

            return false;
        } catch (Throwable) {
            error_reporting($reporting);

            return false;
        }
    }

    public function ping(): bool
    {
        try {
            return $this->getSysInfo()->deviceId !== '';
        } catch (Throwable) {
            return false;
        }
    }

    public function pingOrThrow(): void
    {
        if (!$this->getSysInfo()->deviceId) {
            throw new DeviceException($this, 'Не удалось узнать информацию об устройстве');
        }
    }

    public function getSysInfo(): InfoDevice
    {
        throw new DeviceException($this, 'Не удалось получить информацию об устройстве');
    }

    public function setLoginPassword(#[SensitiveParameter] string $password): static
    {
        return $this;
    }

    public function get(string $endpoint, array $query = [], array $headers = ['Content-Type' => 'application/json'], bool|array $parse = true): mixed
    {
        $now = microtime(true);

        $this->prepare();

        if (!str_starts_with($endpoint, '/')) {
            $endpoint = '/' . $endpoint;
        }

        if (!str_starts_with($endpoint, 'http')) {
            $endpoint = $this->uri . $endpoint;
        }

        $request = client_request('GET', $endpoint . ($query !== [] ? '?' . http_build_query($query) : ''));

        foreach ($headers as $header => $value) {
            $request->withHeader($header, $value);
        }

        $response = $this->client->send($request, $this->clientOption);
        $result = $this->response($response, $parse);

        if ($this->debug) {
            $this->logger?->debug($endpoint . PHP_EOL . '--GET  REQUEST (' . (microtime(true) - $now) . 'ms)--' . PHP_EOL . 'QUERY: ' . json_encode($query) . PHP_EOL . '--GET RESPONSE--' . PHP_EOL . 'STATUS: ' . $response->getStatusCode() . PHP_EOL . 'RESULT: ' . json_encode($result));
        }

        return $result;
    }

    public function post(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json'], bool|array $parse = true): mixed
    {
        $now = microtime(true);

        $this->prepare();

        if (!str_starts_with($endpoint, '/')) {
            $endpoint = '/' . $endpoint;
        }

        if (!str_starts_with($endpoint, 'http')) {
            $endpoint = $this->uri . $endpoint;
        }

        $request = client_request('POST', $endpoint);

        foreach ($headers as $header => $value) {
            $request->withHeader($header, $value);
        }

        if ($body) {
            if (is_string($body)) {
                $request->withBody(stream($body));
            } else {
                $request->withBody(stream(json_encode($body)));
            }
        }

        $response = $this->client->send($request, $this->clientOption);
        $result = $this->response($response, $parse);

        if ($this->debug) {
            $this->logger?->debug($endpoint . PHP_EOL . '--POST  REQUEST (' . (microtime(true) - $now) . 'ms)--' . PHP_EOL . 'BODY: ' . json_encode($body) . PHP_EOL . '--POST RESPONSE--' . PHP_EOL . 'STATUS: ' . $response->getStatusCode() . PHP_EOL . 'RESULT: ' . json_encode($result));
        }

        return $result;
    }

    public function put(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json'], bool|array $parse = true): mixed
    {
        $now = microtime(true);

        $this->prepare();

        if (!str_starts_with($endpoint, '/')) {
            $endpoint = '/' . $endpoint;
        }

        if (!str_starts_with($endpoint, 'http')) {
            $endpoint = $this->uri . $endpoint;
        }

        $request = client_request('PUT', $endpoint);

        foreach ($headers as $header => $value) {
            $request->withHeader($header, $value);
        }

        if ($body) {
            if (is_string($body)) {
                $request->withBody(stream($body));
            } else {
                $request->withBody(stream(json_encode($body)));
            }
        }

        $response = $this->client->send($request, $this->clientOption);
        $result = $this->response($response, $parse);

        if ($this->debug) {
            $this->logger?->debug($endpoint . PHP_EOL . '--PUT  REQUEST (' . (microtime(true) - $now) . 'ms)--' . PHP_EOL . 'BODY: ' . json_encode($body) . PHP_EOL . '--PUT RESPONSE--' . PHP_EOL . 'STATUS: ' . $response->getStatusCode() . PHP_EOL . 'RESULT: ' . json_encode($result));
        }

        return $result;
    }

    public function delete(string $endpoint, array $headers = ['Content-Type' => 'application/json'], bool|array $parse = true): mixed
    {
        $now = microtime(true);

        $this->prepare();

        if (!str_starts_with($endpoint, '/')) {
            $endpoint = '/' . $endpoint;
        }

        if (!str_starts_with($endpoint, 'http')) {
            $endpoint = $this->uri . $endpoint;
        }

        $request = client_request('DELETE', $endpoint);

        foreach ($headers as $header => $value) {
            $request->withHeader($header, $value);
        }

        $response = $this->client->send($request, $this->clientOption);
        $result = $this->response($response, $parse);

        if ($this->debug) {
            $this->logger?->debug($endpoint . PHP_EOL . '--DELETE  REQUEST (' . (microtime(true) - $now) . 'ms)--' . PHP_EOL . '--DELETE RESPONSE--' . PHP_EOL . 'STATUS: ' . $response->getStatusCode() . PHP_EOL . 'RESULT: ' . json_encode($result));
        }

        return $result;
    }

    public static function template(string $value, array $values): string
    {
        if (preg_match_all('(%\w+%)', $value, $matches)) {
            foreach ($matches as $match) {
                foreach ($match as $item) {
                    $key = substr($item, 1, -1);

                    if (array_key_exists($key, $values)) {
                        $value = str_replace($item, $values[$key], $value);
                    }
                }
            }
        }

        return $value;
    }

    /**
     * @return void
     * @throws DeviceException
     */
    private function prepare(): void
    {
        if ($this->timeout > 0) {
            usleep($this->timeout);
        }

        if ($this->prepare === 0) {
            return;
        }

        if ($this->prepare === 1 && !$this->pingRaw()) {
            throw new DeviceException($this, 'Устройство не доступно');
        }

        if ($this->prepare === 2 && !$this->ping()) {
            throw new DeviceException($this, 'Устройство не доступно');
        }
    }

    private function response(ResponseInterface $response, bool|array $parse): mixed
    {
        if ($response->getStatusCode() === 401) {
            throw new DeviceException($this, 'Ошибка авторизации', 'Authorization error', 401);
        }

        if ($parse) {
            if (is_array($parse)) {
                return parse_body($response, $parse);
            }

            return parse_body($response);
        }

        return $response->getBody()->getContents();
    }
}
