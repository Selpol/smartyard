<?php

/**
 * @api {get} /server/version get version
 *
 * @apiVersion 1.0.0
 *
 * @apiName version
 * @apiGroup server
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiSuccess {String} serverVersion server version
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "serverVersion": "1"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X GET http://127.0.0.1:8000/server/api.php/server/version
 */

/**
 * server api
 */

namespace api\server {

    use api\api;
    use Exception;
    use Selpol\Service\DatabaseService;

    /**
     * version method
     */
    class version extends api
    {

        public static function GET($params)
        {
            try {
                $version = container(DatabaseService::class)->getConnection()->query("select var_value from core_vars where var_name = 'dbVersion'", \PDO::FETCH_ASSOC)->fetch()["var_value"];
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
}