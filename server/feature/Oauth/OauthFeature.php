<?php declare(strict_types=1);

namespace Selpol\Feature\Oauth;

use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

define("Selpol\Feature\Oauth\BACKEND", config_get('feature.oauth.backend'));

#[Singleton(BACKEND)]
readonly abstract class OauthFeature extends Feature
{
    public abstract function validateJwt(string $value): ?array;

    public abstract function register(string $mobile): ?string;
}