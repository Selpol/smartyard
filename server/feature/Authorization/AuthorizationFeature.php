<?php

namespace Selpol\Feature\Authorization;

use Exception;
use PDO;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Feature;
use Selpol\Service\DatabaseService;

abstract class AuthorizationFeature extends Feature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function methods($_all = true): bool|array
    {
        $db = container(DatabaseService::class);

        $m = [];

        try {
            if ($_all) {
                $all = $db->getConnection()->query("select aid, api, method, request_method from core_api_methods", PDO::FETCH_ASSOC)->fetchAll();
            } else {
                $all = $db->getConnection()->query("
                            select
                                aid,
                                api,
                                method,
                                request_method
                            from
                                core_api_methods
                            where
                                aid not in (select aid from core_api_methods_common) and 
                                aid not in (select aid from core_api_methods_by_backend) and
                                coalesce(permissions_same, '') = ''
                        ", PDO::FETCH_ASSOC)->fetchAll();
            }

            foreach ($all as $a)
                $m[$a['api']][$a['method']][$a['request_method']] = $a['aid'];
        } catch (Exception $e) {
            error_log(print_r($e, true));

            return false;
        }

        return $m;
    }

    abstract public function allowedMethods(int $uid): array;
}