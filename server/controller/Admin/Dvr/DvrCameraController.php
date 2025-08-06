<?php

declare(strict_types=1);

namespace Selpol\Controller\Admin\Dvr;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\DvrCameraShowRequest;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * DVR-Камера
 */
#[Controller('/admin/dvr/camera')]
readonly class DvrCameraController extends AdminRbtController
{
    /**
     * Найти камеру на DVR сервере
     */
    #[Get('/{id}')]
    public function show(DvrCameraShowRequest $request): ResponseInterface
    {
        $camera = dvr($request->id)?->getCamera($request->camera);

        if ($camera) {
            return self::success($camera);
        }

        return self::error('Камера не найдена', 404);
    }
}
