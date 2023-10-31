<?php declare(strict_types=1);

namespace Selpol\Controller\Internal;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use RuntimeException;
use Selpol\Controller\Controller;
use Selpol\Framework\Http\Response;
use Selpol\Service\Prometheus\Metric;
use Selpol\Service\Prometheus\Sample;
use Selpol\Service\PrometheusService;

readonly class PrometheusController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function index(): Response
    {
        $metrics = container(PrometheusService::class)->collect();

        $result = [];

        foreach ($metrics as $metric) {
            $result[] = '# HELP ' . $metric->name . ' ' . $metric->help;
            $result[] = '# TYPE ' . $metric->name . ' ' . $metric->type;

            foreach ($metric->samples as $sample)
                $result[] = $this->renderSample($metric, $sample);
        }

        return http()->createResponse()
            ->withHeader('Content-Type', 'text/plain; version=0.0.4')
            ->withBody(http()->createStream(implode(PHP_EOL, $result) . PHP_EOL));
    }

    private function renderSample(Metric $metric, Sample $sample): string
    {
        $labelNames = $metric->labelNames;

        if (count($metric->labelNames) > 0 || count($sample->labelNames) > 0) {
            $escapedLabels = $this->escapeAllLabels($labelNames, $sample);

            return $sample->name . '{' . implode(',', $escapedLabels) . '} ' . $sample->value;
        }

        return $sample->name . ' ' . $sample->value;
    }

    private function escapeLabelValue(string $v): string
    {
        return str_replace(["\\", "\n", "\""], ["\\\\", "\\n", "\\\""], $v);
    }

    private function escapeAllLabels(array $labelNames, Sample $sample): array
    {
        $escapedLabels = [];

        $labels = array_combine(array_merge($labelNames, $sample->labelNames), $sample->labelValues);

        if ($labels === false)
            throw new RuntimeException('Unable to combine labels.');

        foreach ($labels as $labelName => $labelValue)
            $escapedLabels[] = $labelName . '="' . $this->escapeLabelValue((string)$labelValue) . '"';

        return $escapedLabels;
    }
}