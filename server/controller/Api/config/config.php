<?php declare(strict_types=1);

namespace Selpol\Controller\Api\config;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigFeature;

readonly class config extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        if ($params['_id'] == 'intercom') {
            $intercoms = DeviceIntercom::fetchAll(setting: setting()->columns(['house_domophone_id', 'device_model']));

            $models = IntercomModel::models();

            return self::success([
                'ids' => array_map(static fn(DeviceIntercom $intercom) => $intercom->house_domophone_id, $intercoms),
                'models' => array_values(array_unique(array_filter(array_map(static fn(DeviceIntercom $intercom) => $intercom->device_model, $intercoms), static fn(string $value) => $value != ''))),

                'titles' => array_map(static fn(IntercomModel $model) => $model->title, $models),
                'vendors' => array_map(static fn(IntercomModel $model) => $model->vendor, $models),

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