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
        set_time_limit(480);

        $validate = validator($params, ['_id' => rule()->id(), 'override' => rule()->required()->bool()->nonNullable()]);

        $uuid = task(new QrTask($validate['_id'], $validate['override']))->sync();

        $file = container(FileFeature::class)->getFile($uuid);

        return response()
            ->withBody($file->stream)
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $uuid . '.zip"');
    }

    public static function index(): array
    {
        return ['POST' => '[Deprecated] [QR] Получить Qr для дома'];
    }
}