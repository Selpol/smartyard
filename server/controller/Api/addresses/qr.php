<?php

namespace Selpol\Controller\Api\addresses;

use Selpol\Controller\Api\Api;
use Selpol\Feature\File\FileFeature;
use Selpol\Framework\Http\Response;
use Selpol\Task\Tasks\QrTask;

readonly class qr extends Api
{
    public static function POST(array $params): Response
    {
        $validate = validator($params, ['_id' => rule()->id(), 'override' => rule()->required()->bool()->nonNullable()]);

        $uuid = task(new QrTask($validate['_id'], null, $validate['override']))->sync();

        return response()
            ->withBody(stream(container(FileFeature::class)->getFileStream($uuid)))
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $uuid . '.zip"');
    }

    public static function index(): array
    {
        return ['POST' => '[QR] Получить Qr для дома'];
    }
}