<?php declare(strict_types=1);

namespace Selpol\Controller\Api\config;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Config\ConfigFeature;

readonly class config extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        if ($params['_id'] == 'intercom') {
            return self::success(container(ConfigFeature::class)->getConfigForIntercomArray());
        }

        return self::error('Не удалось найти параметры конфигурации');
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Подрядчики] Получить параметры конфигурации'];
    }
}