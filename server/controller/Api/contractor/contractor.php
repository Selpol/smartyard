<?php declare(strict_types=1);

namespace Selpol\Controller\Api\contractor;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class contractor extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'title' => rule()->string()->clamp(0, 1000),
            'flat' => rule()->int()->clamp(0, 10000),

            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::success(\Selpol\Entity\Model\Contractor::fetchPage($validate['page'], $validate['size'], criteria()->like('title', $validate['title'])->equal('flat', $validate['flat'])->asc('id')));
    }

    public static function POST(array $params): ResponseInterface
    {
        $contractor = new \Selpol\Entity\Model\Contractor(validator($params, [
            'title' => rule()->required()->string()->clamp(0, 1000)->nonNullable(),
            'flat' => rule()->required()->int()->clamp(0, 10000)->nonNullable()
        ]));

        if ($contractor->insert())
            return self::success($contractor->id);

        return self::error('Не удалось создать подрядчика', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'title' => rule()->required()->string()->clamp(0, 1000)->nonNullable(),
            'flat' => rule()->required()->int()->clamp(0, 10000)->nonNullable()
        ]);

        $contractor = \Selpol\Entity\Model\Contractor::findById($validate['_id'], setting: setting()->nonNullable());

        $contractor->title = $validate['title'];
        $contractor->flat = $validate['flat'];

        if ($contractor->update())
            return self::success($contractor->id);

        return self::error('Не удалось обновить подрядчика', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $contractor = \Selpol\Entity\Model\Contractor::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($contractor->delete())
            return self::success();

        return self::error('Не удалось удалить подрядчика', 400);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Подрядчики] Получить подрядчика', 'POST' => '[Подрядчики] Создать подрядчика', 'PUT' => '[Подрядчики] Обновить подрядчика', 'DELETE' => '[Подрядчики] Удалить подрядчика'];
    }
}