<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

use JsonSerializable;
use Selpol\Feature\Streamer\StreamOutput;

readonly class DvrOnline implements JsonSerializable
{
    public string $server;
    public string $token;

    public StreamOutput $output;

    public function __construct(string $server, string $token, StreamOutput $output)
    {
        $this->server = $server;
        $this->token = $token;

        $this->output = $output;
    }

    public function jsonSerialize(): array
    {
        return ['server' => $this->server, 'token' => $this->token, 'output' => $this->output->value];
    }
}