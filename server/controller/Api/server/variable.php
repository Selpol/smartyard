<?php

namespace Selpol\Controller\Api\server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreVar;

readonly class variable extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::success(CoreVar::fetchPage($validate['page'], $validate['size'], criteria()->equal('hidden', false)->asc('var_id')));
    }

    public static function PUT(array $params): ResponseInterface
    {
        $coreVar = CoreVar::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($coreVar) {
            if (!$coreVar->editable)
                return self::error('Переменную нельзя изменить', 400);

            $coreVar->var_value = rule()->string()->onItem('var_value', $params);

            if ($coreVar->update())
                return self::success($coreVar->var_id);

            return self::error('Не удалось обновить переменную', 400);
        }

        return self::error('Переменная не найдена', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Переменные] Получить список переменных', 'PUT' => '[Переменные] Обновить переменную'];
    }
}