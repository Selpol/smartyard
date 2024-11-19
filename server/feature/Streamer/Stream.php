<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

use JsonSerializable;
use Selpol\Entity\Model\Server\StreamerServer;

class Stream implements JsonSerializable
{
    private readonly StreamerServer $server;
    private readonly string $token;

    private string $source;

    private StreamContainer $container;
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

    public function getSource(): string
    {
        return $this->source;
    }

    public function getContainer(): StreamContainer
    {
        return $this->container ?? StreamContainer::SERVER;
    }

    public function getInput(): StreamInput
    {
        return $this->input ?? StreamInput::RTSP;
    }

    public function getOutput(): StreamOutput
    {
        return $this->output ?? StreamOutput::RTC;
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

    public function container(StreamContainer $value): static
    {
        $this->container = $value;

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
        return ['source' => $this->source, 'container' => $this->getContainer(), 'input' => $this->getInput(), 'output' => $this->getOutput()];
    }
}