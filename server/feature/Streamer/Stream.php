<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

use JsonSerializable;
use Selpol\Entity\Model\Server\StreamerServer;

class Stream implements JsonSerializable
{
    private readonly StreamerServer $server;
    private readonly string $token;

    private string $source;

    private StreamInput $input = StreamInput::RTSP;
    private StreamOutput $output = StreamOutput::RTC;

    private int $latency = 100;
    private StreamTransport $transport = StreamTransport::TCP;

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

    public function getSource(): string
    {
        return $this->source;
    }

    public function getInput(): StreamInput
    {
        return $this->input;
    }

    public function getOutput(): StreamOutput
    {
        return $this->output;
    }

    public function getLatency(): int
    {
        return $this->latency;
    }

    public function getTransport(): StreamTransport
    {
        return $this->transport;
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

    public function latency(int $value): static
    {
        $this->latency = $value;

        return $this;
    }

    public function transport(StreamTransport $value): static
    {
        $this->transport = $value;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return ['source' => $this->source, 'input' => $this->getInput()->value, 'output' => $this->getOutput()->value, 'option' => ['latency' => $this->getLatency(), 'transport' => $this->getTransport()->value]];
    }
}