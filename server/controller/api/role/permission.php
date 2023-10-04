<?php

namespace api\role;

use api\api;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Validator\Filter;
use Selpol\Validator\Rule;

class permission extends api
{
    public static function GET($params)
    {
        return self::SUCCESS('permissions', container(RoleFeature::class)->permissions());
    }

    public static function PUT($params)
    {
        $validate = validator($params, [
            '_id' => [Rule::id()],
            'description' => [Filter::fullSpecialChars(), Rule::required(), Rule::length(), Rule::nonNullable()]
        ]);

        return parent::ANSWER(container(RoleFeature::class)->updatePermission($validate['_id'], $validate['description']));
    }

    public static function index(): array|bool
    {
        return ['GET' => '#same(role,permission,GET)', 'PUT' => '#same(role,permission,PUT)'];
    }
}