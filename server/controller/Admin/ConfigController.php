<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\ConfigIndexRequest;
use Selpol\Controller\Request\Admin\ConfigOptimizeRequest;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\DatabaseService;

#[Controller('/admin/config')]
readonly class ConfigController extends AdminRbtController
{
    #[Get('/suggestion/{type}')]
    public function suggestion(ConfigIndexRequest $request, DatabaseService $database, ConfigFeature $configFeature): ResponseInterface
    {
        if ($request->type == 'intercom') {
            $models = IntercomModel::models();

            /** @var array<string, string> $intercoms */
            $intercoms = array_reduce($database->get('SELECT model, device_model FROM houses_domophones GROUP BY model, device_model'), static function (array $previous, array $current): array {
                $previous[$current['model']] = $current['device_model'];

                return $previous;
            }, []);

            $values = [];

            foreach ($models as $key => $model) {
                if (!array_key_exists($model->vendor, $values)) {
                    $values[$model->vendor] = [
                        'value' => $model->vendor,
                        'title' => 'Производитель домофона',

                        'suggestions' => []
                    ];
                }

                $values[$model->vendor]['suggestions'][] = ['value' => str_replace('.', '', $model->title), 'title' => 'Модель'];

                if (array_key_exists($key, $intercoms)) {
                    $value = $intercoms[$key];

                    if ($value === null) {
                        continue;
                    }

                    if ($value === '') {
                        continue;
                    }

                    $values[$model->vendor]['suggestions'][] = ['value' => strtoupper(str_replace('.', '', $value)), 'title' => 'Ревизия'];

                    if (str_contains($value, '_rev')) {
                        $segments = explode('_rev', $value);

                        if (count($segments) > 1) {
                            $values[$model->vendor]['suggestions'][] = ['value' => strtoupper(str_replace('.', '', $segments[0])), 'title' => 'Ревизия'];
                        }
                    }
                }
            }

            return self::success([
                'suggestions' => $configFeature->getSuggestionsForIntercomConfig(),

                'container_suggestions' => [
                    [
                        'value' => 'intercom',
                        'title' => 'Домофон',

                        'suggestions' => array_values($values)
                    ]
                ]
            ]);
        } else if ($request->type === 'camera') {
            $models = CameraModel::models();

            $values = [];

            foreach ($models as $model) {
                if (!array_key_exists($model->vendor, $values)) {
                    $values[$model->vendor] = [
                        'value' => $model->vendor,
                        'title' => 'Производитель камеры',

                        'suggestions' => []
                    ];
                }

                $values[$model->vendor]['suggestions'][] = ['value' => str_replace('.', '', $model->title), 'title' => 'Модель'];
            }

            return self::success([
                'suggestions' => $configFeature->getSuggestionsForCameraConfig(),

                'container_suggestions' => [
                    [
                        'value' => 'camera',
                        'title' => 'Камера',

                        'suggestions' => array_values($values)
                    ]
                ]
            ]);
        }

        return self::error('Не удалось найти параметры конфигурации');
    }

    #[Get('/intercom/{id}')]
    public function intercom(ConfigOptimizeRequest $request, ConfigFeature $feature): ResponseInterface
    {
        $intercom = intercom($request->id);

        if ($intercom instanceof IntercomDevice) {
            $values = ($request->optimize ? $feature->getOptimizeConfigForIntercom($intercom->model, $intercom->intercom) : $feature->getConfigForIntercom($intercom->model, $intercom->intercom))->getValues();

            return self::success(array_reduce(array_keys($values), static function (string $previous, string $key) use ($values): string {
                return $previous . $key . '=' . $values[$key] . PHP_EOL;
            }, ''));
        }

        return self::error('Не удалось найти параметры конфигурации');
    }

    #[Get('/camera/{id}')]
    public function camera(ConfigOptimizeRequest $request, ConfigFeature $feature): ResponseInterface
    {
        $camera = camera($request->id);

        if ($camera instanceof CameraDevice) {
            $values = ($request->optimize ? $feature->getOptimizeConfigForCamera($camera->model, $camera->camera) : $feature->getConfigForCamera($camera->model, $camera->camera))->getValues();

            return self::success(array_reduce(array_keys($values), static function (string $previous, string $key) use ($values): string {
                return $previous . $key . '=' . $values[$key] . PHP_EOL;
            }, ''));
        }

        return self::error('Не удалось найти параметры конфигурации');
    }
}