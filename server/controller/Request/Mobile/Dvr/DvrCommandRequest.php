<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Dvr;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $id
 *
 * @property-read string $container
 * @property-read string $stream
 *
 * @property-read string $command
 *
 * @property-read int|null $seek
 * @property-read int|null $speed
 *
 * @property-read string|null $token
 *
 * @property-read int|null $from
 * @property-read int|null $to
 */
readonly class DvrCommandRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->required()->string()->nonNullable(),

            'container' => rule()->required()->in(['rtsp', 'hls', 'rtc'])->nonNullable(),
            'stream' => rule()->required()->in(['online', 'archive'])->nonNullable(),

            'command' => rule()->required()->in(['play', 'pause', 'seek', 'speed', 'ping'])->nonNullable(),

            'seek' => rule()->int(),
            'speed' => rule()->int(),

            'token' => rule()->string(),

            'from' => rule()->int(),
            'to' => rule()->int()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор',

            'container' => 'Тип контейнера',
            'stream' => 'Тип потока',

            'command' => 'Команда',

            'seek' => 'Время',
            'speed' => 'Скорость',

            'token' => 'Токен',

            'from' => 'Начало действия архива',
            'to' => 'Конец действия архива'
        ];
    }
}