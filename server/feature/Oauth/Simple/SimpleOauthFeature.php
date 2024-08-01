<?php declare(strict_types=1);

namespace Selpol\Feature\Oauth\Simple;

use Selpol\Feature\Oauth\OauthFeature;

readonly class SimpleOauthFeature extends OauthFeature
{
    public function validateJwt(string $value): ?array
    {
        return null;
    }

    public function register(string $mobile): ?string
    {
        return $mobile;
    }
}