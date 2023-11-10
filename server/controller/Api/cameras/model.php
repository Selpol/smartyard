<?php declare(strict_types=1);

namespace Selpol\Controller\Api\cameras;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Framework\Http\Response;

readonly class model extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        return ['GET' => '[Камера] Получить список моделей'];
    }
}