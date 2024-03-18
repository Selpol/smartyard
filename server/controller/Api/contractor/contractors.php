<?php declare(strict_types=1);

namespace Selpol\Controller\Api\contractor;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Contractor;
use Selpol\Framework\Http\Response;

readonly class contractors extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $validate = validator($params, [
            'title' => rule()->string()->clamp(0, 1000),
            'flat' => rule()->int()->clamp(0, 10000),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        return self::success(Contractor::fetchPage($validate['page'], $validate['size'], criteria()->like('title', $validate['title'])->equal('flat', $validate['flat'])->asc('id')));
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Подрядчики] Получить список подрядчиков'];
    }
}