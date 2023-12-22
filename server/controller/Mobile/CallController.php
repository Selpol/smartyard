<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Service\DeviceService;
use Selpol\Service\RedisService;

#[Controller('/mobile/call', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_CALL]])]
readonly class CallController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/camshot/{hash}')]
    public function camshot(string $hash, RedisService $redisService): Response
    {
        $this->getUser();

        $image = $redisService->get('shot_' . $hash);

        if ($image !== false)
            return response()
                ->withHeader('Content-Type', 'image/jpeg')
                ->withBody(stream($image));

        return user_response(404, message: 'Скриншот не найден');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/live/{hash}')]
    public function live(string $hash, RedisService $redisService, DeviceService $deviceService): Response
    {
        $this->getUser();

        $json_camera = $redisService->get("live_" . $hash);
        $camera_params = json_decode($json_camera, true);

        $model = $deviceService->camera($camera_params["model"], $camera_params["url"], $camera_params["credentials"]);

        if (!$model)
            return user_response(404, message: 'Камера не найдена');

        return response(headers: ['Content-Type' => ['image/jpeg']])->withBody($model->getScreenshot());
    }
}