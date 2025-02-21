<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $enabled Состояние домофона
 * 
 * @property-read string $model Модель домофона
 * @property-read string $server Сервер
 * @property-read string $url URL Домофона
 * @property-read string $credentials Авторизация
 * @property-read string $dtmf DTMF Открытия реле
 * 
 * @property-read int|null $nat NAT Режим
 * 
 * @property-read string|null $ip IP домофона
 * 
 * @property-read string|null $comment Комментарий
 * 
 * @property-read string|null $sos_number Номер SOS
 * 
 * @property-read string|null $config Конфигурация домофона
 * 
 * @property-read bool|null $hidden Скрытый домофон
 */
readonly class IntercomStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'enabled' => rule()->int()->exist(),

            'model' => rule()->in(array_keys(IntercomModel::models()))->exist(),
            'server' => rule()->string()->exist(),
            'url' => rule()->url()->exist(),
            'credentials' => rule()->string()->exist(),
            'dtmf' => rule()->string()->in(["*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"])->exist(),

            'nat' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string(),

            'sos_number' => rule()->string(),

            'config' => rule()->string(),

            'hidden' => rule()->bool()
        ];
    }
}