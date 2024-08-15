<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;
use Selpol\Feature\Streamer\StreamOutput;

readonly class DvrStreamer implements JsonSerializable
{
    public function __construct(public string $server, public string $token, public StreamOutput $output)
    {
    }

    public function jsonSerialize(): array
    {
        return ['server' => $this->server, 'token' => $this->token, 'output' => $this->output->value];
    }
}