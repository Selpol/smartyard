<?php declare(strict_types=1);

namespace Selpol\Controller\Api\accounts;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreAuth;

readonly class session extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->int()->clamp(0),

            'status' => rule()->int(),

            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        $auths = CoreAuth::fetchPage($validate['page'], $validate['size'], criteria()->equal('user_id', $validate['_id'])->equal('status', $validate['status'])->desc('status'));

        return self::success($auths);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $coreAuth = CoreAuth::findById(rule()->id()->onItem('_id', $params));

        if ($coreAuth) {
            $coreAuth->status = 0;

            return $coreAuth->update() ? self::success() : self::error('Не удалось обновить сессию', 400);
        }

        return self::error('Сессия не найдена', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Пользователь] Получить список сессий', 'PUT' => '[Пользователь] Отключить сессию'];
    }
}