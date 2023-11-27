<?php declare(strict_types=1);

namespace Selpol\Controller;

use Selpol\Framework\Router\Route\RouteController;
use Selpol\Service\Auth\AuthTokenInterface;
use Selpol\Service\Auth\AuthUserInterface;
use Selpol\Service\AuthService;

readonly abstract class RbtController extends RouteController
{
    protected function getToken(): AuthTokenInterface
    {
        return container(AuthService::class)->getTokenOrThrow();
    }

    protected function getUser(): AuthUserInterface
    {
        return container(AuthService::class)->getUserOrThrow();
    }
}