<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Exception;
use Selpol\Entity\Repository\Core\CoreVarRepository;

class version extends Api
{
    public static function GET(array $params): array
    {
        try {
            $var = container(CoreVarRepository::class)->fetchRaw('SELECT * FROM core_vars WHERE var_name = :var_name', ['var_name' => 'dbVersion']);

            $version = intval($var->var_value);
        } catch (Exception) {
            $version = 0;
        }

        return [
            "200" => [
                "serverVersion" => $version,
            ]
        ];
    }

    public static function index(): array
    {
        return ["GET" => '[Сервер] Версия базы данных'];
    }
}