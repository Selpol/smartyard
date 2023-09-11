<?php

namespace Selpol\Kernel\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Http\Response;
use Selpol\Http\ServerRequest;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Kernel\Runner\Trait\ResponseTrait;
use Selpol\Service\HttpService;
use Selpol\Service\RedisService;

class FrontendRunner implements KernelRunner
{
    use ResponseTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    function __invoke(Kernel $kernel): int
    {
        $http = $kernel->getContainer()->get(HttpService::class);

        $request = $http->createServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        $kernel->getContainer()->set(ServerRequest::class, $request);

        if ($request->getMethod() === 'OPTIONS') {
            return $this->emit(
                $this->response(204)
                    ->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Headers', '*')
                    ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])
                    ->withHeader('Content-Type', 'text/html;charset=ISO-8859-1')
            );
        }

        $http_authorization = $request->getHeader('Authorization');
        $http_refresh = $request->hasHeader('X-Api-Refresh');

        if (count($http_authorization) == 0)
            return $this->emit($this->response(403)->withStatusJson());

        $ip = connection_ip($request);

        if (!$ip)
            return $this->emit($this->response(403)->withStatusJson());

        $redis_cache_ttl = config('redis.cache_ttl') ?? 3600;

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

        $required_backends = ["authentication", "authorization", "users"];

        foreach ($required_backends as $backend)
            if (backend($backend) === false)
                return $this->emit($this->response(500)->withStatusJson());

        $clearCache = false;

        if ($token = $request->getQueryParam('_token'))
            $http_authorization = 'Bearer ' . urldecode($token);

        if ($request->getQueryParam('refresh'))
            $http_refresh = true;

        if ($request->getQueryParam('_clearCache'))
            $clearCache = true;

        $params += $request->getQueryParams();

        if (count($_POST)) {
            foreach ($_POST as $key => $value)
                if ($key == '_token') $http_authorization = 'Bearer ' . urldecode($value);
                else if ($key == '_refresh') $http_refresh = true;
                else if ($key == '_clearCache') $clearCache = true;
                else $params[$key] = urldecode($value);
        }

        $body = $request->getParsedBody();

        if (is_array($body)) {
            if (array_key_exists('_token', $body)) $http_authorization = 'Bearer ' . $body['_token'];
            else if (array_key_exists('_refresh', $body)) $http_refresh = true;
            else if (array_key_exists('_clearCache', $body)) $clearCache = true;

            $params += $body;
        }

        $auth = false;

        if ($api == 'server' && $method == 'ping')
            return $this->emit($this->response()->withString('pong'));

        if ($api == 'authentication' && $method == 'login') {
            if (!@$params['login'] || !@$params['password']) {
                return $this->emit($this->response(403)->withStatusJson());
            }
        } else {
            if ($http_authorization) {
                $userAgent = $request->getHeader('User-Agent');

                $auth = backend('authentication')->auth($http_authorization, count($userAgent) > 0 ? $userAgent[0] : '', $ip);

                if (!$auth)
                    return $this->emit($this->response(403)->withStatusJson());
            } else return $this->emit($this->response(403)->withStatusJson());
        }

        if ($http_authorization && $auth) {
            $params["_uid"] = $auth["uid"];

            $params["_login"] = $auth["login"];
            $params["_token"] = $auth["token"];

            foreach ($required_backends as $backend)
                backend($backend)->setCreds($auth["uid"], $auth["login"]);
        }

        $params["_md5"] = md5(print_r($params, true));

        $params["_ip"] = $ip;

        if (@$params["_login"])
            container(RedisService::class)->getRedis()->set("last_" . md5($params["_login"]), time());

        if ($api == 'accounts' && $method == 'forgot')
            return $this->emit($this->forgot($params));
        else if (file_exists(path("controller/api/{$api}/{$method}.php"))) {
            $cache = false;

            if ($params["_request_method"] === "GET") {
                try {
                    $cache = json_decode(container(RedisService::class)->getRedis()->get("cache_" . $params["_md5"]) . "_" . $auth["uid"], true);
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                }
            }

            if ($cache && !$http_refresh) {
                header("X-Api-Data-Source: cache_" . $params["_md5"] . "_" . $auth["uid"]);
                $code = array_key_first($cache);

                return $this->emit($this->response($code)->withJson($cache[$code]));
            } else {
                if ($clearCache)
                    clear_cache($auth['uid']);

                require_once path("controller/api/$api/$method.php");

                if (class_exists("\\api\\$api\\$method")) {
                    $result = call_user_func(["\\api\\$api\\$method", $params["_request_method"]], $params);

                    $code = array_key_first($result);

                    if ((int)$code) {
                        if ($params['_request_method'] == 'GET' && (int)$code == 200) {
                            $ttl = (array_key_exists('cache', $result)) ? ((int)$cache) : $redis_cache_ttl;

                            container(RedisService::class)->getRedis()->setex('cache_' . $params['_md5'] . '_' . $auth['uid'], $ttl, json_encode($result));
                        }

                        return $this->emit($this->response($code)->withJson($result));
                    } else return $this->emit($this->response(500)->withStatusJson());
                } else return $this->emit($this->response(404)->withStatusJson());
            }
        }

        return $this->emit($this->response(404)->withStatusJson());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    private function forgot(array $params): Response
    {
        if (@$params["eMail"]) {
            $uid = backend('users')->getUidByEMail($params["eMail"]);
            if ($uid !== false) {
                $redis = container(RedisService::class)->getRedis();

                $keys = $redis->keys("forgot_*_" . $uid);

                if (!count($keys)) {
                    $token = md5(guid_v4());
                    $redis->setex("forgot_" . $token . "_" . $uid, 900, "1");
                }
            }
        }

        if (@$params["token"]) {
            $redis = container(RedisService::class)->getRedis();

            $keys = $redis->keys("forgot_{$params["token"]}_*");

            foreach ($keys as $key)
                $redis->del($key);
        }

        if (@$params["available"])
            if (backend('users')->capabilities()["mode"] !== "rw")
                return $this->response(403)->withStatusJson();

        return $this->response()->withStatusJson();
    }
}