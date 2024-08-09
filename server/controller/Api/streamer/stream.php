<?php

namespace Selpol\Controller\Api\streamer;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Feature\Streamer\ApiStream;
use Selpol\Feature\Streamer\StreamerFeature;

readonly class stream extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $server = StreamerServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        return self::success(container(StreamerFeature::class)->listStream($server));
    }

    public static function POST(array $params): ResponseInterface
    {
        $server = StreamerServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        $validate = validator([
            'id' => rule()->required()->string()->clamp(0, 128)->nonNullable(),

            'input' => rule()->required()->string()->clamp(0, 128)->nonNullable(),

            'input_type' => rule()->required()->in(['rtsp'])->nonNullable(),
            'output_type' => rule()->required()->in(['rtc'])->nonNullable(),
        ], $params);

        $stream = new ApiStream($validate['id'], $validate['input'], $validate['input_type'], $validate['output_type'], null, null);

        if (container(StreamerFeature::class)->addStream($server, $stream)) {
            return self::success();
        }

        return self::error('Не удалось добавить поток на стример', 404);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $server = StreamerServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        $validate = validator([
            'id' => rule()->required()->string()->clamp(0, 128)->nonNullable(),

            'input' => rule()->required()->string()->clamp(0, 128)->nonNullable(),

            'input_type' => rule()->required()->in(['rtsp'])->nonNullable(),
            'output_type' => rule()->required()->in(['rtc'])->nonNullable(),
        ], $params);

        $stream = new ApiStream($validate['id'], $validate['input'], $validate['input_type'], $validate['output_type'], null, null);

        if (container(StreamerFeature::class)->updateStream($server, $stream)) {
            return self::success();
        }

        return self::error('Не удалось обновить поток на стримере', 404);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $server = StreamerServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        $validate = validator(['id' => rule()->required()->string()->clamp(0, 128)->nonNullable()], $params);

        if (container(StreamerFeature::class)->deleteStream($server, $validate['id'])) {
            return self::success();
        }

        return self::error('Не удалось удалить поток на стримере', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Стример-Поток] Получить список потоков', 'POST' => '[Стример-Поток] Добавить поток', 'PUT' => '[Стример-Поток] Обновить поток', 'DELETE' => '[Стример-Поток] Удалить поток'];
    }
}