<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Dvr;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $id
 *
 * @property-read string $container
 * @property-read string $stream
 *
 * @property-read int|null $time
 *
 * @property-read bool|null $sub
 * @property-read bool|null $hw
 */
readonly class DvrVideoRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->required()->string()->nonNullable(),

            'container' => rule()->required()->in(['rtsp', 'hls', 'streamer_rtc', 'streamer_rtsp'])->nonNullable(),
            'stream' => rule()->required()->in(['online', 'archive'])->nonNullable(),

            'time' => rule()->int(),

            'sub' => rule()->bool(),
            'hw' => rule()->bool()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор',

            'container' => 'Тип контейнера',
            'stream' => 'Тип потока',

            'time' => 'Время',

            'sub' => 'Дополнительный поток',
            'hw' => 'Архив с устройства'
        ];
    }
}