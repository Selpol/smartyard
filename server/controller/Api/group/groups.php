<?php declare(strict_types=1);

namespace Selpol\Controller\Api\group;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Group\GroupFeature;

readonly class groups extends Api
{
    public static function GET(array $params): array|ResponseInterface
    {
        $validate = validator($params, [
            'name' => rule()->string(),
            'type' => rule()->in(['subscriber', 'camera', 'intercom', 'key', 'address']),

            'for' => rule()->in(['contractor', 'address']),
            'id' => rule()->string()->clamp(0, 64),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        $result = container(GroupFeature::class)->find(
            $validate['name'],
            $validate['type'] ? GroupFeature::TYPE_MAP[$validate['type']] : null,
            $validate['for'] ? GroupFeature::FOR_MAP[$validate['for']] : null,
            $validate['id'],
            $validate['page'],
            $validate['size']
        );

        if ($result) {
            return self::success($result);
        }

        return self::success([]);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Группы] Получить список групп'];
    }
}