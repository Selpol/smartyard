<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор стримера
 *
 * @property-read string $stream_id Идентификатор потока
 *
 * @property-read string $input Входящий поток
 *
 * @property-read string $input_type Тип входящего потока
 * @property-read string $output_type Тип выходящего потока
 */
readonly class StreamerRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'stream_id' => rule()->required()->string()->nonNullable(),

            'input' => rule()->required()->string()->clamp(0, 128)->nonNullable(),

            'input_type' => rule()->required()->in(['rtsp'])->nonNullable(),
            'output_type' => rule()->required()->in(['rtc'])->nonNullable(),
        ];
    }
}