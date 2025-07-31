<?php

declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Dvr;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $id
 *
 * @property-read string $container
 * @property-read string $stream
 *
 * @property-read int|null $time
 */
readonly class DvrVideoRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->required()->string()->nonNullable(),

            'container' => rule()->required()->in(['rtsp', 'hls', 'streamer_rtc', 'streamer_rtsp'])->nonNullable(),
            'stream' => rule()->required()->in(['camera', 'online', 'archive'])->nonNullable(),

            'time' => rule()->int(),

            'capabilities' => rule()->array(),

            'capabilities.*.type' => rule()->string()->exist(),

            'capabilities.*.bitrate' => rule()->array()->exist(),
            'capabilities.*.bitrate.*' => rule()->int()->exist(),

            'capabilities.*.profiles' => rule()->array()->exist(),
            'capabilities.*.profiles.*' => rule()->array()->exist(),

            'capabilities.*.hd' => rule()->array()->exist(),
            'capabilities.*.hd.first' => rule()->bool()->exist(),
            'capabilities.*.hd.second' => rule()->float()->exist(),

            'capabilities.*.fhd' => rule()->array()->exist(),
            'capabilities.*.fhd.first' => rule()->bool()->exist(),
            'capabilities.*.fhd.second' => rule()->float()->exist(),

            'capabilities.*.hufd' => rule()->array()->exist(),
            'capabilities.*.hufd.first' => rule()->bool()->exist(),
            'capabilities.*.hufd.second' => rule()->float()->exist(),

            'capabilities.*.ufd' => rule()->array()->exist(),
            'capabilities.*.ufd.first' => rule()->bool()->exist(),
            'capabilities.*.ufd.second' => rule()->float()->exist(),
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Идентификатор',

            'container' => 'Тип контейнера',
            'stream' => 'Тип потока',

            'time' => 'Время',

            'capabilities' => 'Возможности устройства'
        ];
    }
}
