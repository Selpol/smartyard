<?php

namespace Selpol\Controller\Api\role;

use Selpol\Controller\Api\api;
use Selpol\Feature\Role\RoleFeature;

class permission extends api
{
    public static function GET(array $params): array
    {
        return self::SUCCESS('permissions', container(RoleFeature::class)->permissions());
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Права] Получить список'];
    }
}