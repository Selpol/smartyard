<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Http\Response;
use Selpol\Service\DeviceService;
use Throwable;

readonly class info extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $device = DeviceIntercom::findById(rule()->id()->onItem('_id', $params));

        if (!$device)
            return self::error('Не удалось найти домофон', 404);

        $intercom = container(DeviceService::class)->intercomByEntity($device);

        if ($intercom) {
            $info = $intercom->getSysInfo();

            try {
                exec('arp ' . $device->ip . ' | grep ' . $device->ip . ' | awk \'{print $3}\'', $output);

                return self::success(['info' => $info, 'mac' => $output]);
            } catch (Throwable) {
                return self::success(['info' => $info, 'mac' => []]);
            }
        }

        return self::error('Не удалось найти устройство', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Получить информацию об устройстве'];
    }
}