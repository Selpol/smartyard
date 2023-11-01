<?php declare(strict_types=1);

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Selpol\Framework\Http\ServerRequest;
use Selpol\Framework\Http\UploadedFile;

if (!function_exists('config_get')) {
    function config_get(?string $key = null, mixed $default = null): mixed
    {
        if ($key !== null) {
            $config = kernel()->getConfig();

            return collection_get($config, $key, $default);
        }

        return kernel()->getConfig();
    }
}

if (!function_exists('server_request')) {
    function server_request(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, body: stream(fopen('php://input', 'r')), cookiesParams: $_COOKIE, queryParams: $_GET, serverParams: $serverParams);
    }
}

if (!function_exists('uploaded_file')) {
    function uploaded_file(StreamInterface $stream, int $size = null, int $error = UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface
    {
        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }
}

if (!function_exists('parse_body')) {
    function parse_body(MessageInterface $message): mixed
    {
        $contents = $message->getBody()->getContents();

        if ($contents) {
            $contentType = $message->getHeader('Content-Type');

            if ($contentType && in_array('application/xml', $contentType)) {
                if (str_starts_with($contents, '<') && str_ends_with($contents, '>'))
                    return json_decode(json_encode(simplexml_load_string($contents)), true);
                else if (str_starts_with($contents, '{') && str_ends_with($contents, '}'))
                    return json_decode($contents, true);
            } else return json_decode($contents, true);
        }

        return null;
    }
}