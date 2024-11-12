<?php declare(strict_types=1);

namespace Selpol\Device\Ip;

use Psr\Http\Message\ResponseInterface;
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

    protected ClientOption $clientOption;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password)
    {
        parent::__construct($uri);

        $this->password = trim($password);

        $this->clientOption = (new ClientOption())->basic($this->login, $this->password);

        $this->debug = config_get('debug', false);

        $this->setLogger(file_logger('ip'));
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

        try {
            $fp = stream_socket_client($url, $code, $message, timeout: 1);

            if ($fp) {
                fclose($fp);

                return true;
            }

            return false;
        } catch (Throwable) {
            return false;
        }
    }

    public function ping(): bool
    {
        try {
            return array_key_exists('DeviceID', $this->getSysInfo());
        } catch (Throwable) {
            return false;
        }
    }

    public function getSysInfo(): array
    {
        throw new DeviceException($this, 'Не удалось получить информацию об устройстве');
    }

    public function setLoginPassword(#[SensitiveParameter] string $password): static
    {
        return $this;
    }

    public function get(string $endpoint, array $query = [], array $headers = ['Content-Type' => 'application/json'], bool|array $parse = true): mixed
    {
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
            $this->logger?->debug($endpoint . PHP_EOL . '--GET  REQUEST--' . PHP_EOL . 'QUERY: ' . json_encode($query) . PHP_EOL . '--GET RESPONSE--' . PHP_EOL . 'STATUS: ' . $response->getStatusCode() . PHP_EOL . 'RESULT: ' . json_encode($result));
        }

        return $result;
    }

    public function post(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json'], bool|array $parse = true): mixed
    {
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
            $this->logger?->debug($endpoint . PHP_EOL . '--POST  REQUEST--' . PHP_EOL . 'BODY: ' . json_encode($body) . PHP_EOL . '--POST RESPONSE--' . PHP_EOL . 'STATUS: ' . $response->getStatusCode() . PHP_EOL . 'RESULT: ' . json_encode($result));
        }

        return $result;
    }

    public function put(string $endpoint, mixed $body = null, array $headers = ['Content-Type' => 'application/json'], bool|array $parse = true): mixed
    {
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
            $this->logger?->debug($endpoint . PHP_EOL . '--PUT  REQUEST--' . PHP_EOL . 'BODY: ' . json_encode($body) . PHP_EOL . '--PUT RESPONSE--' . PHP_EOL . 'STATUS: ' . $response->getStatusCode() . PHP_EOL . 'RESULT: ' . json_encode($result));
        }

        return $result;
    }

    public function delete(string $endpoint, array $headers = ['Content-Type' => 'application/json'], bool|array $parse = true): mixed
    {
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
            $this->logger?->debug($endpoint . PHP_EOL . '--DELETE  REQUEST--' . PHP_EOL . '--DELETE RESPONSE--' . PHP_EOL . 'STATUS: ' . $response->getStatusCode() . PHP_EOL . 'RESULT: ' . json_encode($result));
        }

        return $result;
    }

    /**
     * @return void
     * @throws DeviceException
     */
    private function prepare(): void
    {
        if (!$this->pingRaw()) {
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