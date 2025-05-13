<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\RateLimitMiddleware;
use Selpol\Service\DeviceService;
use Selpol\Service\RedisService;

#[Controller('/mobile/call', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]], excludes: [RateLimitMiddleware::class])]
readonly class CallController extends MobileRbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/camshot/{hash}')]
    public function camshot(string $hash, RedisService $redisService, DeviceService $deviceService): Response
    {
        $this->getUser();

        $call = json_decode($redisService->get('call/hash/' . $hash), true);

        if (!$call) {
            return user_response(404, message: 'Неизвестный звонок');
        }

        $entrances = HouseEntrance::fetchAll(criteria()->equal('house_domophone_id', $call['domophone'])->equal('domophone_output', 0), setting()->columns(['camera_id']));

        if (count($entrances) == 0) {
            return user_response(404, message: 'Вход не найден');
        }

        $camera = $deviceService->cameraById($entrances[0]->camera_id);

        if (!$camera) {
            return user_response(404, message: 'Камера не найдена');
        }

        return response(headers: ['Content-Type' => ['image/jpeg']])->withBody($camera->getScreenshot());
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/live/{hash}')]
    public function live(string $hash, RedisService $redisService, DeviceService $deviceService): Response
    {
        return $this->camshot($hash, $redisService, $deviceService);
    }
}