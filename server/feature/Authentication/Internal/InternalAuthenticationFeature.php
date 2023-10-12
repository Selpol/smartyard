<?php

namespace Selpol\Feature\Authentication\Internal;

use PDO;
use Selpol\Feature\Authentication\AuthenticationFeature;

class InternalAuthenticationFeature extends AuthenticationFeature
{
    public function checkAuth(string $login, string $password): int|bool
    {
        $sth = $this->getDatabase()->getConnection()->prepare("select uid, password from core_users where login = :login and enabled = 1");

        $sth->execute([":login" => $login]);

        $res = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (count($res) == 1 && password_verify($password, $res[0]["password"]))
            return $res[0]["uid"];
        else return false;
    }
}