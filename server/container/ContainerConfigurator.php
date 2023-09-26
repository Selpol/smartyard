<?php declare(strict_types=1);

namespace Selpol\Container;

class ContainerConfigurator
{
    private array $factories = [];

    public function getFactories(): array
    {
        return $this->factories;
    }

    public function singleton(string $id, ?string $factory = null): void
    {
        $this->factories[$id] = [true, $factory];
    }

    public function factory(string $id, ?string $factory = null): void
    {
        $this->factories[$id] = [false, $factory];
    }
}