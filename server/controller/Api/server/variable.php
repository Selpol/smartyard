<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Framework\Http\Response;

readonly class variable extends Api
{
    public static function GET(array $params): array|Response
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::SUCCESS('variables', container(CoreVarRepository::class)->fetchPage($validate['page'], $validate['size'], criteria()->equal('hidden', false)->asc('var_id')));
    }

    public static function PUT(array $params): array|Response
    {
        $coreVar = container(CoreVarRepository::class)->findById(rule()->id()->onItem('_id', $params));

        if ($coreVar) {
            if (!$coreVar->editable)
                return self::ERROR('Переменную нельзя изменить');

            $coreVar->var_value = rule()->string()->onItem('var_value', $params);

            return self::ANSWER(container(CoreVarRepository::class)->update($coreVar));
        }

        return self::ERROR('Переменная не найдена');
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Переменные] Получить список переменных', 'PUT' => '[Переменные] Обновить переменную'];
    }
}