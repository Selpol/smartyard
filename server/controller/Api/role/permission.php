<?php

namespace Selpol\Controller\Api\role;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class permission extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success(\Selpol\Entity\Model\Permission::fetchAll(criteria()->asc('title'), setting: setting()->columns(['id', 'title', 'description'])));
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Права] Получить список'];
    }
}