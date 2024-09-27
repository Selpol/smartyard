<?php declare(strict_types=1);

namespace Selpol\Service\Prometheus\Collector;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\Prometheus\Collector;
use Selpol\Service\PrometheusService;

readonly class Gauge extends Collector
{
    public const TYPE = 'gauge';

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function set(int|float $value, array $labels = []): void
    {
        container(PrometheusService::class)->updateGauge([
            'name' => $this->getName(),
            'help' => $this->getHelp(),
            'type' => $this->getType(),

            'labelNames' => $this->getLabelNames(),
            'labelValues' => $labels,

            'value' => $value,

            'command' => self::COMMAND_SET
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function incBy(int|float $value, array $labels = []): void
    {
        container(PrometheusService::class)->updateGauge([
            'name' => $this->getName(),
            'help' => $this->getHelp(),
            'type' => $this->getType(),

            'labelNames' => $this->getLabelNames(),
            'labelValues' => $labels,

            'value' => $value,

            'command' => is_float($value) ? self::COMMAND_INCREMENT_FLOAT : self::COMMAND_INCREMENT_INTEGER
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function decBy(int|float $value, array $labels = []): void
    {
        $this->incBy(-$value, $labels);
    }
}