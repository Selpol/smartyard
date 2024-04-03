<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Feature\Feature;
use Selpol\Feature\Streamer\Redis\RedisStreamerFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(RedisStreamerFeature::class)]
readonly abstract class StreamerFeature extends Feature
{
    public abstract function random(): StreamerServer;

    public abstract function stream(Stream $value): void;
}