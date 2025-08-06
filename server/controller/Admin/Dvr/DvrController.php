<?php

declare(strict_types=1);

namespace Selpol\Controller\Admin\Dvr;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\DvrShowRequest;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Управление DVR серверами
 */
#[Controller('/admin/dvr')]
readonly class DvrController extends AdminRbtController
{
    /**
     * Получить камеры с DVR сервера
     *
     * @param int $id Идентификатор DVR сервера
     */
    #[Get('/{id}')]
    public function index(int $id): ResponseInterface
    {
        $cameras = dvr($id)?->getCameras();

        if ($cameras !== null && $cameras !== []) {
            usort($cameras, self::sort(...));

            return self::success($cameras);
        }

        return self::success([]);
    }

    /**
     * Получить камеру с сервера
     */
    #[Get('/show/{id}')]
    public function show(DvrShowRequest $request): ResponseInterface
    {
        $camera = dvr($request->id)?->getCamera($request->camera);

        if ($camera) {
            return self::success($camera);
        }

        return self::error('Камера не найдена', 404);
    }

    private static function sort(array $a, array $b): int
    {
        return strcmp($a['title'], $b['title']);
    }
}
