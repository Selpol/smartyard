<?php

namespace Selpol\Feature\Authentication;

use Exception;
use Selpol\Feature\Authentication\Internal\InternalAuthenticationFeature;
use Selpol\Feature\Feature;
use Selpol\Feature\User\UserFeature;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\DatabaseService;
use Selpol\Service\RedisService;

#[Singleton(InternalAuthenticationFeature::class)]
readonly abstract class AuthenticationFeature extends Feature
{
    abstract public function checkAuth(string $login, string $password): int|bool;

    public function login(string $login, string $password, bool $rememberMe, string $ua = "", string $did = "", string $ip = ""): array
    {
        return $this->getRedis()->use(1, function (RedisService $service) use ($login, $password, $rememberMe, $ua, $did, $ip) {
            $db = container(DatabaseService::class);

            $uid = $this->checkAuth($login, $password);

            if ($uid !== false) {
                $keys = $service->keys('user:' . $uid . ':token:*');

                $first_key = "";
                $first_key_time = time();

                if (count($keys) > config_get('redis.max_allowed_tokens')) {
                    foreach ($keys as $key) {
                        try {
                            $auth = json_decode($service->get($key), true);

                            if (@(int)$auth["updated"] < $first_key_time)
                                $first_key = $key;
                        } catch (Exception) {
                            $service->del($key);
                        }
                    }

                    $service->del($first_key);
                }

                if ($rememberMe) {
                    $token = md5($uid . ":" . $login . ":" . $password . ":" . $did);
                } else {
                    if ($did === "Base64")
                        $token = md5($uid . ":" . $login . ":" . $password);
                    else
                        $token = md5(guid_v4());
                }

                $service->setEx('user:' . $uid . ':token:' . $token, $rememberMe ? (7 * 24 * 60 * 60) : 24 * 60 * 60, json_encode([
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
            } else return ["result" => false, "code" => 403, "error" => "forbidden"];
        });
    }

    public function auth(string $authorization, string $ua = "", string $ip = ""): array|bool
    {
        $authorization = explode(" ", $authorization);

        if ($authorization[0] === "Bearer") {
            $token = $authorization[1];

            return $this->getRedis()->use(1, function (RedisService $service) use ($token, $ua, $ip) {
                $keys = $service->keys('user:*:token:' . $token);

                if (count($keys) === 1) {
                    $auth = json_decode($service->get($keys[0]), true);

                    if ($ua)
                        $auth["ua"] = $ua;

                    if ($ip)
                        $auth["ip"] = $ip;

                    $auth["updated"] = time();

                    $auth["token"] = $token;

                    $service->setEx($keys[0], $auth["persistent"] ? (7 * 24 * 60 * 60) : 24 * 60 * 60, json_encode($auth));

                    if (container(UserFeature::class)->getUidByLogin($auth["login"]) == $auth["uid"]) return $auth;
                    else return false;
                }

                return false;
            });
        }

        return false;
    }

    public function logout(string $token): void
    {
        $this->getRedis()->use(1, function (RedisService $service) use ($token) {
            $keys = $service->keys('user:*:token:' . $token);

            if (count($keys) === 1)
                $service->del($keys[0]);
        });
    }
}