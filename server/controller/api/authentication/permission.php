<?php

namespace api\authentication;

use api\api;
use Selpol\Service\AuthService;

class permission extends api
{
    public static function GET($params)
    {
        return self::SUCCESS('permissions', container(AuthService::class)->getPermissions());
    }

    public static function index(): array
    {
        return ['GET'];
    }
}