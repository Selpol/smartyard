<?php declare(strict_types=1);

use Psr\Http\Message\MessageInterface;

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

if (!function_exists('parse_body')) {
    function parse_body(MessageInterface $message): mixed
    {
        $contents = trim($message->getBody()->getContents());

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