<?php

namespace Selpol\Controller\Api\cameras;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Service\AuthService;

readonly class cameras extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'comment' => rule()->string()->clamp(0, 1000),

            'model' => rule()->string()->in(array_keys(CameraModel::models())),
            'ip' => rule()->string()->clamp(0, 15),

            'device_id' => rule()->string()->clamp(0, 128),
            'device_model' => rule()->string()->clamp(0, 64),
            'device_software_version' => rule()->string()->clamp(0, 64),
            'device_hardware_version' => rule()->string()->clamp(0, 64),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 10000)->nonNullable()]
        ]);

        $criteria = criteria()
            ->like('comment', $validate['comment'])
            ->equal('model', $validate['model'])
            ->like('ip', $validate['ip'])
            ->like('device_id', $validate['device_id'])
            ->like('device_model', $validate['device_model'])
            ->like('device_software_version', $validate['device_software_version'])
            ->like('device_hardware_version', $validate['device_hardware_version'])
            ->asc('camera_id');

        if (!container(AuthService::class)->checkScope('camera-hidden')) {
            $criteria->equal('hidden', false);
        }

        $page = DeviceCamera::fetchPage($validate['page'], $validate['size'], $criteria);

        $result = [];

        foreach ($page->getData() as $data)
            $result[] = $data->toArrayMap([
                'camera_id' => 'cameraId',
                'dvr_server_id' => 'dvr_server_id',
                'frs_server_id' => 'frs_server_id',
                'enabled' => 'enabled',
                'model' => 'model',
                'url' => 'url',
                'stream' => 'stream',
                'credentials' => 'credentials',
                'name' => 'name',
                'dvr_stream' => 'dvrStream',
                'timezone' => 'timezone',
                'lat' => 'lat',
                'lon' => 'lon',
                'common' => 'common',
                'comment' => 'comment',
                'device_id' => 'deviceId',
                'device_model' => 'deviceModel',
                'device_software_version' => 'deviceSoftwareVersion',
                'device_hardware_version' => 'deviceHardwareVersion',
                'config' => 'config',
                'hidden' => 'hidden'
            ]);

        return self::success(new EntityPage($result, $page->getTotal(), $page->getPage(), $page->getSize()));
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Камера] Получить список'];
    }
}