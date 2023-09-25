<?php

namespace Selpol\Service\Prometheus\Collector;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Service\Prometheus\Collector;
use Selpol\Service\PrometheusService;

class Histogram extends Collector
{
    const TYPE = 'histogram';

    private array $buckets;

    public function __construct(string $namespace, string $name, string $help, array $labelNames = [], ?array $buckets = null)
    {
        parent::__construct($namespace, $name, $help, $labelNames);

        $this->buckets = $buckets ?? self::getDefaultBuckets();
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function observe(float $value, array $labels = []): void
    {
        container(PrometheusService::class)->updateHistogram([
            'name' => $this->getName(),
            'help' => $this->getHelp(),
            'type' => $this->getType(),

            'labelNames' => $this->getLabelNames(),
            'labelValues' => $labels,

            'value' => $value,

            'buckets' => $this->buckets
        ]);
    }

    public static function getDefaultBuckets(): array
    {
        return [0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0];
    }
}