<?php declare(strict_types=1);

namespace Selpol\Service\Prometheus\Collector;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Service\Prometheus\Collector;
use Selpol\Service\PrometheusService;

readonly class Gauge extends Collector
{
    const TYPE = 'gauge';

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
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
     * @throws NotFoundExceptionInterface|RedisException
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
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function decBy(int|float $value, array $labels = []): void
    {
        $this->incBy(-$value, $labels);
    }
}