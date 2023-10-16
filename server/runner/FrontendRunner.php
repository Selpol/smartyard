<?php

namespace Selpol\Runner;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Http\Response;
use Selpol\Http\ServerRequest;
use Selpol\Runner\Trait\ResponseTrait;
use Selpol\Service\Auth\Token\RedisAuthToken;
use Selpol\Service\Auth\User\RedisAuthUser;
use Selpol\Service\AuthService;
use Selpol\Service\HttpService;
use Throwable;

class FrontendRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerKernelTrait;

    use ResponseTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws InvalidArgumentException
     */
    function run(array $arguments): int
    {
        $http = container(HttpService::class);

        $request = $http->createServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        kernel()->getContainer()->set(ServerRequest::class, $request);

        if ($request->getMethod() === 'OPTIONS')
            return $this->emit($this->response(204)->withHeader('Content-Type', 'text/html;charset=ISO-8859-1'));

        $http_authorization = $request->getHeader('Authorization');

        if (count($http_authorization) == 0) $http_authorization = false;
        else $http_authorization = $http_authorization[0];

        $http_refresh = $request->hasHeader('X-Api-Refresh');

        $ip = connection_ip($request);

        if (!$ip)
            return $this->emit($this->response(403)->withStatusJson('Неизвестный источник запроса'));

        $path = explode("?", $request->getRequestTarget())[0];

        $server = parse_url(config_get('api.frontend'));

        if ($server && $server['path']) $path = substr($path, strlen($server['path']));
        if ($path && $path[0] == '/') $path = substr($path, 1);

        $m = explode('/', $path);

        $api = @$m[0];
        $method = @$m[1];

        $params = [];

        if (count($m) >= 3)
            $params["_id"] = urldecode($m[2]);

        $params["_path"] = ["api" => $api, "method" => $method];

        $params["_request_method"] = @$_SERVER['REQUEST_METHOD'];
        $params["ua"] = @$_SERVER["HTTP_USER_AGENT"];

        if ($token = $request->getQueryParam('_token'))
            $http_authorization = 'Bearer ' . urldecode($token);

        $params += $request->getQueryParams();

        if (count($_POST)) {
            foreach ($_POST as $key => $value)
                if ($key == '_token') $http_authorization = 'Bearer ' . urldecode($value);
                else $params[$key] = urldecode($value);
        }

        $body = $request->getParsedBody();

        if (is_array($body)) {
            if (array_key_exists('_token', $body)) $http_authorization = 'Bearer ' . $body['_token'];

            $params += $body;
        }

        $auth = false;

        if ($api == 'server' && $method == 'ping')
            return $this->emit($this->response()->withString('pong'));
        else if ($api == 'accounts' && $method == 'forgot')
            return $this->emit($this->response(204));
        else if ($api == 'authentication' && $method == 'login') {
            if (!@$params['login'] || !@$params['password'])
                return $this->emit($this->response(400)->withStatusJson('Логин или пароль не указан'));
        } else if ($http_authorization) {
            $userAgent = $request->getHeader('User-Agent');

            $auth = container(AuthenticationFeature::class)->auth($http_authorization, count($userAgent) > 0 ? $userAgent[0] : '', $ip);

            if (!$auth)
                return $this->emit($this->response(401)->withStatusJson('Пользователь не авторизирован'));

            container(AuthService::class)->setToken(new RedisAuthToken($auth['token']));
            container(AuthService::class)->setUser(new RedisAuthUser($auth));
        } else return $this->emit($this->response(401)->withStatusJson('Данные авторизации не переданны'));

        if ($http_authorization && $auth) {
            $params["_uid"] = $auth["uid"];

            $params["_login"] = $auth["login"];
            $params["_token"] = $auth["token"];
        }

        $params["_md5"] = md5(print_r($params, true));

        $params["_ip"] = $ip;

        if (!($api == 'authentication' && $method == 'login') && !container(AuthService::class)->checkScope($api . '-' . $method . '-' . strtolower($params['_request_method']))) {
            try {
                $permission = container(PermissionRepository::class)->findByTitle($api . '-' . $method . '-' . strtolower($params['_request_method']));

                return $this->emit($this->response(403)->withStatusJson('Недостаточно прав для данного действия (' . $permission->description . ')'));
            } catch (Throwable) {
                return $this->emit($this->response(403)->withStatusJson('Недостаточно прав для данного действия'));
            }
        }

        if (file_exists(path("controller/Api/$api/$method.php"))) {
            require_once path("controller/Api/$api/$method.php");

            /** @var class-string<Api> $class */
            $class = "Selpol\\Controller\\Api\\$api\\$method";

            if (class_exists($class)) {
                $result = $class::{$params['_request_method']}($params);

                if ($result !== null) {
                    if ($result instanceof Response)
                        return $this->emit(
                            $result->withHeader('Access-Control-Allow-Origin', '*')
                                ->withHeader('Access-Control-Allow-Headers', '*')
                                ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])
                        );

                    $code = array_key_first($result);

                    if ((int)$code) return $this->emit($this->response($code)->withJson($result[$code]));
                    else return $this->emit($this->response(500)->withStatusJson());
                }

                return $this->emit($this->response(204));
            } else return $this->emit($this->response(404)->withStatusJson());
        }

        return $this->emit($this->response(404)->withStatusJson());
    }

    protected function response(int $code = 200): Response
    {
        return container(HttpService::class)->createResponse($code)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
    }
}