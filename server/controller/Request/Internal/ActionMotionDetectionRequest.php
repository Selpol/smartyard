<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $ip IP-адрес устройства
 * @property-read bool $motionActive Статус детекции
 */
readonly class ActionMotionDetectionRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'ip' => rule()->required()->ipV4()->nonNullable(),
            'motionActive' => rule()->required()->bool()->nonNullable()
        ];
    }
}