<?php

namespace Selpol\Feature\Authorization\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Authorization\AuthorizationFeature;

class InternalAuthorizationFeature extends AuthorizationFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function allowedMethods(int $uid): array
    {
        return $this->methods();
    }
}