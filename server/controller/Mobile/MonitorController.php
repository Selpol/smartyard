<?php

declare(strict_types=1);

namespace Selpol\Controller\Mobile;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\MonitorIndexRequest;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Monitor\MonitorFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Service\ZabbixService;

#[Controller('/mobile/monitor')]
readonly class MonitorController extends MobileRbtController
{
    #[Post]
    public function index(MonitorIndexRequest $request, MonitorFeature $feature): ResponseInterface
    {
        $role = $this->getUser()->getOriginalValue()['role'];

        if ($role == 0) {
            return user_response(data: []);
        }

        $result = [];

        foreach ($request->ids as $id) {
            $result[$id] = $feature->status($id);
        }

        return user_response(data: $result);
    }

    #[Get('/{id}')]
    public function show(int $id, ZabbixService $service): ResponseInterface
    {
        $role = $this->getUser()->getOriginalValue()['role'];

        if ($role == 0) {
            return user_response(403, message: 'Ошибка доступа');
        }

        $camera = DeviceCamera::findById($id);

        if (!$camera) {
            return user_response(404, message: 'Не удалось найти камеру');
        }

        if (!$camera->ip) {
            return user_response(404, message: 'Не удалось найти ip');
        }

        $host = $service->host_get($camera->ip);

        if (!$host || count($host) == 0) {
            return user_response(404, message: 'Не удалось найти хост');
        }

        $host = $host[0];

        $trigger = $service->trigger_get($host['hosetid']);

        return user_response(data: ['host' => $host, 'trigger' => $trigger]);
    }
}
