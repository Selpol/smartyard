<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Http\Response;
use Selpol\Service\DeviceService;

readonly class info extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $deviceIntercom = DeviceIntercom::findById(rule()->id()->onItem('_id', $params));

        if (!$deviceIntercom instanceof DeviceIntercom) {
            return self::error('Не удалось найти домофон', 404);
        }

        $device = container(DeviceService::class)->intercomByEntity($deviceIntercom);

        if ($device) {
            $info = $device->getSysInfo();

            $deviceIntercom->device_id = $info['DeviceID'];
            $deviceIntercom->device_model = $info['DeviceModel'];
            $deviceIntercom->device_software_version = $info['SoftwareVersion'];
            $deviceIntercom->device_hardware_version = $info['HardwareVersion'];

            $deviceIntercom->update();

            $info['cms'] = explode(',', $device->resolveString('cms.value', ''));
            $info['output'] = $device->resolveInt('output', 1);

            return self::success($info);
        }

        return self::error('Не удалось найти устройство', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Получить информацию об устройстве'];
    }
}