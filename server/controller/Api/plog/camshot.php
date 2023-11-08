<?php

namespace Selpol\Controller\Api\plog;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\File\FileFeature;

readonly class camshot extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return response()
            ->withHeader('Content-Type', 'image/jpeg')
            ->withBody(stream(container(FileFeature::class)->getFileStream(container(FileFeature::class)->fromGUIDv4($params['_id']))));
    }

    public static function index(): array|bool
    {
        return ['GET' => '[События] Получить скриншот'];
    }
}