<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\Core\CoreVarRepository;
use Throwable;

readonly class version extends Api
{
    public static function GET(array $params): array
    {
        try {
            $var = container(CoreVarRepository::class)->fetch(criteria()->equal('var_name', 'dbVersion'));

            $version = intval($var->var_value);
        } catch (Throwable) {
            $version = 0;
        }

        return self::ANSWER(['version' => '0.0.0', 'dbVersion' => $version]);
    }

    public static function index(): array
    {
        return ["GET" => '[Сервер] Версия базы данных'];
    }
}