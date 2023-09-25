<?php

namespace Selpol\Service\Prometheus\Collector;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Service\Prometheus\Collector;
use Selpol\Service\PrometheusService;

class Summary extends Collector
{
    const TYPE = 'summary';

    private array $quantiles;

    private int $maxAgeSeconds;

    public function __construct(string $namespace, string $name, string $help, array $labelNames = [], array $quantiles = null, int $maxAgeSeconds = 600)
    {
        parent::__construct($namespace, $name, $help, $labelNames);

        $this->quantiles = $quantiles ?? self::getDefaultQuantiles();

        $this->maxAgeSeconds = $maxAgeSeconds;
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
        container(PrometheusService::class)->updateSummary([
            'name' => $this->getName(),
            'help' => $this->getHelp(),
            'type' => $this->getType(),

            'labelNames' => $this->getLabelNames(),
            'labelValues' => $labels,

            'value' => $value,

            'quantiles' => $this->quantiles,

            'maxAgeSeconds' => $this->maxAgeSeconds
        ]);
    }

    public static function getDefaultQuantiles(): array
    {
        return [0.01, 0.05, 0.5, 0.95, 0.99];
    }
}