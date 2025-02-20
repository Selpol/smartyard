<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Controller\Request\PageRequest;
use Selpol\Device\Ip\Intercom\IntercomModel;

/**
 * @property-read int $id Идентификатор домофона
 * 
 * @property-read int $enabled Состояние домофона
 * 
 * @property-read string $model Модель домофона
 * @property-read string $server Сервер
 * @property-read string $url URL Домофона
 * @property-read string $credentials Авторизация
 * @property-read string $dtmf DTMF Открытия реле
 * 
 * @property-read int $first_time Первая синхронизация
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
readonly class IntercomUpdateRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'id' => rule()->id(),

            'enabled' => rule()->required()->int()->nonNullable(),

            'model' => rule()->required()->in(array_keys(IntercomModel::models()))->nonNullable(),
            'server' => rule()->required()->string()->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),
            'credentials' => rule()->required()->string()->nonNullable(),
            'dtmf' => rule()->required()->string()->in(["*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"])->nonNullable(),

            'first_time' => rule()->int()->exist(),

            'nat' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string(),

            'sos_number' => rule()->string(),

            'config' => rule()->string(),

            'hidden' => rule()->bool()
        ];
    }
}