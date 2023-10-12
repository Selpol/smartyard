<?php

namespace Selpol\Feature\Oauth;

use Selpol\Feature\Feature;
use Selpol\Feature\Oauth\Internal\InternalOauthFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalOauthFeature::class)]
abstract class OauthFeature extends Feature
{
    public abstract function validateJwt(string $value): ?array;

    public abstract function register(string $mobile): ?string;
}