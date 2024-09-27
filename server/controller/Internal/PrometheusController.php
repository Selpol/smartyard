<?php declare(strict_types=1);

namespace Selpol\Controller\Internal;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use RuntimeException;
use Selpol\Controller\RbtController;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Middleware\PrometheusMiddleware;
use Selpol\Service\Prometheus\Metric;
use Selpol\Service\Prometheus\Sample;
use Selpol\Service\PrometheusService;

#[Controller('/internal/prometheus', excludes: [PrometheusMiddleware::class])]
readonly class PrometheusController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    #[Get]
    public function index(): Response
    {
        $service = container(PrometheusService::class);

        $metrics = $service->collect();

        $result = [];

        foreach ($metrics as $metric) {
            $result[] = '# HELP smartyard_' . $metric->name . ' ' . $metric->help;
            $result[] = '# TYPE smartyard_' . $metric->name . ' ' . $metric->type;

            foreach ($metric->samples as $sample)
                $result[] = $this->renderSample($metric, $sample);
        }

        $this->memory($result);
        $this->memory_peak($result);

        return response()
            ->withHeader('Content-Type', 'text/plain; version=0.0.4')
            ->withBody(stream(implode(PHP_EOL, $result) . PHP_EOL));
    }

    private function memory(array &$result): void
    {
        $result[] = '# HELP smartyard_php_memory PHP Memory usage';
        $result[] = '# TYPE smartyard_php_memory gauge';
        $result[] = 'smartyard_php_memory{} ' . memory_get_usage();
    }

    private function memory_peak(array &$result): void
    {
        $result[] = '# HELP smartyard_php_memory_peak PHP Memory peak usage';
        $result[] = '# TYPE smartyard_php_memory_peak gauge';
        $result[] = 'smartyard_php_memory_peak{} ' . memory_get_peak_usage();
    }

    private function renderSample(Metric $metric, Sample $sample): string
    {
        $labelNames = $metric->labelNames;

        if ($metric->labelNames !== [] || $sample->labelNames !== []) {
            $escapedLabels = $this->escapeAllLabels($labelNames, $sample);

            return 'smartyard_' . $sample->name . '{' . implode(',', $escapedLabels) . '} ' . $sample->value;
        }

        return 'smartyard_' . $sample->name . ' ' . $sample->value;
    }

    private function escapeLabelValue(string $v): string
    {
        return str_replace(["\\", "\n", '"'], ["\\\\", "\\n", "\\\""], $v);
    }

    private function escapeAllLabels(array $labelNames, Sample $sample): array
    {
        $escapedLabels = [];

        $labels = array_combine(array_merge($labelNames, $sample->labelNames), $sample->labelValues);

        if ($labels === false) {
            throw new RuntimeException('Unable to combine labels.');
        }

        foreach ($labels as $labelName => $labelValue)
            $escapedLabels[] = $labelName . '="' . $this->escapeLabelValue((string)$labelValue) . '"';

        return $escapedLabels;
    }
}