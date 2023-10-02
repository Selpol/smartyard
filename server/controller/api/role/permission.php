<?php

namespace api\role;

use api\api;
use Selpol\Feature\Role\RoleFeature;

class permission extends api
{
    public static function GET($params)
    {
        return self::SUCCESS('permissions', container(RoleFeature::class)->permissions());
    }

    public static function index(): array|bool
    {
        return ['GET' => '#same(role,permissions,GET)'];
    }
}