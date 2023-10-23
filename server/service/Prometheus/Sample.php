<?php declare(strict_types=1);

namespace Selpol\Service\Prometheus;

readonly class Sample
{
    public string $name;

    public array $labelNames;
    public array $labelValues;

    public int|float $value;

    public function __construct(array $value)
    {
        $this->name = $value['name'];

        $this->labelNames = $value['labelNames'];
        $this->labelValues = $value['labelValues'];

        $this->value = intval($value['value']);
    }
}