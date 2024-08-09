<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Service\AuthService;

readonly class intercoms extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'comment' => rule()->string()->clamp(0, 1000),

            'ip' => rule()->string()->clamp(0, 15),

            'device_id' => rule()->string()->clamp(0, 128),
            'device_model' => rule()->string()->clamp(0, 64),
            'device_software_version' => rule()->string()->clamp(0, 64),
            'device_hardware_version' => rule()->string()->clamp(0, 64),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        $criteria = criteria()
            ->like('comment', $validate['comment'])
            ->like('ip', $validate['ip'])
            ->like('device_id', $validate['device_id'])
            ->like('device_model', $validate['device_model'])
            ->like('device_software_version', $validate['device_software_version'])
            ->like('device_hardware_version', $validate['device_hardware_version'])
            ->asc('house_domophone_id');

        if (!container(AuthService::class)->checkScope('intercom-hidden'))
            $criteria->equal('hidden', false);

        $page = DeviceIntercom::fetchPage($validate['page'], $validate['size'], $criteria);

        $result = [];

        foreach ($page->getData() as $data)
            $result[] = $data->toArrayMap([
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
                'hidden' => 'hidden'
            ]);

        return self::success(new EntityPage($result, $page->getTotal(), $page->getPage(), $page->getSize()));
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Домофон] Получить список домофонов'];
    }
}