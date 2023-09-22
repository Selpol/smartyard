<?php

/**
 * @api {get} /authorization/methods get all methods available on server
 *
 * @apiVersion 1.0.0
 *
 * @apiName methods
 * @apiGroup authorization
 *
 * @apiHeader {string} token authentication token
 */

/**
 * authorization api
 */

namespace api\authorization {

    use api\api;
    use Selpol\Feature\Authorization\AuthorizationFeature;

    /**
     * available method
     */
    class methods extends api
    {
        public static function GET($params)
        {
            $methods = container(AuthorizationFeature::class)->methods($params["all"]);

            return api::ANSWER($methods, ($methods !== false) ? "methods" : "notFound");
        }

        public static function index()
        {
            return [
                "GET" => "#common",
            ];
        }
    }
}