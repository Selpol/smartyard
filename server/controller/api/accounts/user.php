<?php

/**
 * @api {get} /accounts/user/:uid get user
 *
 * @apiVersion 1.0.0
 *
 * @apiName getUser
 * @apiGroup users
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} uid user id
 *
 * @apiSuccess {Object} user user info
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "user": {
 *          "uid": 1,
 *          "login": "my_login",
 *          "realName": "my_real_password",
 *          "eMail": "my_email",
 *          "phone": "my_phone",
 *          "groups": [
 *              1,2,3
 *          ]
 *      }
 *  }
 *
 * @apiError userNotFound user not found
 * @apiError forbidden access denied
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "userNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl http://127.0.0.1:8000/server/api.php/accounts/user/1
 */

/**
 * @api {post} /accounts/user create user
 *
 * @apiVersion 1.0.0
 *
 * @apiName createUser
 * @apiGroup users
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {string} login login
 * @apiParam {string} password password
 * @apiParam {string} realName real name
 * @apiParam {string} eMail e-mail
 * @apiParam {string} phone phone
 *
 * @apiSuccess {Number} uid user id
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "uid": 1
 *  }
 *
 * @apiError invalidLogin invalid login
 * @apiError invalidPassword invalid password
 * @apiError invalidPhone invalid phone
 * @apiError invalidEMail invalid e-mail
 * @apiError forbidden access denied
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 406 Not Acceptable
 *  {
 *      "error": "invalidPhone"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X POST http://127.0.0.1:8000/server/api.php/accounts/user \
 *      -H 'Content-Type: application/json' \
 *      -d '{"login":"my_login","password":"my_password","realName":"my_real_name","eMail":"my_email","phone":"my_phone"}'
 */

/**
 * @api {put} /accounts/user/:uid update user
 *
 * @apiVersion 1.0.0
 *
 * @apiName updateUser
 * @apiGroup users
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} uid user id
 * @apiParam {string} login login
 * @apiParam {string} password password
 * @apiParam {string} realName real name
 * @apiParam {string} eMail e-mail
 * @apiParam {string} phone phone
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 204 OK
 *
 * @apiError userNotFound user not found
 * @apiError invalidLogin invalid login
 * @apiError invalidPassword invalid password
 * @apiError invalidPhone invalid phone
 * @apiError invalidEMail invalid e-mail
 * @apiError forbidden access denied
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "userNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X PUT http://127.0.0.1:8000/server/api.php/accounts/user/1 \
 *      -H 'Content-Type: application/json' \
 *      -d '{"login":"my_login","password":"my_password","realName":"my_real_name","eMail":"my_email","phone":"my_phone"}'
 */

/**
 * @api {delete} /accounts/user/:uid delete user
 *
 * @apiVersion 1.0.0
 *
 * @apiName deleteUser
 * @apiGroup users
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} uid user id
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 204 OK
 *
 * @apiError userNotFound user not found
 * @apiError forbidden access denied
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "userNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X DELETE http://127.0.0.1:8000/server/api.php/accounts/user/1
 */

/**
 * accounts namespace
 */

namespace api\accounts {

    use api\api;
    use Selpol\Entity\Model\Core\CoreUser;
    use Selpol\Entity\Repository\Core\CoreUserRepository;
    use Selpol\Feature\Authentication\AuthenticationFeature;
    use Selpol\Feature\User\UserFeature;

    /**
     * user methods
     */
    class user extends api
    {
        public static function GET($params)
        {
            $user = container(UserFeature::class)->getUser($params["_id"]);

            return api::ANSWER($user, ($user !== false) ? "user" : "notFound");
        }

        public static function POST($params)
        {
            $user = new CoreUser();

            $user->login = $params['login'];
            $user->password = password_hash(generate_password(), PASSWORD_DEFAULT);

            $user->real_name = $params['realName'];
            $user->e_mail = $params['eMail'];
            $user->phone = $params['phone'];

            $success = container(CoreUserRepository::class)->insert($user);

            return self::ANSWER($success ? $user->uid : false, $success ? 'uid' : 'notAcceptable');
        }

        public static function PUT($params)
        {
            if (!array_key_exists('realName', $params) && array_key_exists('enabled', $params)) {
                $user = container(CoreUserRepository::class)->findById($params['_id']);

                $user->enabled = $params['enabled'];

                $success = container(UserFeature::class)->modifyUserEnabled($params['_id'], $params['enabled']);

                return self::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            $success = container(UserFeature::class)->modifyUser($params["_id"], $params["realName"], $params["eMail"], $params["phone"], $params["tg"], $params["notification"], $params["enabled"], $params["defaultRoute"], $params["persistentToken"]);

            if (@$params["password"] && (int)$params["_id"]) {
                $success = $success && container(UserFeature::class)->setPassword($params["_id"], $params["password"]);
                return self::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            } else return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");

        }

        public static function DELETE($params)
        {
            if (@$params["session"]) {
                container(AuthenticationFeature::class)->logout($params["session"]);

                $success = true;
            } else {
                $user = container(CoreUserRepository::class)->findById($params['_id']);

                $success = container(CoreUserRepository::class)->delete($user);
            }

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index(): array
        {
            return ["GET" => "[Пользователь] Получить пользователя", "POST" => '[Пользователь] Создать пользователя', "PUT" => "[Пользователь] Обновить пользователя", "DELETE" => '[Пользователь] Удалить пользователя'];
        }
    }
}