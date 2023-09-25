<?php

namespace Selpol\Service\Prometheus;

class Metric
{
    public string $name;
    public string $type;
    public string $help;

    /** @var string[] */
    public array $labelNames;

    /** @var Sample[] */
    public array $samples = [];

    public function __construct(array $value)
    {
        $this->name = $value['name'];
        $this->type = $value['type'];
        $this->help = $value['help'];

        $this->labelNames = $value['labelNames'];

        foreach ($value['samples'] as $sample)
            $this->samples[] = new Sample($sample);
    }
}