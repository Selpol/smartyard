<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Framework\Http\Response;

readonly class variable extends Api
{
    public static function GET(array $params): array|Response
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::SUCCESS('variables', CoreVar::fetchPage($validate['page'], $validate['size'], criteria()->equal('hidden', false)->asc('var_id')));
    }

    public static function PUT(array $params): array|Response
    {
        $coreVar = CoreVar::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($coreVar) {
            if (!$coreVar->editable)
                return self::ERROR('Переменную нельзя изменить');

            $coreVar->var_value = rule()->string()->onItem('var_value', $params);

            return self::ANSWER($coreVar->update());
        }

        return self::ERROR('Переменная не найдена');
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Переменные] Получить список переменных', 'PUT' => '[Переменные] Обновить переменную'];
    }
}