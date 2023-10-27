<?php

namespace Selpol\Controller\Api\role;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Role\RoleFeature;

readonly class permission extends Api
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