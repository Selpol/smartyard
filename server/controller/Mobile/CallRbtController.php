<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Controller\RbtController;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\DeviceService;
use Selpol\Service\RedisService;

#[Controller(('/mobile/call'))]
readonly class CallRbtController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    #[Get('/camshot/{hash}')]
    public function camshot(string $hash): Response
    {
        $this->getUser();

        $image = container(RedisService::class)->getConnection()->get('shot_' . $hash);

        if ($image !== false)
            return response()
                ->withHeader('Content-Type', 'image/jpeg')
                ->withBody(stream($image));

        return user_response(404, message: 'Скриншот не найден');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    #[Get('/live/{hash}')]
    public function live(string $hash): Response
    {
        $this->getUser();

        $json_camera = container(RedisService::class)->getConnection()->get("live_" . $hash);
        $camera_params = json_decode($json_camera, true);

        $model = container(DeviceService::class)->camera($camera_params["model"], $camera_params["url"], $camera_params["credentials"]);

        if (!$model)
            return user_response(404, message: 'Камера не найдена');

        return response(headers: ['Content-Type' => 'image/jpeg'])->withBody($model->getScreenshot());
    }
}