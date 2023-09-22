<?php

namespace Selpol\Feature\Authentication;

use Exception;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Feature\Feature;
use Selpol\Feature\User\UserFeature;
use Selpol\Service\DatabaseService;
use Selpol\Service\RedisService;

abstract class AuthenticationFeature extends Feature
{
    abstract public function checkAuth(string $login, string $password): int|bool;

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function login(string $login, string $password, bool $rememberMe, string $ua = "", string $did = "", string $ip = ""): array
    {
        $db = container(DatabaseService::class);
        $redis = container(RedisService::class)->getRedis();

        $uid = $this->checkAuth($login, $password);
        if ($uid !== false) {
            $keys = $redis->keys("auth_*_" . $uid);
            $first_key = "";
            $first_key_time = time();
            if (count($keys) > config('redis.max_allowed_tokens')) {
                foreach ($keys as $key) {
                    try {
                        $auth = json_decode($redis->get($key));
                        if (@(int)$auth["updated"] < $first_key_time) {
                            $first_key = $key;
                        }
                    } catch (Exception) {
                        $redis->delete($key);
                    }
                }

                $redis->delete($first_key);
            }
            if ($rememberMe) {
                $token = md5($uid . ":" . $login . ":" . $password . ":" . $did);
            } else {
                if ($did === "Base64") {
                    $token = md5($uid . ":" . $login . ":" . $password);
                } else {
                    $token = md5(guid_v4());
                }
            }

            $redis->setex("auth_" . $token . "_" . $uid, $rememberMe ? (7 * 24 * 60 * 60) : config('redis.token_idle_ttl'), json_encode([
                "uid" => (string)$uid,
                "login" => $login,
                "persistent" => $rememberMe,
                "ua" => $ua,
                "did" => $did,
                "ip" => $ip,
                "started" => time(),
                "updated" => time(),
            ]));

            $db->modify("update core_users set last_login = " . time() . " where uid = " . $uid, false, ["silent"]);

            return ["result" => true, "token" => $token, "login" => $login, "ua" => $ua, "uid" => $uid];
        } else return ["result" => false, "code" => 404, "error" => "userNotFound"];
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function auth(string $authorization, string $ua = "", string $ip = ""): array|bool
    {
        $redis = container(RedisService::class)->getRedis();

        $authorization = explode(" ", $authorization);

        if ($authorization[0] === "Bearer") {
            $token = $authorization[1];

            $keys = $redis->keys("persistent_" . $token . "_*");

            foreach ($keys as $key) {
                $auth = json_decode($redis->get($key), true);

                if ($ua) {
                    $auth["ua"] = $ua;
                }

                if ($ip) {
                    $auth["ip"] = $ip;
                }

                $auth["updated"] = time();

                $auth["token"] = $token;

                $redis->set($key, json_encode($auth));

                if (container(UserFeature::class)->getUidByLogin($auth["login"]) == $auth["uid"]) {
                    return $auth;
                } else {
                    $redis->del($key);
                }
            }

            $keys = $redis->keys("auth_" . $token . "_*");

            foreach ($keys as $key) {
                $auth = json_decode($redis->get($key), true);

                if ($ua) {
                    $auth["ua"] = $ua;
                }

                if ($ip) {
                    $auth["ip"] = $ip;
                }

                $auth["updated"] = time();

                $auth["token"] = $token;

                $redis->setex($key, $auth["persistent"] ? (7 * 24 * 60 * 60) : config('redis.token_idle_ttl'), json_encode($auth));

                if (container(UserFeature::class)->getUidByLogin($auth["login"]) == $auth["uid"]) {
                    return $auth;
                } else {
                    return false;
                }
            }
        }

        if ($authorization[0] === "Base64") {
            $login = base64_decode($authorization[1]);
            $password = base64_decode($authorization[2]);

            $auth = $this->login($login, $password, false, $ua, "Base64", $ip);

            $auth["updated"] = time();

            if ($auth["result"])
                return $auth;
        }

        return false;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function logout(string $token, bool $all = false): void
    {
        $redis = container(RedisService::class)->getRedis();

        $keys = $redis->keys("auth_" . $token . "_*");

        if ($all) {
            foreach ($keys as $key) {
                $uid = @explode("_", $key)[2];
                $_keys = $redis->keys("auth_*_" . $uid);

                foreach ($_keys as $_key)
                    $redis->del($_key);

                break;
            }
        } else {
            $keys = $redis->keys("auth_" . $token . "_*");

            foreach ($keys as $key)
                $redis->del($key);
        }
    }
}