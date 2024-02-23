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
    public abstract function checkAuth(string $login, string $password): int|bool;

    public function login(string $login, string $password, bool $rememberMe, string $ua = "", string $did = "", string $ip = ""): array
    {
        $uid = $this->checkAuth($login, $password);

        if ($uid !== false) {
            $auths = CoreAuth::fetchAll(criteria()->equal('user_id', $uid)->equal('status', 1));

            if (count($auths) > 5) {
                foreach ($auths as $auth) {
                    $auth->status = 0;

                    $auth->update();
                }
            }

            $auth = new CoreAuth([
                'token' => md5($uid . ":" . guid_v4() . ":" . $did),

                'user_id' => $uid,
                'user_agent' => $ua,
                'user_ip' => $ip,

                'remember_me' => $rememberMe ? 1 : 0,

                'status' => 1
            ]);

            if ($auth->insert()) {
                container(DatabaseService::class)->modify("update core_users set last_login = " . time() . " where uid = " . $uid, false, ["silent"]);

                return ["result" => true, "token" => $auth->token];
            }
        }

        return ["result" => false, "code" => 403, "error" => "forbidden"];
    }

    public function auth(string $authorization, string $ua = "", string $ip = ""): array|bool
    {
        $authorization = explode(' ', $authorization);

        if ($authorization[0] === 'Bearer') {
            $token = $authorization[1];

            $auth = CoreAuth::fetch(criteria()->equal('token', $token)->equal('status', 1));

            if ($auth) {
                if ($auth->remember_me && $auth->user_agent != $ua && $auth->user_ip != $ip) {
                    $auth->status = 0;
                    $auth->update();

                    return false;
                }

                $user = CoreUser::findById($auth->user_id);

                if ($user) {
                    $auth->last_access_to = date('Y-m-d H:i:s');

                    if ($auth->update())
                        return ['token' => $token, 'user' => $user->jsonSerialize()];
                }
            }
        }

        return false;
    }

    public function logout(string $token): bool
    {
        $auth = CoreAuth::fetch(criteria()->equal('token', $token)->equal('status', 1));

        if ($auth) {
            $auth->status = 0;

            return $auth->update();
        }

        return false;
    }
}