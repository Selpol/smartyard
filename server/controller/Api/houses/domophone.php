<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Repository\Device\DeviceIntercomRepository;

readonly class domophone extends Api
{
    public static function GET(array $params): array
    {
        $intercom = container(DeviceIntercomRepository::class)->findById($params['_id'])->toArrayMap([
            'house_domophone_id' => 'domophoneId',
            'enabled' => 'enabled',
            'model' => 'model',
            'server' => 'server',
            'url' => 'url',
            'credentials' => 'credentials',
            'dtmf' => 'dtmf',
            'first_time' => 'firstTime',
            'nat' => 'nat',
            'comment' => 'comment',
            'ip' => 'ip',
            'sos_number' => 'sosNumber'
        ]);

        $intercom['json'] = IntercomModel::models()[$intercom['model']]->toArray();

        return self::ANSWER($intercom);
    }

    public static function POST(array $params): array
    {
        $intercom = new DeviceIntercom();

        self::set($intercom, $params);

        if (container(DeviceIntercomRepository::class)->insert($intercom))
            return self::ANSWER($intercom->house_domophone_id, 'domophoneId');

        return Api::ERROR('Неудалось добавить домофон');
    }

    public static function PUT(array $params): array
    {
        $intercom = container(DeviceIntercomRepository::class)->findById($params['_id']);

        self::set($intercom, $params);

        if (container(DeviceIntercomRepository::class)->update($intercom))
            return self::ANSWER($intercom->house_domophone_id, 'domophoneId');

        return Api::ERROR('Неудалось обновить домофон');
    }

    public static function DELETE(array $params): array
    {
        $intercom = container(DeviceIntercomRepository::class)->findById($params['_id']);

        if (container(DeviceIntercomRepository::class)->delete($intercom))
            return self::ANSWER();

        return Api::ERROR('Неудалось удалить домофон');
    }

    public static function index(): array
    {
        return ['GET' => '[Дом] Получить домофон', 'PUT' => '[Дом] Обновить домофон', 'POST' => '[Дом] Создать домофон', 'DELETE' => '[Дом] Удалить домофон'];
    }

    private static function set(DeviceIntercom $intercom, array $params): void
    {
        $intercom->enabled = $params['enabled'];

        $intercom->model = $params['model'];
        $intercom->server = $params['server'];
        $intercom->url = $params['url'];
        $intercom->credentials = $params['credentials'];
        $intercom->dtmf = $params['dtmf'];

        if (array_key_exists('firstTime', $params))
            $intercom->first_time = $params['firstTime'];

        $intercom->nat = $params['nat'];

        $intercom->comment = $params['comment'];

        if (array_key_exists('sosNumber', $params))
            $intercom->sos_number = $params['sosNumber'];

        $ip = gethostbyname(parse_url($intercom->url, PHP_URL_HOST));

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
            $intercom->ip = $ip;
    }
}