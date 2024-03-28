<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

use JsonSerializable;
use Selpol\Entity\Model\Server\StreamerServer;

class Stream implements JsonSerializable
{
    private readonly StreamerServer $server;
    private readonly string $token;

    private string $source;

    private StreamInput $input;
    private StreamOutput $output;

    public function __construct(StreamerServer $server, ?string $token = null)
    {
        $this->server = $server;
        $this->token = $token ?: uniqid(more_entropy: true);
    }

    public function getServer(): StreamerServer
    {
        return $this->server;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getOutput(): StreamOutput
    {
        return $this->output;
    }

    /**
     * Ссылка на видеопоток для рестримера
     * @param string $value
     * @return $this
     */
    public function source(string $value): static
    {
        $this->source = $value;

        return $this;
    }

    /**
     * Указываем обязательный тип входного потока для рестримера
     * @param StreamInput $value
     * @return $this
     */
    public function input(StreamInput $value): static
    {
        $this->input = $value;

        return $this;
    }

    /**
     * Указываем обязательный тип выходного потока для рестримера
     * @param StreamOutput $value
     * @return $this
     */
    public function output(StreamOutput $value): static
    {
        $this->output = $value;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return ['source' => $this->source, 'input' => $this->input ?? StreamInput::RTSP, 'output' => $this->output ?? StreamOutput::RTC];
    }
}