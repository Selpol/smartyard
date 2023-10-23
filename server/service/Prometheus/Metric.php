<?php declare(strict_types=1);

namespace Selpol\Service\Prometheus;

readonly class Metric
{
    public string $name;
    public string $type;
    public string $help;

    /** @var string[] */
    public array $labelNames;

    /** @var Sample[] */
    public array $samples;

    public function __construct(array $value)
    {
        $this->name = $value['name'];
        $this->type = $value['type'];
        $this->help = $value['help'];

        $this->labelNames = $value['labelNames'];

        $result = [];

        foreach ($value['samples'] as $sample)
            $result[] = new Sample($sample);

        $this->samples = $result;
    }
}