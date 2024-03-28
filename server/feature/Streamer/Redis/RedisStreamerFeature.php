<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer\Redis;

use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Feature\Streamer\Stream;
use Selpol\Feature\Streamer\StreamerFeature;

readonly class RedisStreamerFeature extends StreamerFeature
{
    public function random(): StreamerServer
    {
        $streamers = array_map(static fn(StreamerServer $server) => $server->id, StreamerServer::fetchAll(setting: setting()->columns(['id'])));
        $index = rand(0, count($streamers) - 1);

        return StreamerServer::findById($streamers[$index], setting: setting()->nonNullable());
    }

    public function stream(Stream $value): void
    {
        // 30 Секунд время, за которое нужно запросить стрим, иначе он будет утерен и его требуется запросить заново.
        $this->getRedis()->setEx('streamer:' . $value->getServer()->id . ':' . $value->getToken(), 30, json_encode($value));
    }
}