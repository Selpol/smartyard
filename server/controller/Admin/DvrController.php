<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\DvrShowRequest;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

#[Controller('/admin/dvr')]
readonly class DvrController extends AdminRbtController
{
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

    #[Get('/show/{id}')]
    public function show(DvrShowRequest $request): ResponseInterface
    {
        $id = dvr($request->id)?->getCameraId($request->search);

        if ($id != null) {
            return self::success($id);
        }

        return self::error('Камера не найдена', 404);
    }

    public static function scopes(): array
    {
        return [
            'dvr-index-get' => '[Dvr] Получить список камер на сервере',
            'dvr-show-get' => '[Dvr] Найти идентификатор камеры',
        ];
    }

    private static function sort(array $a, array $b): int
    {
        return strcmp($a['title'], $b['title']);
    }
}