<?php declare(strict_types=1);

namespace Selpol\Controller\Api\accounts;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreAuth;
use Selpol\Framework\Http\Response;

readonly class session extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->int()->clamp(0),

            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        $auths = CoreAuth::fetchPage($validate['page'], $validate['size'], criteria()->equal('user_id', $validate['_id']));

        return self::success($auths);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Пользователь] Получить список сессий'];
    }
}