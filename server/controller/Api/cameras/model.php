<?php declare(strict_types=1);

namespace Selpol\Controller\Api\cameras;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Camera\CameraModel;

readonly class model extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success(CameraModel::models());
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Камера] Получить список моделей'];
    }
}