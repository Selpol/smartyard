<?php

namespace Selpol\Feature\Authentication;

use Selpol\Entity\Model\Core\CoreAuth;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Feature\Authentication\Internal\InternalAuthenticationFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\DatabaseService;

#[Singleton(InternalAuthenticationFeature::class)]
readonly abstract class AuthenticationFeature extends Feature
{
    public abstract function checkAuth(string $login, string $password): string|int|bool;

    public function login(string $login, string $password, bool $rememberMe, string $ua = "", string $did = "", string $ip = ""): array
    {
        $uid = $this->checkAuth($login, $password);

        if (!is_string($uid)) {
            $auths = CoreAuth::fetchAll(criteria()->equal('user_id', $uid)->equal('status', 1));

            $auth = new CoreAuth([
                'token' => md5($uid . ":" . guid_v4() . ":" . $did),

                'user_id' => $uid,
                'user_agent' => $ua,
                'user_ip' => $ip,

                'remember_me' => $rememberMe ? 1 : 0,

                'status' => 1
            ]);

            if ($auth->safeInsert()) {
                container(DatabaseService::class)->modify("update core_users set last_login = " . time() . " where uid = " . $uid, false, ["silent"]);

                return ["result" => true, "token" => $auth->token];
            }

            return ["result" => false, "code" => 500, "error" => "error", "message" => "Не удалось завершить авторизацию"];
        }

        return ["result" => false, "code" => 403, "error" => "forbidden", "message" => $uid];
    }

    public function auth(string $token, string $ua = "", string $ip = ""): ?CoreUser
    {
        $auth = CoreAuth::fetch(criteria()->equal('token', $token)->equal('status', 1));

        if ($auth) {
            if ($auth->remember_me && $auth->user_agent != $ua && $auth->user_ip != $ip) {
                $auth->status = 0;
                $auth->update();

                return null;
            }

            $user = CoreUser::findById($auth->user_id);

            if ($user) {
                return $user;
            }
        }

        return null;
    }

    public function logout(string $token): bool
    {
        $auth = CoreAuth::fetch(criteria()->equal('token', $token)->equal('status', 1));

        if ($auth) {
            $auth->status = 0;

            return $auth->safeUpdate();
        }

        return false;
    }
}