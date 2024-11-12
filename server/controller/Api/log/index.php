<?php

namespace Selpol\Controller\Api\log;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class index extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        if (array_key_exists('file', $params)) {
            $path = str_replace('..', '', path('var/log/' . $params['file']));

            if (is_file($path)) {
                return self::success(file_get_contents($path));
            }

            return self::error('Файл не найден', 404);
        }

        $path = path('/var/log/');

        return self::success(self::walk($path));
    }

    public static function index(): array
    {
        return ['GET' => '[Логи] Получить логи'];
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