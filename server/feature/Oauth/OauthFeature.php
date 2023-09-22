<?php

namespace Selpol\Feature\Oauth;

use Selpol\Feature\Feature;

abstract class OauthFeature extends Feature
{
    public abstract function validateJwt(string $value): ?array;

    public abstract function register(string $mobile): ?string;
}