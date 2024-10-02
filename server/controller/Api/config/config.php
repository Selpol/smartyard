<?php declare(strict_types=1);

namespace Selpol\Controller\Api\config;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Service\DatabaseService;

readonly class config extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        if ($params['_id'] == 'intercom') {
            $intercoms = container(DatabaseService::class)->get('SELECT DISTINCT device_model FROM houses_domophones');
            $models = IntercomModel::models();

            return self::success([
                'models' => array_reduce(array_values(array_unique(array_filter(array_map('trim', array_map(static fn(array $intercom) => $intercom['device_model'], $intercoms)), static fn(?string $value) => $value !== null && $value != ''))), static function (array $previous, string $current) {
                    if (str_contains($current, '_rev')) {
                        $segments = explode('_rev', $current);

                        if (count($segments) > 1) {
                            $previous[] = $segments[0];
                        }
                    }

                    $previous[] = $current;

                    return $previous;
                }, []),

                'titles' => array_values(array_unique(array_values(array_map(static fn(IntercomModel $model) => $model->title, $models)))),
                'vendors' => array_values(array_unique(array_values(array_map(static fn(IntercomModel $model) => $model->vendor, $models)))),

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