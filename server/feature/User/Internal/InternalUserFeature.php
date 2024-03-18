<?php

namespace Selpol\Feature\User\Internal;

use PDO;
use Selpol\Feature\User\UserFeature;
use Throwable;

readonly class InternalUserFeature extends UserFeature
{
    public function getUsers(): bool|array
    {
        try {
            $users = $this->getDatabase()->getConnection()->query("select uid, login, real_name, e_mail, phone, tg, enabled, last_login from core_users order by uid", PDO::FETCH_ASSOC)->fetchAll();
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
                    "lastLogin" => $u["last_login"]
                ];
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
            $user = $this->getDatabase()->getConnection()->query("select uid, login, real_name, e_mail, phone, tg, notification, enabled, default_route from core_users where uid = $uid", PDO::FETCH_ASSOC)->fetchAll();

            if (count($user)) {
                return [
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
            } else return false;
        } catch (Throwable $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function addUser(string $login, ?string $realName = null, ?string $eMail = null, ?string $phone = null): int|bool
    {
        $login = trim($login);
        $password = generate_password();

        try {
            $connection = $this->getDatabase()->getConnection();

            $sth = $connection->prepare("insert into core_users (login, password, real_name, e_mail, phone, enabled) values (:login, :password, :real_name, :e_mail, :phone, 1)");

            if ($sth->execute([
                'login' => $login,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'real_name' => $realName ? trim($realName) : null,
                'e_mail' => $eMail ? trim($eMail) : null,
                'phone' => $phone ? trim($phone) : null,
            ]))
                return $connection->lastInsertId();
            else return false;
        } catch (Throwable $e) {
            error_log(print_r($e, true));

            return false;
        }
    }

    public function deleteUser(int $uid): bool
    {
        if ($uid > 0) {
            try {
                $this->getDatabase()->getConnection()->prepare("delete from core_users where uid = :uid")->execute(['uid' => $uid]);
            } catch (Throwable $e) {
                error_log(print_r($e, true));

                return false;
            }

            return true;
        } else return false;
    }

    public function getUidByEMail(string $eMail): bool|int
    {
        try {
            $sth = $this->getDatabase()->getConnection()->prepare("select uid from core_users where e_mail = :e_mail");

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
}