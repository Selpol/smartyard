<?php declare(strict_types=1);

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Framework\Http\Response;

readonly class model extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        return self::success(IntercomModel::models());
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Получить список моделей'];
    }
}