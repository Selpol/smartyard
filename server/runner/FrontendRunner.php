<?php

namespace Selpol\Runner;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Router\Trait\EmitTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Service\Auth\Token\RedisAuthToken;
use Selpol\Service\Auth\User\RedisAuthUser;
use Selpol\Service\AuthService;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

class FrontendRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerKernelTrait;

    use EmitTrait {
        emit as frontendEmit;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws InvalidArgumentException
     */
    function run(array $arguments): int
    {
        $request = server_request($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        $request->withParsedBody(parse_body($request));

        kernel()->getContainer()->set(ServerRequestInterface::class, $request);

        if ($request->getMethod() === 'OPTIONS')
            return $this->emit(response(204));

        $http_authorization = $request->getHeader('Authorization');

        if (count($http_authorization) == 0) $http_authorization = false;
        else $http_authorization = $http_authorization[0];

        $ip = connection_ip($request);

        if (!$ip)
            return $this->emit(rbt_response(401, 'Неизвестный источник запроса'));

        $path = explode("?", $request->getRequestTarget())[0];

        $server = parse_url(config_get('api.frontend'));

        if ($server && $server['path']) $path = substr($path, strlen($server['path']));
        if ($path && $path[0] == '/') $path = substr($path, 1);

        $m = explode('/', $path);

        if (count($m) < 2)
            return $this->emit(rbt_response(404));

        $api = $m[0];
        $method = $m[1];

        $params = [];

        if (count($m) >= 3)
            $params["_id"] = urldecode($m[2]);

        $params["_path"] = ["api" => $api, "method" => $method];

        if (!$_SERVER['REQUEST_METHOD'])
            return $this->emit(rbt_response(404));

        $params["_request_method"] = $_SERVER['REQUEST_METHOD'];

        if (!$_SERVER['HTTP_USER_AGENT'])
            return $this->emit(rbt_response(404));

        $params["ua"] = $_SERVER["HTTP_USER_AGENT"];

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
            return $this->emit(rbt_response()->withBody(stream('pong')));
        else if ($api == 'accounts' && $method == 'forgot')
            return $this->emit(response(204));
        else if ($api == 'authentication' && $method == 'login') {
            if (!@$params['login'] || !@$params['password'])
                return $this->emit(rbt_response(400, 'Логин или пароль не указан'));
        } else if ($http_authorization) {
            $userAgent = $request->getHeader('User-Agent');

            $auth = container(AuthenticationFeature::class)->auth($http_authorization, count($userAgent) > 0 ? $userAgent[0] : '', $ip);

            if (!$auth)
                return $this->emit(rbt_response(401, 'Пользователь не авторизирован'));

            container(AuthService::class)->setToken(new RedisAuthToken($auth['token']));
            container(AuthService::class)->setUser(new RedisAuthUser($auth));
        } else return $this->emit(rbt_response(401, 'Данные авторизации не переданны'));

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

                return $this->emit(rbt_response(403, 'Недостаточно прав для данного действия (' . ($permission?->description ?? 'Неизвестное правило') . ')'));
            } catch (Throwable) {
                return $this->emit(rbt_response(403, 'Недостаточно прав для данного действия'));
            }
        }

        /** @var class-string<Api> $class */
        $class = "Selpol\\Controller\\Api\\$api\\$method";

        if (class_exists($class)) {
            $result = $class::{$params['_request_method']}($params);

            if ($result !== null) {
                if ($result instanceof Response)
                    return $this->emit($result);

                $code = array_key_first($result);

                if ((int)$code) return $this->emit(json_response($code, body: $result[$code]));
                else return $this->emit(rbt_response(500));
            }

            return $this->emit(response(204));
        }

        return $this->emit(rbt_response(404));
    }

    function error(Throwable $throwable): int
    {
        try {
            if ($throwable instanceof KernelException)
                $response = json_response($throwable->getCode() ?: 500, body: ['success' => false, 'message' => $throwable->getLocalizedMessage()]);
            else if ($throwable instanceof ValidatorException)
                $response = json_response(400, body: ['success' => false, 'message' => $throwable->getValidatorMessage()->message]);
            else {
                file_logger('response')->error($throwable);

                $response = json_response(500, body: ['success' => false, 'message' => Response::$codes[500]['message']]);
            }

            return $this->emit($response);
        } catch (Throwable $throwable) {
            file_logger('response')->critical($throwable);

            return 1;
        }
    }

    protected function emit(ResponseInterface $response): int
    {
        $this->frontendEmit(
            $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', '*')
                ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])
        );

        return 0;
    }
}