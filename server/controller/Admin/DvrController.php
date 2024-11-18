<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

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
     * Найти камеру на DVR сервере
     */
    #[Get('/show/{id}')]
    public function show(DvrShowRequest $request): ResponseInterface
    {
        $id = dvr($request->id)?->getCameraId($request->search);

        if ($id != null) {
            return self::success($id);
        }

        return self::error('Камера не найдена', 404);
    }

    private static function sort(array $a, array $b): int
    {
        return strcmp($a['title'], $b['title']);
    }
}