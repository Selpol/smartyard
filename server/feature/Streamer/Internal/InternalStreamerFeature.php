<?php

declare(strict_types=1);

namespace Selpol\Feature\Streamer\Internal;

use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Feature\Streamer\Stream;
use Selpol\Feature\Streamer\StreamerFeature;
use Selpol\Framework\Client\Client;

readonly class InternalStreamerFeature extends StreamerFeature
{
    public function random(): StreamerServer
    {
        $streamers = array_map(static fn(StreamerServer $server) => $server->id, StreamerServer::fetchAll(setting: setting()->columns(['id'])));
        $index = rand(0, count($streamers) - 1);

        return StreamerServer::findById($streamers[$index], setting: setting()->nonNullable());
    }

    public function stream(Stream $value): void
    {
        file_logger('streamer')->debug('Publish stream ', [$value]);

        $request = client_request('PUT', uri($value->getServer()->web)->withPath('/api/v1/source'))
            ->withBody(stream(['token' => $value->getToken(), 'source' => $value->getSource(), 'input' => $value->getInput()->value, 'output' => $value->getOutput()->value, 'option' => ['latency' => $value->getLatency(), 'transport' => $value->getTransport()]]));

        $response = container(Client::class)->send($request);

        if ($response->getStatusCode() !== 200) {
            file_logger('streamer')->debug('Error put stream', [$response->getBody()->getContents()]);
        }
    }
}
