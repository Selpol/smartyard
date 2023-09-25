<?php

namespace Selpol\Service\Prometheus;

abstract class Collector
{
    const COMMAND_INCREMENT_INTEGER = 1;
    const COMMAND_INCREMENT_FLOAT = 2;

    const COMMAND_SET = 3;

    protected string $name;
    protected string $help;

    /**
     * @var string[]
     */
    protected array $labelNames;

    public function __construct(string $namespace, string $name, string $help, array $labelNames = [])
    {
        $this->name = ($namespace !== '' ? $namespace . '_' : '') . $name;
        $this->help = $help;

        $this->labelNames = $labelNames;
    }

    public abstract function getType(): string;

    public function getName(): string
    {
        return $this->name;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    public function getLabelNames(): array
    {
        return $this->labelNames;
    }
}