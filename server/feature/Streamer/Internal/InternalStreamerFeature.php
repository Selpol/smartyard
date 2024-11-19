<?php declare(strict_types=1);

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
        $request = client_request('PUT', uri($value->getServer()->title)->withPath('/api/v1/source'));

        $response = container(Client::class)->send($request);

        if ($response->getStatusCode() !== 200) {
            file_logger('streamer')->debug('Error put stream', [$response->getBody()->getContents()]);
        }
    }
}