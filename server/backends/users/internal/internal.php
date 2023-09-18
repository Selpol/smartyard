<?php

namespace backends\users;

use Exception;
use Throwable;

class internal extends users
{
    public function getUsers(): bool|array
    {
        try {
            $users = $this->db->query("select uid, login, real_name, e_mail, phone, tg, enabled, last_login, primary_group from core_users order by uid", \PDO::FETCH_ASSOC)->fetchAll();
            $_users = [];

            foreach ($users as $user) {
                $_users[] = [
                    "uid" => $user["uid"],
                    "login" => $user["login"],
                    "realName" => $user["real_name"],
                    "eMail" => $user["e_mail"],
                    "phone" => $user["phone"],
                    "tg" => $user["tg"],
                    "enabled" => $user["enabled"],
                    "lastLogin" => $user["last_login"],
                    "lastAction" => $this->redis->get("last_" . md5($user["login"]))
                ];
            }

            $a = backend("authorization");

            if ($a->allow([
                "_uid" => $this->uid,
                "_path" => ["api" => "accounts", "method" => "user"],
                "_request_method" => "POST",
            ])) {
                foreach ($_users as &$u) {
                    $u["sessions"] = [];

                    $lk = $this->redis->keys("auth_*_{$u["uid"]}");

                    foreach ($lk as $k)
                        $u["sessions"][] = json_decode($this->redis->get($k), true);

                    $pk = $this->redis->keys("persistent_*_{$u["uid"]}");

                    foreach ($pk as $k) {
                        $s = json_decode($this->redis->get($k), true);
                        $s["byPersistentToken"] = true;

                        $u["sessions"][] = $s;
                    }
                }
            } else {
                foreach ($_users as &$u) {
                    unset($u["lastLogin"]);
                    unset($u["lastAction"]);
                }
            }

            return $_users;
        } catch (Exception $e) {
            error_log(print_r($e, true));
            return false;
        }
    }

    public function getUser(int $uid): bool|array
    {
        try {
            $user = $this->db->query("select uid, login, real_name, e_mail, phone, tg, notification, enabled, default_route, primary_group from core_users where uid = $uid", \PDO::FETCH_ASSOC)->fetchAll();

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
                $_keys = $this->redis->keys("persistent_*_" . $user[0]["uid"]);

                foreach ($_keys as $_key) {
                    $persistent = explode("_", $_key)[1];

                    break;
                }

                if ($persistent)
                    $_user["persistentToken"] = $persistent;

                return $_user;
            } else return false;
        } catch (Exception $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function addUser(string $login, ?string $realName = null, ?string $eMail = null, ?string $phone = null): int|bool
    {
        $login = trim($login);
        $password = $this->generate_password();

        try {
            $sth = $this->db->prepare("insert into core_users (login, password, real_name, e_mail, phone, enabled) values (:login, :password, :real_name, :e_mail, :phone, 1)");

            if ($sth->execute([
                ":login" => $login,
                ":password" => password_hash($password, PASSWORD_DEFAULT),
                ":real_name" => $realName ? trim($realName) : null,
                ":e_mail" => $eMail ? trim($eMail) : null,
                ":phone" => $phone ? trim($phone) : null,
            ]))
                return $this->db->lastInsertId();
            else return false;
        } catch (Exception $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function setPassword(int $uid, string $password): bool
    {
        if ($uid === 0 || !trim($password))
            return false;

        try {
            $sth = $this->db->prepare("update core_users set password = :password where uid = $uid");

            return $sth->execute([":password" => password_hash($password, PASSWORD_DEFAULT)]);
        } catch (Exception $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function deleteUser(int $uid): bool
    {
        if ($uid > 0) { // admin cannot be deleted
            try {
                $this->db->exec("delete from core_users where uid = $uid");

                $_keys = $this->redis->keys("persistent_*_" . $uid);

                foreach ($_keys as $_key)
                    $this->redis->del($_key);
            } catch (Exception $e) {
                error_log(print_r($e, true));

                return false;
            }

            return true;
        } else return false;
    }

    public function modifyUserEnabled(int $uid, bool $enabled): bool
    {
        try {
            $sth = $this->db->prepare("update core_users set enabled = :enabled where uid = $uid");

            return $sth->execute([":enabled" => $enabled ? "1" : "0"]);
        } catch (Throwable) {
            return false;
        }
    }

    public function modifyUser(int $uid, string $realName = '', string $eMail = '', string $phone = '', string $tg = '', string $notification = 'tgEmail', bool $enabled = true, string $defaultRoute = '', bool|string|null $persistentToken = false, int $primaryGroup = -1): bool
    {
        if (!in_array($notification, ["none", "tgEmail", "emailTg", "tg", "email"]))
            return false;

        try {
            $sth = $this->db->prepare("update core_users set real_name = :real_name, e_mail = :e_mail, phone = :phone, tg = :tg, notification = :notification, enabled = :enabled, default_route = :default_route, primary_group = :primary_group where uid = $uid");

            if ($persistentToken && strlen(trim($persistentToken)) === 32 && $uid && $enabled) {
                $this->redis->set("persistent_" . trim($persistentToken) . "_" . $uid, json_encode([
                    "uid" => $uid,
                    "login" => $this->db->get("select login from core_users where uid = $uid", false, false, ["fieldlify"]),
                    "started" => time(),
                ]));
            } else {
                $_keys = $this->redis->keys("persistent_*_" . $uid);

                foreach ($_keys as $_key)
                    $this->redis->del($_key);
            }

            if (!$enabled) {
                $_keys = $this->redis->keys("auth_*_" . $uid);

                foreach ($_keys as $_key)
                    $this->redis->del($_key);
            }

            return $sth->execute([
                ":real_name" => trim($realName),
                ":e_mail" => trim($eMail),
                ":phone" => trim($phone),
                ":tg" => trim($tg),
                ":notification" => trim($notification),
                ":enabled" => $enabled ? "1" : "0",
                ":default_route" => trim($defaultRoute),
                ":primary_group" => $primaryGroup,
            ]);
        } catch (Exception $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    /**
     * get uid by e-mail
     *
     * @param string $eMail e-mail
     *
     * @return false|integer
     */

    public function getUidByEMail(string $eMail): bool|int
    {
        try {
            $sth = $this->db->prepare("select uid from core_users where e_mail = :e_mail");

            if ($sth->execute([":e_mail" => $eMail])) {
                $users = $sth->fetchAll(\PDO::FETCH_ASSOC);

                if (count($users)) return (int)$users[0]["uid"];
                else return false;
            } else return false;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * returns class capabilities
     *
     * @return array
     */

    public function capabilities(): array
    {
        return ["mode" => "rw"];
    }

    public function getUidByLogin(string $login): int|bool
    {
        try {
            $users = $this->db->get("select uid from core_users where login = :login", ["login" => $login], ["uid" => "uid"]);

            if (count($users)) return (int)$users[0]["uid"];
            else return false;
        } catch (Exception) {
            return false;
        }
    }

    private function generate_password(int $length = 8): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }
}
