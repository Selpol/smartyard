<?php

namespace Selpol\Controller\Api\sip;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Sip\SipUser;
use Selpol\Entity\Repository\Sip\SipUserRepository;

class user extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, [
            'type' => rule()->int()->clamp(1, 9),
            'title' => rule()->string(),

            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::SUCCESS(
            'servers',
            container(SipUserRepository::class)->fetchPaginate(
                $validate['page'],
                $validate['size'],
                criteria()->orEqual('type', $validate['type'])->orLike('title', $validate['title'])->asc('id')
            )
        );
    }

    public static function POST(array $params): array
    {
        $sipUser = new SipUser(validator($params, [
            'type' => rule()->required()->int()->clamp(1, 9)->nonNullable(),

            'title' => rule()->required()->string()->nonNullable(),

            'password' => rule()->required()->string()->nonNullable()
        ]));

        if (container(SipUserRepository::class)->insert($sipUser))
            return self::SUCCESS('id', $sipUser->id);

        return self::ERROR('Не удалось создать');
    }

    public static function PUT(array $params): array
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'type' => rule()->required()->int()->clamp(1, 9)->nonNullable(),

            'title' => rule()->required()->string()->nonNullable(),

            'password' => rule()->required()->string()->nonNullable()
        ]);

        $sipUser = container(SipUserRepository::class)->findById($validate['_id']);

        $sipUser->type = $validate['type'];
        $sipUser->title = $validate['title'];

        $sipUser->password = $validate['password'];

        if (container(SipUserRepository::class)->update($sipUser))
            return self::SUCCESS('id', $sipUser->id);

        return self::ERROR('Не удалось обновить');
    }

    public static function DELETE(array $params): array
    {
        $id = rule()->id()->onItem('_id', $params);

        $sipUser = container(SipUserRepository::class)->findById($id);

        if (container(SipUserRepository::class)->delete($sipUser))
            return self::SUCCESS('id', $sipUser->id);

        return self::ERROR('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET' => '[SipUser] Получить список', 'POST' => '[SipUser] Добавить пользователя', 'PUT' => '[SipUser] Обновить пользователя', 'DELETE' => '[SipUser] Удалить пользователя'];
    }
}