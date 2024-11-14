<?php declare(strict_types=1);

namespace Selpol\Controller;

use Selpol\Service\Auth\AuthTokenInterface;
use Selpol\Service\Auth\User\SubscriberAuthUser;
use Selpol\Service\AuthService;

readonly abstract class MobileRbtController extends RbtController
{
    protected function getToken(): AuthTokenInterface
    {
        return container(AuthService::class)->getTokenOrThrow();
    }

    protected function getUser(): SubscriberAuthUser
    {
        return container(AuthService::class)->getUserOrThrow();
    }
}