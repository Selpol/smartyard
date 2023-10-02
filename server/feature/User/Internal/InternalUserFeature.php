<?php

namespace Selpol\Feature\User\Internal;

use PDO;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\User\UserFeature;
use Selpol\Service\AuthService;
use Throwable;

class InternalUserFeature extends UserFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function getUsers(): bool|array
    {
        $user = container(AuthService::class)->getUserOrThrow();

        try {
            $users = $this->getDatabase()->query("select uid, login, real_name, e_mail, phone, tg, enabled, last_login from core_users order by uid", PDO::FETCH_ASSOC)->fetchAll();
            $_users = [];

            foreach ($users as $u) {
                $_users[] = [
                    "uid" => $u["uid"],
                    "login" => $u["login"],
                    "realName" => $u["real_name"],
                    "eMail" => $u["e_mail"],
                    "phone" => $u["phone"],
                    "tg" => $u["tg"],
                    "enabled" => $u["enabled"],
                    "lastLogin" => $u["last_login"],
                    "lastAction" => $this->getRedis()->getRedis()->get("last_" . md5($u["login"]))
                ];
            }

            foreach ($_users as &$u) {
                if ($u['uid'] == $user->getIdentifier() || $user->getUsername() === 'admin') {
                    $u['sessions'] = [];

                    $lk = $this->getRedis()->getRedis()->keys("auth_*_{$u['uid']}");

                    foreach ($lk as $k)
                        $u['sessions'][] = json_decode($this->getRedis()->getRedis()->get($k), true);

                    $pk = $this->getRedis()->getRedis()->keys("persistent_*_{$u["uid"]}");

                    foreach ($pk as $k) {
                        $s = json_decode($this->getRedis()->getRedis()->get($k), true);
                        $s["byPersistentToken"] = true;

                        $u["sessions"][] = $s;
                    }
                } else {
                    unset($u['lastLogin']);
                    unset($u['lastAction']);
                }
            }

            return $_users;
        } catch (Throwable $e) {
            error_log(print_r($e, true));
            return false;
        }
    }

    public function getUser(int $uid): bool|array
    {
        try {
            $user = $this->getDatabase()->query("select uid, login, real_name, e_mail, phone, tg, notification, enabled, default_route from core_users where uid = $uid", PDO::FETCH_ASSOC)->fetchAll();

            if (count($user)) {
                $_user = [
                    "uid" => $user[0]["uid"],
                    "login" => $user[0]["login"],
                    "realName" => $user[0]["real_name"],
                    "eMail" => $user[0]["e_mail"],
                    "phone" => $user[0]["phone"],
                    "tg" => $user[0]["tg"],
                    "notification" => $user[0]["notification"],
                    "enabled" => $user[0]["enabled"],
                    "defaultRoute" => $user[0]["default_route"]
                ];

                $persistent = false;
                $_keys = $this->getRedis()->getRedis()->keys("persistent_*_" . $user[0]["uid"]);

                foreach ($_keys as $_key) {
                    $persistent = explode("_", $_key)[1];

                    break;
                }

                if ($persistent)
                    $_user["persistentToken"] = $persistent;

                return $_user;
            } else return false;
        } catch (Throwable $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function addUser(string $login, ?string $realName = null, ?string $eMail = null, ?string $phone = null): int|bool
    {
        $login = trim($login);
        $password = $this->generate_password();

        try {
            $db = $this->getDatabase();

            $sth = $db->prepare("insert into core_users (login, password, real_name, e_mail, phone, enabled) values (:login, :password, :real_name, :e_mail, :phone, 1)");

            if ($sth->execute([
                ":login" => $login,
                ":password" => password_hash($password, PASSWORD_DEFAULT),
                ":real_name" => $realName ? trim($realName) : null,
                ":e_mail" => $eMail ? trim($eMail) : null,
                ":phone" => $phone ? trim($phone) : null,
            ]))
                return $db->lastInsertId();
            else return false;
        } catch (Throwable $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function setPassword(int $uid, string $password): bool
    {
        if ($uid === 0 || !trim($password))
            return false;

        try {
            $sth = $this->getDatabase()->prepare("update core_users set password = :password where uid = $uid");

            return $sth->execute([":password" => password_hash($password, PASSWORD_DEFAULT)]);
        } catch (Throwable $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function deleteUser(int $uid): bool
    {
        if ($uid > 0) {
            try {
                $this->getDatabase()->exec("delete from core_users where uid = $uid");

                $redis = $this->getRedis()->getRedis();

                $_keys = $redis->keys("persistent_*_" . $uid);

                foreach ($_keys as $_key)
                    $redis->del($_key);
            } catch (Throwable $e) {
                error_log(print_r($e, true));

                return false;
            }

            return true;
        } else return false;
    }

    public function modifyUserEnabled(int $uid, bool $enabled): bool
    {
        try {
            $sth = $this->getDatabase()->prepare("update core_users set enabled = :enabled where uid = $uid");

            return $sth->execute([":enabled" => $enabled ? "1" : "0"]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function modifyUser(int $uid, string $realName = '', string $eMail = '', string $phone = '', string|null $tg = '', string|null $notification = 'tgEmail', bool $enabled = true, string|null $defaultRoute = '', bool|string|null $persistentToken = false): bool
    {
        if (!in_array($notification, ["none", "tgEmail", "emailTg", "tg", "email"]))
            return false;

        try {
            $db = $this->getDatabase();
            $redis = $this->getRedis()->getRedis();

            $sth = $db->prepare("update core_users set real_name = :real_name, e_mail = :e_mail, phone = :phone, tg = :tg, notification = :notification, enabled = :enabled, default_route = :default_route where uid = $uid");

            if ($persistentToken && strlen(trim($persistentToken)) === 32 && $uid && $enabled) {
                $redis->set("persistent_" . trim($persistentToken) . "_" . $uid, json_encode([
                    "uid" => $uid,
                    "login" => $db->get("select login from core_users where uid = $uid", options: ["fieldlify"]),
                    "started" => time(),
                ]));
            } else {
                $_keys = $redis->keys("persistent_*_" . $uid);

                foreach ($_keys as $_key)
                    $redis->del($_key);
            }

            if (!$enabled) {
                $_keys = $redis->keys("auth_*_" . $uid);

                foreach ($_keys as $_key)
                    $redis->del($_key);
            }

            return $sth->execute([
                ":real_name" => trim($realName),
                ":e_mail" => trim($eMail),
                ":phone" => trim($phone),
                ":tg" => trim($tg),
                ":notification" => trim($notification),
                ":enabled" => $enabled ? "1" : "0",
                ":default_route" => trim($defaultRoute),
            ]);
        } catch (Throwable $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function getUidByEMail(string $eMail): bool|int
    {
        try {
            $sth = $this->getDatabase()->prepare("select uid from core_users where e_mail = :e_mail");

            if ($sth->execute([":e_mail" => $eMail])) {
                $users = $sth->fetchAll(PDO::FETCH_ASSOC);

                if (count($users)) return (int)$users[0]["uid"];
                else return false;
            } else return false;
        } catch (Throwable) {
            return false;
        }
    }

    public function getUidByLogin(string $login): int|bool
    {
        try {
            $users = $this->getDatabase()->get("select uid from core_users where login = :login", ["login" => $login], ["uid" => "uid"]);

            if (count($users)) return (int)$users[0]["uid"];
            else return false;
        } catch (Throwable) {
            return false;
        }
    }

    private function generate_password(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < 8; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }
}