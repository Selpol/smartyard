<?php declare(strict_types=1);

namespace Selpol\Service\Prometheus\Collector;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Service\Prometheus\Collector;
use Selpol\Service\PrometheusService;

class Counter extends Collector
{
    const TYPE = 'counter';

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function incBy(int|float $value, array $labels = []): void
    {
        container(PrometheusService::class)->updateCounter([
            'name' => $this->getName(),
            'help' => $this->getHelp(),
            'type' => $this->getType(),

            'labelNames' => $this->getLabelNames(),
            'labelValues' => $labels,

            'value' => $value,

            'command' => is_float($value) ? self::COMMAND_INCREMENT_FLOAT : self::COMMAND_INCREMENT_INTEGER
        ]);
    }
}