<?php declare(strict_types=1);

namespace Selpol\Controller\Api\config;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Service\DatabaseService;

readonly class config extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        if ($params['_id'] == 'intercom') {
            $models = IntercomModel::models();

            /** @var array<string, string> $intercoms */
            $intercoms = array_reduce(container(DatabaseService::class)->get('SELECT model, device_model FROM houses_domophones GROUP BY model, device_model'), static function (array $previous, array $current): array {
                $previous[$current['model']] = $current['device_model'];

                return $previous;
            }, []);

            $values = [];

            foreach ($models as $key => $model) {
                if (!array_key_exists($model->vendor, $values)) {
                    $values[$model->vendor] = [];
                }

                if (!array_key_exists($model->title, $values[$model->vendor])) {
                    $values[$model->vendor][] = ['type' => 'title', 'value' => $model->title];
                }

                if (array_key_exists($key, $intercoms)) {
                    $value = $intercoms[$key];

                    if ($value == null || $value == '') {
                        continue;
                    }

                    $values[$model->vendor][] = ['type' => 'model', 'value' => $value];

                    if (str_contains($value, '_rev')) {
                        $segments = explode('_rev', $value);

                        if (count($segments) > 1) {
                            $values[$model->vendor][] = ['type' => 'model', 'value' => $segments[0]];
                        }
                    }
                }
            }

            return self::success([
                'values' => $values,
                'items' => container(ConfigFeature::class)->getDescriptionForIntercomConfig()
            ]);
        }

        return self::error('Не удалось найти параметры конфигурации');
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Конфигурация] Получить параметры конфигурации'];
    }
}