<?php

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

        $this->value = $value['value'];
    }
}