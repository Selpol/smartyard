<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;
use Selpol\Http\Exception\HttpException;
use Selpol\Service\DatabaseService;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;

class domophone extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id()]);

        $households = container(HouseFeature::class);

        return Api::ANSWER($households->getDomophone($validate['_id']));
    }

    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $domophoneId = $households->addDomophone($params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["nat"], $params["comment"]);

        if ($domophoneId) {
            static::modifyIp($domophoneId, $params['url']);

            return Api::ANSWER($domophoneId, 'domophoneId');
        }

        return Api::ERROR('Домофон не добавлена' . PHP_EOL . last_error());
    }

    public static function PUT(array $params): array
    {
        $households = container(HouseFeature::class);

        $domophone = $households->getDomophone($params['_id']);

        if ($domophone) {
            $success = $households->modifyDomophone($params["_id"], $params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["firstTime"], $params["nat"], $params["locksAreOpen"], $params["comment"]);

            if ($success) {
                if (array_key_exists('configure', $params) && $params['configure'])
                    task(new IntercomConfigureTask($params['_id']))->high()->dispatch();

                if ($domophone['url'] !== $params['url'])
                    static::modifyIp($domophone['domophoneId'], $params['url']);

                static::unlock($params['_id'], boolval($domophone['locksAreOpen']));
            }

            return Api::ANSWER($success);
        }

        return Api::ERROR('Домофон не найден');
    }

    public static function DELETE(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->deleteDomophone($params["_id"]);

        return Api::ANSWER($success);
    }

    public static function index(): array
    {
        return ['GET' => '[Дом] Получить домофон', 'PUT' => '[Дом] Обновить домофон', 'POST' => '[Дом] Создать домофон', 'DELETE' => '[Дом] Удалить домофон'];
    }

    private static function modifyIp(int $id, string $url): void
    {
        $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
            container(DatabaseService::class)->modify('UPDATE houses_domophones SET ip = :ip WHERE house_domophone_id = :id', ['ip' => $ip, 'id' => $id]);
    }

    private static function unlock(int $id, bool $value): void
    {
        $device = intercom($id);

        if (!$device->ping())
            throw new HttpException(message: 'Устройство не доступно');

        $device->unlock($value);
    }
}