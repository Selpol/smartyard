<?php

namespace Selpol\Kernel\Runner;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Http\Response;
use Selpol\Http\ServerRequest;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Kernel\Runner\Trait\ResponseTrait;
use Selpol\Service\Auth\Token\RedisAuthToken;
use Selpol\Service\Auth\User\RedisAuthUser;
use Selpol\Service\AuthService;
use Selpol\Service\HttpService;
use Selpol\Service\RedisService;

class FrontendRunner implements KernelRunner
{
    use ResponseTrait {
        emit as protected traitEmit;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws InvalidArgumentException
     */
    function __invoke(Kernel $kernel): int
    {
        require path('/controller/api/api.php');

        $http = $kernel->getContainer()->get(HttpService::class);

        $request = $http->createServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        $kernel->getContainer()->set(ServerRequest::class, $request);

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

        $server = parse_url(config('api.frontend'));

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
            return $this->emit($this->forgot($params));
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

        if (@$params["_login"])
            container(RedisService::class)->getConnection()->set("last_" . md5($params["_login"]), time());

        if (!($api == 'authentication' && $method == 'login') && !container(AuthService::class)->checkScope($api . '-' . $method . '-' . $params['_request_method']))
            return $this->emit($this->response(403)->withStatusJson('Недостаточно прав для данного действия'));

        if (file_exists(path("controller/api/$api/$method.php"))) {
            require_once path("controller/api/$api/$method.php");

            if (class_exists("\\api\\$api\\$method")) {
                $result = call_user_func(["\\api\\$api\\$method", $params['_request_method']], $params);

                $code = array_key_first($result);

                if ((int)$code) return $this->emit($this->response($code)->withJson($result[$code]));
                else return $this->emit($this->response(500)->withStatusJson());
            } else return $this->emit($this->response(404)->withStatusJson());
        }

        return $this->emit($this->response(404)->withStatusJson());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function response(int $code = 200): Response
    {
        return container(HttpService::class)->createResponse($code)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    protected function emit(ResponseInterface $response): int
    {
        container(AuditFeature::class)->audit(container(ServerRequest::class), $response);

        return $this->traitEmit($response);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    private function forgot(array $params): Response
    {
        if (array_key_exists('token', $params)) {
            $redis = container(RedisService::class)->getConnection();

            $keys = $redis->keys("forgot_{$params['token']}_*");

            foreach ($keys as $key)
                $redis->del($key);
        }

        return $this->response(204);
    }
}