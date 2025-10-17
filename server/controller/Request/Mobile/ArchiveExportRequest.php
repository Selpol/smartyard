<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 *
 * @property-read int $from
 * @property-read int $to
 */
readonly class ArchiveExportRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'from' => rule()->required()->int()->nonNullable(),
            'to' => rule()->required()->int()->nonNullable()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор',

            'from' => 'Дата начала',
            'to' => 'Дата конца'
        ];
    }
}