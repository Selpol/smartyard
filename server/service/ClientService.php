<?php declare(strict_types=1);

namespace Selpol\Service;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Http\Exception\HttpException;
use Selpol\Http\Request;
use Selpol\Http\Response;
use Selpol\Http\Stream;

class ClientService
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function get(string $uri, array $query = [], array $headers = []): Response
    {
        $request = container(HttpService::class)->createRequest('GET', $uri);

        foreach ($headers as $key => $value)
            $request->withHeader($key, $value);

        return $this->request($request);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function post(string $uri, ?string $body = null, array $headers = []): Response
    {
        $request = container(HttpService::class)->createRequest('POST', $uri);

        foreach ($headers as $key => $value)
            $request->withHeader($key, $value);

        if ($body)
            $request->withBody(container(HttpService::class)->createStream($body));

        return $this->request($request);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function put(string $uri, ?string $body = null, array $headers = []): Response
    {
        $request = container(HttpService::class)->createRequest('PUT', $uri);

        foreach ($headers as $key => $value)
            $request->withHeader($key, $value);

        if ($body)
            $request->withBody(container(HttpService::class)->createStream($body));

        return $this->request($request);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function delete(string $uri, array $headers = []): Response
    {
        $request = container(HttpService::class)->createRequest('DELETE', $uri);

        foreach ($headers as $key => $value)
            $request->withHeader($key, $value);

        return $this->request($request);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function request(Request $request): Response
    {
        $response = container(HttpService::class)->createResponse();

        $options = $this->createOptions($request);

        $this->addBodyOptions($options, $request);
        $this->addHeadersOptions($options, $request);

        if ($request->getUri()->getUserInfo())
            $options[CURLOPT_USERPWD] = $request->getUri()->getUserInfo();

        $this->addHeaderFunction($options, $response);
        $this->addWriteFunction($options, $response);

        $ch = curl_init();

        curl_setopt_array($ch, $options);
        curl_exec($ch);

        switch (curl_errno($ch)) {
            case CURLE_OK:
                break;
            case CURLE_COULDNT_RESOLVE_PROXY:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_COULDNT_CONNECT:
            case CURLE_OPERATION_TIMEOUTED:
            case CURLE_SSL_CONNECT_ERROR:
                throw new HttpException($request, $response, message: curl_error($ch));
            default:
                throw new HttpException($request, $response, message: curl_error($ch));
        }

        if ($response->hasBody())
            $response->getBody()->rewind();

        return $response;
    }

    private function createOptions(Request $request): array
    {
        return [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => false,

            CURLOPT_HTTP_VERSION => $request->getProtocolVersion(),

            CURLOPT_URL => (string)$request->getUri()
        ];
    }

    private function addBodyOptions(array &$options, Request $request): void
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD', 'TRACE'], true) && $request->hasBody()) {
            $body = $request->getBody();
            $bodySize = $body->getSize();

            if ($bodySize === null || $bodySize > 1024 * 1024) {
                $options[CURLOPT_UPLOAD] = true;

                if ($bodySize !== null)
                    $options[CURLOPT_INFILESIZE] = $bodySize;

                $options[CURLOPT_READFUNCTION] = static function ($ch, $fd, $len) use ($body) {
                    return $body->read($len);
                };
            } else $options[CURLOPT_POSTFIELDS] = $body->getContents();
        }

        if ($request->getMethod() === 'HEAD')
            $options[CURLOPT_NOBODY] = true;
        else if ($request->getMethod() !== 'GET')
            $options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
    }

    private function addHeadersOptions(array &$options, Request $request): void
    {
        $headers = [];
        $requestHeaders = $request->getHeaders();

        foreach ($requestHeaders as $key => $values) {
            if ($key === 'Content-Length') {
                if (array_key_exists(CURLOPT_POSTFIELDS, $options))
                    $values = [strlen($options[CURLOPT_POSTFIELDS])];
                else if (!array_key_exists(CURLOPT_READFUNCTION, $options))
                    $values = [0];
            }

            foreach ($values as $value)
                $headers[] = $key . ': ' . $value;
        }

        $options[CURLOPT_HTTPHEADER] = $headers;
    }

    private function addHeaderFunction(array &$options, Response $response): void
    {
        $options[CURLOPT_HEADERFUNCTION] = static function ($ch, $data) use ($response) {
            $cleanData = trim($data);

            if ($cleanData !== '') {
                if (str_starts_with(strtoupper($cleanData), 'HTTP/')) {
                    $statusSegments = explode(' ', $cleanData, 3);

                    if ($statusSegments < 2)
                        throw new InvalidArgumentException($cleanData . ' is not a valid HTTP status line');

                    $reasonPhrase = count($statusSegments) > 2 ? $statusSegments[2] : '';

                    $response->withStatus((int)$statusSegments[1], $reasonPhrase)->withProtocolVersion(substr($statusSegments[0], 5));
                } else {
                    $headerSegments = explode(':', $cleanData, 2);

                    if (count($headerSegments) !== 2)
                        throw new InvalidArgumentException($cleanData . ' is not a valid HTTP header line');

                    $name = trim($headerSegments[0]);
                    $value = trim($headerSegments[1]);

                    $response->withAddedHeader($name, $value);
                }
            }

            return strlen($data);
        };
    }

    private function addWriteFunction(array &$options, Response $response): void
    {
        $options[CURLOPT_WRITEFUNCTION] = static function ($ch, $data) use ($response) {
            if (!$response->hasBody())
                $response->withBody(Stream::memory());

            return $response->getBody()->write($data);
        };
    }
}