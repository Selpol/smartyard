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
use Selpol\Service\Auth\Token\CoreAuthToken;
use Selpol\Service\Auth\User\CoreAuthUser;
use Selpol\Service\AuthService;
use Selpol\Service\Exception\DatabaseException;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

class FrontendRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerKernelTrait;

    use EmitTrait {
        emit as frontendEmit;
    }

    public function __construct()
    {
        $this->setLogger(file_logger('frontend'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws InvalidArgumentException
     */
    public function run(array $arguments): int
    {
        $request = server_request($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        $request->withParsedBody(parse_body($request));

        kernel()->getContainer()->set(ServerRequestInterface::class, $request);

        if ($request->getMethod() === 'OPTIONS') {
            return $this->emit(response(204));
        }

        $http_authorization = $request->getHeader('Authorization');

        $http_authorization = count($http_authorization) == 0 ? false : $http_authorization[0];

        $ip = connection_ip($request);

        if ($ip === null || $ip === '' || $ip === '0') {
            return $this->emit(rbt_response(401, 'Неизвестный источник запроса'));
        }

        $path = explode("?", $request->getRequestTarget())[0];

        $server = parse_url((string)config_get('api.frontend'));

        if ($server && $server['path']) {
            $path = substr($path, strlen($server['path']));
        }

        if ($path && $path[0] === '/') {
            $path = substr($path, 1);
        }

        $m = explode('/', $path);

        if (count($m) < 2) {
            return $this->emit(rbt_response(404));
        }

        $api = $m[0];
        $method = $m[1];

        $params = [];

        if (count($m) >= 3) {
            $params["_id"] = urldecode($m[2]);
        }

        $params["_path"] = ["api" => $api, "method" => $method];

        if (!$_SERVER['REQUEST_METHOD']) {
            return $this->emit(rbt_response(404));
        }

        $params["_request_method"] = $_SERVER['REQUEST_METHOD'];

        if (!$_SERVER['HTTP_USER_AGENT']) {
            return $this->emit(rbt_response(404));
        }

        $params["ua"] = $_SERVER["HTTP_USER_AGENT"];

        $params += $request->getQueryParams();

        if ($_POST !== []) {
            foreach ($_POST as $key => $value) {
                $params[$key] = urldecode((string)$value);
            }
        }

        $body = $request->getParsedBody();

        if (is_array($body)) {
            $params += $body;
        }

        if ($api === 'server' && $method === 'ping') {
            return $this->emit(rbt_response()->withBody(stream('pong')));
        }

        if ($api === 'accounts' && $method === 'forgot') {
            return $this->emit(response(204));
        }

        if ($api === 'authentication' && $method === 'login') {
            if (!@$params['login'] || !@$params['password']) {
                return $this->emit(rbt_response(400, 'Логин или пароль не указан'));
            }
        } elseif ($http_authorization) {
            $authorization = explode(' ', $http_authorization);

            if (count($authorization) !== 2 || $authorization[0] !== 'Bearer') {
                return $this->emit(rbt_response(401, 'Не верные данные авторизации'));
            }

            $userAgent = $request->getHeaderLine('User-Agent');
            $user = container(AuthenticationFeature::class)->auth($authorization[1], $userAgent, $ip);

            if (!$user) {
                return $this->emit(rbt_response(401, 'Пользователь не авторизирован'));
            }

            container(AuthService::class)->setToken(new CoreAuthToken($user['token'], $user->aud_jti));
            container(AuthService::class)->setUser(new CoreAuthUser($user));
        } else {
            return $this->emit(rbt_response(401, 'Данные авторизации не переданны'));
        }

        if (!($api === 'authentication' && $method === 'login') && !container(AuthService::class)->checkScope($api . '-' . $method . '-' . strtolower((string)$params['_request_method']))) {
            try {
                $permission = container(PermissionRepository::class)->findByTitle($api . '-' . $method . '-' . strtolower((string)$params['_request_method']));

                return $this->emit(rbt_response(403, 'Недостаточно прав для данного действия (' . ($permission?->description ?? 'Неизвестное правило') . ')'));
            } catch (Throwable) {
                return $this->emit(rbt_response(403, 'Недостаточно прав для данного действия'));
            }
        }

        /** @var class-string<Api> $class */
        $class = sprintf('Selpol\Controller\Api\%s\%s', $api, $method);

        if (class_exists($class)) {
            $result = $class::{$params['_request_method']}($params);

            if ($result !== null) {
                if ($result instanceof Response) {
                    return $this->emit($result);
                }

                $code = array_key_first($result);

                if ((int)$code !== 0) {
                    return $this->emit(json_response($code, body: $result[$code]));
                }

                return $this->emit(rbt_response(500));
            }

            return $this->emit(response(204));
        }

        return $this->emit(rbt_response(404));
    }

    public function error(Throwable $throwable): int
    {
        try {
            if ($throwable instanceof KernelException) {
                $response = json_response($throwable->getCode() ?: 500, body: ['success' => false, 'message' => $throwable->getLocalizedMessage()]);
            } elseif ($throwable instanceof ValidatorException) {
                $response = json_response(400, body: ['success' => false, 'message' => $throwable->getValidatorMessage()->message]);
            } elseif ($throwable instanceof DatabaseException) {
                if ($throwable->isUniqueViolation()) {
                    $response = json_response(400, body: ['success' => false, 'message' => 'Дубликат объекта']);
                } elseif ($throwable->isForeignViolation()) {
                    $response = json_response(400, body: ['success' => false, 'Объект имеет дочерние зависимости']);
                } else {
                    $response = json_response(500, body: ['success' => false, 'message' => Response::$codes[500]['message']]);
                }
            } else {
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