<?php

namespace Selpol\Controller\Api\role;

use Selpol\Controller\Api\Api;

readonly class permission extends Api
{
    public static function GET(array $params): array
    {
        return self::SUCCESS('permissions', \Selpol\Entity\Model\Permission::fetchAll());
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Права] Получить список'];
    }
}