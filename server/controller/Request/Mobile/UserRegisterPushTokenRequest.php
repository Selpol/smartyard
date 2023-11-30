<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string|null $pushToken
 * @property-read string|null $voipToken
 *
 * @property-read bool $production
 *
 * @property-read string $platform
 *
 * @property-read bool $voipEnabled
 */
readonly class UserRegisterPushTokenRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'pushToken' => [filter()->fullSpecialChars(), rule()->clamp(16)],
            'voipToken' => [filter()->fullSpecialChars(), rule()->clamp(16)],

            'production' => [filter()->default(false), rule()->bool()],

            'platform' => rule()->required()->in(['ios', 'android', 'huawei'])->nonNullable(),

            'voipEnabled' => [filter()->default(true), rule()->bool()]
        ];
    }
}