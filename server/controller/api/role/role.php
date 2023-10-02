<?php

namespace api\role;

use api\api;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Validator\Filter;
use Selpol\Validator\Rule;

class role extends api
{
    public static function GET($params)
    {
        return self::SUCCESS('roles', container(RoleFeature::class)->roles());
    }

    public static function POST($params)
    {
        $validate = validator($params, [
            'title' => [Filter::fullSpecialChars(), Rule::required(), Rule::length(), Rule::nonNullable()],
            'description' => [Filter::fullSpecialChars(), Rule::required(), Rule::length(), Rule::nonNullable()]
        ]);

        return parent::ANSWER(container(RoleFeature::class)->createRole($validate['title'], $validate['description']));
    }

    public static function PUT($params)
    {
        $validate = validator($params, [
            '_id' => [Rule::id()],
            'title' => [Filter::fullSpecialChars(), Rule::required(), Rule::length(), Rule::nonNullable()],
            'description' => [Filter::fullSpecialChars(), Rule::required(), Rule::length(), Rule::nonNullable()]
        ]);

        return parent::ANSWER(container(RoleFeature::class)->updateRole($validate['_id'], $validate['title'], $validate['description']));
    }

    public static function DELETE($params)
    {
        $id = Rule::id()->onItem('_id', $params);

        return self::ANSWER(container(RoleFeature::class)->deleteRole($id));
    }

    public static function index(): array|bool
    {
        return [
            'GET' => '#same(role,role,GET)',
            'POST' => '#same(role,role,POST',
            'PUT' => '#same(role,role,PUT',
            'DELETE' => '#same(role,role,DELETE',
        ];
    }
}