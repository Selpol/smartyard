<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Feature\Feature;
use Selpol\Feature\Streamer\Internal\InternalStreamerFeature;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Client\ClientOption;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalStreamerFeature::class)]
readonly abstract class StreamerFeature extends Feature
{
    private ClientOption $option;

    public function __construct()
    {
        $this->option = (new ClientOption())->raw(CURLOPT_SSL_VERIFYPEER, false)->raw(CURLOPT_SSL_VERIFYHOST, false);
    }

    public abstract function random(): StreamerServer;

    public abstract function stream(Stream $value): void;

    public function listStream(StreamerServer $server): array
    {
        if (!$server->web) {
            return [];
        }

        $response = container(Client::class)->send(request('GET', $server->web . '/api/v1/stream'), $this->option);

        if ($response->getStatusCode() === 200) {
            $body = json_decode($response->getBody()->getContents(), true);

            if (array_key_exists('success', $body) && $body['success'] && array_key_exists('data', $body) && is_array($body['data'])) {
                return $body['data'];
            } else if (array_key_exists('message', $body)) {
                file_logger('streamer')->debug($body['message']);
            }
        }

        return [];
    }

    public function addStream(StreamerServer $server, ApiStream $stream): bool
    {
        if (!$server->web) {
            return false;
        }

        $response = container(Client::class)->send(request('POST', $server->web . '/api/v1/stream')->withBody(stream($stream)), $this->option);

        return $response->getStatusCode() === 200 && array_key_exists('success', $response) ? $response['success'] : false;
    }

    public function updateStream(StreamerServer $server, ApiStream $stream): bool
    {
        if (!$server->web) {
            return false;
        }

        $response = container(Client::class)->send(request('PUT', $server->web . '/api/v1/stream')->withBody(stream($stream)), $this->option);

        return $response->getStatusCode() === 200 && array_key_exists('success', $response) ? $response['success'] : false;
    }

    public function deleteStream(StreamerServer $server, ApiStream|string $stream): bool
    {
        if (!$server->web) {
            return false;
        }

        $id = $stream instanceof ApiStream ? $stream->id : (string)$stream;

        $response = container(Client::class)->send(request('DELETE', $server->web . '/api/v1/stream?id=' . $id), $this->option);

        return $response->getStatusCode() === 200 && array_key_exists('success', $response) ? $response['success'] : false;
    }
}