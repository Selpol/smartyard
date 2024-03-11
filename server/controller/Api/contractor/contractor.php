<?php declare(strict_types=1);

namespace Selpol\Controller\Api\contractor;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Group\GroupFeature;

readonly class contractor extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $contractor = \Selpol\Entity\Model\Contractor::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($contractor)
            return self::success($contractor);

        return self::error('Не удалось найти подрядчика');
    }

    public static function POST(array $params): ResponseInterface
    {
        $contractor = new \Selpol\Entity\Model\Contractor(validator($params, [
            'title' => rule()->required()->string()->clamp(0, 1000)->nonNullable(),
            'flat' => rule()->required()->int()->clamp(0, 10000)->nonNullable(),
            'code' => rule()->string()
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
            'flat' => rule()->required()->int()->clamp(0, 10000)->nonNullable(),
            'code' => rule()->string()
        ]);

        $contractor = \Selpol\Entity\Model\Contractor::findById($validate['_id'], setting: setting()->nonNullable());

        $contractor->title = $validate['title'];
        $contractor->flat = $validate['flat'];
        $contractor->code = $validate['code'];

        if ($contractor->update())
            return self::success($contractor->id);

        return self::error('Не удалось обновить подрядчика', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $contractor = \Selpol\Entity\Model\Contractor::findById(rule()->id()->onItem('_id', $params), setting: setting()->columns(['id'])->nonNullable());

        container(GroupFeature::class)->deleteFor(GroupFeature::FOR_CONTRACTOR, $contractor->id);

        if ($contractor->delete())
            return self::success();

        return self::error('Не удалось удалить подрядчика', 400);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Подрядчики] Получить подрядчика', 'POST' => '[Подрядчики] Создать подрядчика', 'PUT' => '[Подрядчики] Обновить подрядчика', 'DELETE' => '[Подрядчики] Удалить подрядчика'];
    }
}