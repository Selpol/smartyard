<?php declare(strict_types=1);

namespace Selpol\Service\Prometheus;

readonly abstract class Collector
{
    public const COMMAND_INCREMENT_INTEGER = 1;

    public const COMMAND_INCREMENT_FLOAT = 2;

    public const COMMAND_SET = 3;

    protected string $name;

    public function __construct(string $namespace, string $name, protected string $help, /**
     * @var string[]
     */
    protected array $labelNames = [])
    {
        $this->name = ($namespace !== '' ? $namespace . '_' : '') . $name;
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