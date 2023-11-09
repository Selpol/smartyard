<?php

namespace Selpol\Feature\User;

use Selpol\Feature\Feature;
use Selpol\Feature\User\Internal\InternalUserFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalUserFeature::class)]
readonly abstract class UserFeature extends Feature
{
    abstract public function getUsers(): bool|array;

    abstract public function getUser(int $uid): bool|array;

    abstract public function getUidByEMail(string $eMail): bool|int;

    abstract public function getUidByLogin(string $login): int|bool;

    abstract public function addUser(string $login, ?string $realName = null, ?string $eMail = null, ?string $phone = null): int|bool;

    abstract public function deleteUser(int $uid): bool;
}