<?php declare(strict_types=1);

namespace Selpol\Controller\Request;

use Selpol\Framework\Router\Route\RouteRequest;
use Selpol\Framework\Validator\ValidatorOnItemInterface;

/**
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class PageRequest extends RouteRequest
{
    /**
     * @return array<string, ValidatorOnItemInterface|array<ValidatorOnItemInterface>>
     */
    public static function getExtendValidate(): array
    {
        return [];
    }

    public static function getValidate(): array
    {
        return array_merge(
            [
                'page' => [filter()->default(0), rule()->int()->clamp(0)->exist()],
                'size' => [filter()->default(10), rule()->int()->clamp(1, 1000)->exist()]
            ],
            static::getExtendValidate()
        );
    }
}
