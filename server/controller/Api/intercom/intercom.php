<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Service\AuthService;
use Selpol\Service\DeviceService;
use Throwable;

readonly class intercom extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $criteria = criteria();

        if (!container(AuthService::class)->checkScope('intercom-hidden')) {
            $criteria->equal('hidden', false);
        }

        $intercom = DeviceIntercom::findById($params['_id'], $criteria, setting()->nonNullable())->toArrayMap([
            'house_domophone_id' => 'domophoneId',
            'enabled' => 'enabled',
            'model' => 'model',
            'server' => 'server',
            'url' => 'url',
            'credentials' => 'credentials',
            'dtmf' => 'dtmf',
            'first_time' => 'firstTime',
            'nat' => 'nat',
            'comment' => 'comment',
            'ip' => 'ip',
            'sos_number' => 'sosNumber',
            'device_id' => 'deviceId',
            'device_model' => 'deviceModel',
            'device_software_version' => 'deviceSoftwareVersion',
            'device_hardware_version' => 'deviceHardwareVersion',
            'config' => 'config',
            'hidden' => 'hidden'
        ]);

        $intercom['json'] = IntercomModel::models()[$intercom['model']]->toArray();

        return self::success($intercom);
    }

    public static function POST(array $params): ResponseInterface
    {
        $intercom = new DeviceIntercom();

        self::set($intercom, $params);

        if ($intercom->safeInsert()) {
            return self::success($intercom->house_domophone_id);
        }

        return self::error('Не удалось создать домофон', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $intercom = DeviceIntercom::findById($params['_id'], setting: setting()->nonNullable());

        self::set($intercom, $params);

        if ($intercom->safeUpdate()) {
            container(ConfigFeature::class)->clearCacheConfigForIntercom($intercom->house_domophone_id);

            return self::success($intercom->house_domophone_id);
        }

        return self::error('Не удалось обновить домофон', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $intercom = DeviceIntercom::findById($params['_id'], setting: setting()->nonNullable());

        if ($intercom->safeDelete()) {
            return self::success();
        }

        return self::error('Не удалось далить домофон', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Домофон] Получить домофон', 'PUT' => '[Домофон] Обновить домофон', 'POST' => '[Домофон] Создать домофон', 'DELETE' => '[Домофон] Удалить домофон'];
    }

    private static function set(DeviceIntercom $intercom, array $params): void
    {
        $intercom->enabled = $params['enabled'];

        $intercom->model = $params['model'];
        $intercom->server = $params['server'];
        $intercom->url = $params['url'];
        $intercom->credentials = $params['credentials'];
        $intercom->dtmf = $params['dtmf'];

        if (array_key_exists('firstTime', $params)) {
            $intercom->first_time = $params['firstTime'];
        }

        $intercom->nat = $params['nat'];

        $intercom->comment = $params['comment'];

        if (array_key_exists('sosNumber', $params)) {
            $intercom->sos_number = $params['sosNumber'];
        }

        if (array_key_exists('config', $params)) {
            $intercom->config = $params['config'];
        }

        if (array_key_exists('hidden', $params)) {
            $intercom->hidden = $params['hidden'];
        }

        $ip = gethostbyname(parse_url($intercom->url, PHP_URL_HOST));

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            $intercom->ip = $ip;
        }

        try {
            $device = container(DeviceService::class)->intercomByEntity($intercom);

            if ($device) {
                $info = $device->getSysInfo();

                $intercom->device_id = $info->deviceId;
                $intercom->device_model = $info->deviceModel;
                $intercom->device_software_version = $info->softwareVersion;
                $intercom->device_hardware_version = $info->hardwareVersion;
            }
        } catch (Throwable) {

        }
    }
}