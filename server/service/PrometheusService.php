<?php declare(strict_types=1);

namespace Selpol\Service;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Service\Prometheus\Collector;
use Selpol\Service\Prometheus\Collector\Counter;
use Selpol\Service\Prometheus\Collector\Gauge;
use Selpol\Service\Prometheus\Collector\Histogram;
use Selpol\Service\Prometheus\Collector\Summary;
use Selpol\Service\Prometheus\Metric;

class PrometheusService
{
    const PREFIX = 'PROMETHEUS_';
    const SUFFIX = '_METRIC_KEYS';

    /** @var Counter[] */
    private array $counters = [];

    /** @var Gauge[] */
    private array $gauges = [];

    /** @var Histogram[] */
    private array $histograms;

    /** @var Summary[] */
    private array $summaries = [];

    /**
     * @return Metric[]
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function collect(): array
    {
        return array_map(
            static fn(array $metric): Metric => new Metric($metric),
            array_merge(
                $this->collectHistograms(),
                $this->collectGauges(),
                $this->collectCounters(),
                $this->collectSummaries()
            )
        );
    }

    public function getCounter(string $namespace, string $name, string $help, array $labels = []): Counter
    {
        if (!array_key_exists($namespace . $name, $this->counters))
            $this->counters[$namespace . $name] = new Counter($namespace, $name, $help, $labels);

        return $this->counters[$namespace . $name];
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function updateCounter(array $value): void
    {
        $redis = container(RedisService::class)->getRedis();

        $metaData = $value;

        unset($metaData['value'], $metaData['labelValues'], $metaData['command']);

        $redis->eval(
            <<<LUA
local result = redis.call(ARGV[1], KEYS[1], ARGV[3], ARGV[2])
local added = redis.call('sAdd', KEYS[2], KEYS[1])
if added == 1 then
    redis.call('hMSet', KEYS[1], '__meta', ARGV[4])
end
return result
LUA
            ,
            [
                $this->toMetricKey($value),
                self::PREFIX . Counter::TYPE . self::SUFFIX,
                $this->toRedisCommand($value),
                $value['value'],
                json_encode($value['labelValues']),
                json_encode($metaData),
            ],
            2
        );
    }

    public function getGauge(string $namespace, string $name, string $help, array $labels = []): Gauge
    {
        if (!array_key_exists($namespace . $name, $this->gauges))
            $this->gauges[$namespace . $name] = new Gauge($namespace, $name, $help, $labels);

        return $this->gauges[$namespace . $name];
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function updateGauge(array $value): void
    {
        $redis = container(RedisService::class)->getRedis();

        $metaData = $value;
        unset($metaData['value'], $metaData['labelValues'], $metaData['command']);

        $redis->eval(
            <<<LUA
local result = redis.call(ARGV[1], KEYS[1], ARGV[2], ARGV[3])

if ARGV[1] == 'hSet' then
    if result == 1 then
        redis.call('hSet', KEYS[1], '__meta', ARGV[4])
        redis.call('sAdd', KEYS[2], KEYS[1])
    end
else
    if result == ARGV[3] then
        redis.call('hSet', KEYS[1], '__meta', ARGV[4])
        redis.call('sAdd', KEYS[2], KEYS[1])
    end
end
LUA
            ,
            [
                $this->toMetricKey($value),
                self::PREFIX . Histogram::TYPE . self::SUFFIX,
                $this->toRedisCommand($value),
                json_encode($value['labelValues']),
                $value['value'],
                json_encode($metaData),
            ],
            2
        );
    }

    public function getHistogram(string $namespace, string $name, string $help, array $labels = [], ?array $buckets = null): Histogram
    {
        if (!array_key_exists($namespace . $name, $this->histograms))
            $this->histograms[$namespace . $name] = new Histogram($namespace, $name, $help, $labels, $buckets);

        return $this->histograms[$namespace . $name];
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function updateHistogram(array $value): void
    {
        $redis = container(RedisService::class)->getRedis();

        $bucketToIncrease = '+Inf';

        foreach ($value['buckets'] as $bucket) {
            if ($value['value'] <= $bucket) {
                $bucketToIncrease = $bucket;

                break;
            }
        }

        $metaData = $value;

        unset($metaData['value'], $metaData['labelValues']);

        $redis->eval(<<<LUA
local result = redis.call('hIncrByFloat', KEYS[1], ARGV[1], ARGV[3])
redis.call('hIncrBy', KEYS[1], ARGV[2], 1)
if tonumber(result) >= tonumber(ARGV[3]) then
    redis.call('hSet', KEYS[1], '__meta', ARGV[4])
    redis.call('sAdd', KEYS[2], KEYS[1])
end
return result
LUA,
            [
                $this->toMetricKey($value),
                self::PREFIX . Histogram::TYPE . self::SUFFIX,
                json_encode(['b' => 'sum', 'labelValues' => $value['labelValues']]),
                json_encode(['b' => $bucketToIncrease, 'labelValues' => $value['labelValues']]),
                $value['value'],
                json_encode($metaData)
            ],
            2
        );
    }

    public function getSummary(string $namespace, string $name, string $help, array $labels = [], array $quantiles = null, int $maxAgeSeconds = 600): Summary
    {
        if (!array_key_exists($namespace . $name, $this->summaries))
            $this->summaries[] = new Summary($namespace, $name, $help, $labels, $quantiles, $maxAgeSeconds);

        return $this->summaries[$namespace . $name];
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function updateSummary(array $value): void
    {
        $redis = container(RedisService::class)->getRedis();

        // store meta
        $summaryKey = self::PREFIX . Summary::TYPE . self::SUFFIX;
        $metaKey = $summaryKey . ':' . $value['name'] . ':meta';
        $json = json_encode($this->metaData($value));

        $redis->setNx($metaKey, $json);

        // store value key
        $valueKey = $summaryKey . ':' . $this->valueKey($value);
        $json = json_encode($this->encodeLabelValues($value['labelValues']));

        $redis->setNx($valueKey, $json);

        // trick to handle uniqid collision
        $done = false;
        while (!$done) {
            $sampleKey = $valueKey . ':' . uniqid('', true);
            $done = $redis->set($sampleKey, $value['value'], ['NX', 'EX' => $value['maxAgeSeconds']]);
        }
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function wipe(): void
    {
        $redis = container(RedisService::class)->getRedis();

        $keys = $redis->keys(self::PREFIX . '*');

        if ($keys && count($keys))
            foreach ($keys as $key)
                $redis->del($key);
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    private function collectHistograms(): array
    {
        $redis = container(RedisService::class)->getRedis();

        $keys = $redis->sMembers(self::PREFIX . Histogram::TYPE . self::SUFFIX);

        sort($keys);

        $histograms = [];

        foreach ($keys as $key) {
            $raw = $redis->hGetAll(str_replace($redis->_prefix(''), '', $key));

            if (!isset($raw['__meta']))
                continue;

            $histogram = json_decode($raw['__meta'], true);
            unset($raw['__meta']);
            $histogram['samples'] = [];

            $histogram['buckets'][] = '+Inf';

            $allLabelValues = [];

            foreach (array_keys($raw) as $k) {
                $d = json_decode($k, true);

                if ($d['b'] == 'sum')
                    continue;

                $allLabelValues[] = $d['labelValues'];
            }

            $allLabelValues = array_map("unserialize", array_unique(array_map("serialize", $allLabelValues)));
            sort($allLabelValues);

            foreach ($allLabelValues as $labelValues) {
                $acc = 0;

                foreach ($histogram['buckets'] as $bucket) {
                    $bucketKey = json_encode(['b' => $bucket, 'labelValues' => $labelValues]);

                    if (isset($raw[$bucketKey]))
                        $acc += $raw[$bucketKey];

                    $histogram['samples'][] = [
                        'name' => $histogram['name'] . '_bucket',
                        'labelNames' => ['le'],
                        'labelValues' => array_merge($labelValues, [$bucket]),
                        'value' => $acc,
                    ];
                }

                $histogram['samples'][] = [
                    'name' => $histogram['name'] . '_count',
                    'labelNames' => [],
                    'labelValues' => $labelValues,
                    'value' => $acc,
                ];

                $histogram['samples'][] = [
                    'name' => $histogram['name'] . '_sum',
                    'labelNames' => [],
                    'labelValues' => $labelValues,
                    'value' => $raw[json_encode(['b' => 'sum', 'labelValues' => $labelValues])],
                ];
            }

            $histograms[] = $histogram;
        }

        return $histograms;
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    private function collectGauges(): array
    {
        $redis = container(RedisService::class)->getRedis();

        $keys = $redis->sMembers(self::PREFIX . Gauge::TYPE . self::SUFFIX);
        sort($keys);
        $gauges = [];

        foreach ($keys as $key) {
            $raw = $redis->hGetAll(str_replace($redis->_prefix(''), '', $key));

            if (!isset($raw['__meta']))
                continue;

            $gauge = json_decode($raw['__meta'], true);
            unset($raw['__meta']);
            $gauge['samples'] = [];
            foreach ($raw as $k => $value) {
                $gauge['samples'][] = [
                    'name' => $gauge['name'],
                    'labelNames' => [],
                    'labelValues' => json_decode($k, true),
                    'value' => $value,
                ];
            }

            usort($gauge['samples'], static fn($a, $b): int => strcmp(implode("", $a['labelValues']), implode("", $b['labelValues'])));

            $gauges[] = $gauge;
        }

        return $gauges;
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    private function collectCounters(): array
    {
        $redis = container(RedisService::class)->getRedis();

        $keys = $redis->sMembers(self::PREFIX . Counter::TYPE . self::SUFFIX);
        sort($keys);
        $counters = [];

        foreach ($keys as $key) {
            $raw = $redis->hGetAll(str_replace($redis->_prefix(''), '', $key));

            if (!isset($raw['__meta']))
                continue;

            $counter = json_decode($raw['__meta'], true);
            unset($raw['__meta']);
            $counter['samples'] = [];
            foreach ($raw as $k => $value) {
                $counter['samples'][] = [
                    'name' => $counter['name'],
                    'labelNames' => [],
                    'labelValues' => json_decode($k, true),
                    'value' => $value,
                ];
            }

            usort($counter['samples'], static fn($a, $b): int => strcmp(implode("", $a['labelValues']), implode("", $b['labelValues'])));

            $counters[] = $counter;
        }
        return $counters;
    }

    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    private function collectSummaries(): array
    {
        $redis = container(RedisService::class)->getRedis();

        $summaryKey = self::PREFIX . Summary::TYPE . self::SUFFIX;

        $keys = $redis->keys($summaryKey . ':*:meta');

        $summaries = [];
        foreach ($keys as $metaKeyWithPrefix) {
            $metaKey = $metaKeyWithPrefix;
            $rawSummary = $redis->get($metaKey);
            if ($rawSummary === false) {
                continue;
            }
            $summary = json_decode($rawSummary, true);
            $metaData = $summary;
            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
                'maxAgeSeconds' => $metaData['maxAgeSeconds'],
                'quantiles' => $metaData['quantiles'],
                'samples' => [],
            ];

            $values = $redis->keys($summaryKey . ':' . $metaData['name'] . ':*:value');
            foreach ($values as $valueKeyWithPrefix) {
                $valueKey = $valueKeyWithPrefix;
                $rawValue = $redis->get($valueKey);
                if ($rawValue === false) {
                    continue;
                }
                $value = json_decode($rawValue, true);
                $encodedLabelValues = $value;
                $decodedLabelValues = $this->decodeLabelValues($encodedLabelValues);

                $samples = [];
                $sampleValues = $redis->keys($summaryKey . ':' . $metaData['name'] . ':' . $encodedLabelValues . ':value:*');
                foreach ($sampleValues as $sampleValueWithPrefix) {
                    $sampleValue = $sampleValueWithPrefix;
                    $samples[] = (float)$redis->get($sampleValue);
                }

                if (count($samples) === 0) {
                    $redis->del($valueKey);
                    continue;
                }

                // Compute quantiles
                sort($samples);
                foreach ($data['quantiles'] as $quantile) {
                    $data['samples'][] = [
                        'name' => $metaData['name'],
                        'labelNames' => ['quantile'],
                        'labelValues' => array_merge($decodedLabelValues, [$quantile]),
                        'value' => $this->quantile($samples, $quantile),
                    ];
                }

                // Add the count
                $data['samples'][] = [
                    'name' => $metaData['name'] . '_count',
                    'labelNames' => [],
                    'labelValues' => $decodedLabelValues,
                    'value' => count($samples),
                ];

                // Add the sum
                $data['samples'][] = [
                    'name' => $metaData['name'] . '_sum',
                    'labelNames' => [],
                    'labelValues' => $decodedLabelValues,
                    'value' => array_sum($samples),
                ];
            }

            if (count($data['samples']) > 0) {
                $summaries[] = $data;
            } else {
                $redis->del($metaKey);
            }
        }

        return $summaries;
    }

    private function quantile(array $arr, float $q): float
    {
        $count = count($arr);
        if ($count === 0) {
            return 0;
        }

        $j = floor($count * $q);
        $r = $count * $q - $j;
        if (0.0 === $r) {
            return $arr[$j - 1];
        }
        return $arr[$j];
    }

    private function toRedisCommand(array $value): string
    {
        return match ($value['command']) {
            Collector::COMMAND_INCREMENT_INTEGER => 'hIncrBy',
            Collector::COMMAND_INCREMENT_FLOAT => 'hIncrByFloat',
            Collector::COMMAND_SET => 'hSet',
            default => throw new InvalidArgumentException('Unknown command')
        };
    }

    private function toMetricKey(array $value): string
    {
        return implode(':', [self::PREFIX, $value['type'], $value['name']]);
    }

    private function encodeLabelValues(array $values): string
    {
        return base64_encode(json_encode($values));
    }

    private function decodeLabelValues(string $values): array
    {
        return json_decode(base64_decode($values), true);
    }

    private function valueKey(array $data): string
    {
        return implode(':', [
            $data['name'],
            $this->encodeLabelValues($data['labelValues']),
            'value'
        ]);
    }

    private function metaData(array $data): array
    {
        $metricsMetaData = $data;

        unset($metricsMetaData['value'], $metricsMetaData['command'], $metricsMetaData['labelValues']);

        return $metricsMetaData;
    }
}