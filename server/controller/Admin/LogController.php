<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\LogIndexRequest;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

#[Controller('/admin/log')]
readonly class LogController extends AdminRbtController
{
    #[Get]
    public function index(LogIndexRequest $request): ResponseInterface
    {
        if ($request->file) {
            $path = realpath(path('var/log/' . $request->file));

            if ($path !== path('var/log/' . $request->file)) {
                return self::error('Путь ' . $path . ' не является достоверным', 400);
            }

            if (is_file($path)) {
                return self::success(file_get_contents($path));
            }

            return self::error('Файл не найден', 404);
        }

        $path = path('/var/log/');

        return self::success(self::walk($path));
    }

    private static function walk(string $path): array
    {
        $files = scandir($path);

        if ($files === false) {
            return [];
        }

        $result = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $subPath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_file($subPath)) {
                if (str_ends_with($file, '.log')) {
                    $result[] = ['name' => $file];
                }
            } else if (is_dir($subPath)) {
                $result[] = ['name' => $file, 'children' => self::walk($subPath)];
            }
        }

        return $result;
    }
}