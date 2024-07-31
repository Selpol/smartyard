<?php

namespace Selpol\Feature\Authentication\Internal;

use PDO;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Feature\Authentication\AuthenticationFeature;

readonly class InternalAuthenticationFeature extends AuthenticationFeature
{
    public function checkAuth(string $login, string $password): int|bool
    {
        $user = CoreUser::fetch(criteria()->equal('login', $login)->equal('enabled', 1), setting: setting()->columns(['uid', 'password']));

        if ($user == null)
            return 'Пользователь не найден';

        if (!password_verify($password, $user->password))
            return 'Пароль не верен';

        return $user->uid;
    }
}