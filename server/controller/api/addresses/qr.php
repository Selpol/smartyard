<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;
    use Selpol\Feature\File\FileFeature;
    use Selpol\Task\Tasks\QrTask;

    /**
     * qr method
     */
    class qr extends api
    {
        public static function POST($params)
        {
            $validate = validator($params, ['_id' => rule()->id(), 'override' => rule()->required()->bool()->nonNullable()]);

            $uuid = task(new QrTask($validate['_id'], null, $validate['override']))->sync();

            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $uuid . '.zip"');

            echo container(FileFeature::class)->getFileBytes($uuid);

            exit(0);
        }

        public static function index(): array
        {
            return [
                "POST" => "[QR] Получить Qr для дома"
            ];
        }
    }
}