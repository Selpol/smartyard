<?php declare(strict_types=1);

namespace Selpol\Service\Prometheus;

class Sample
{
    public readonly string $name;

    public readonly array $labelNames;
    public readonly array $labelValues;

    public readonly int|float $value;

    public function __construct(array $value)
    {
        $this->name = $value['name'];

        $this->labelNames = $value['labelNames'];
        $this->labelValues = $value['labelValues'];

        $this->value = intval($value['value']);
    }
}