<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор домофона
 * 
 * @property-read int $enabled Состояние домофона
 * 
 * @property-read string $model Модель домофона
 * @property-read string $server Сервер
 * @property-read string $url URL Домофона
 * @property-read string $credentials Авторизация
 * 
 * @property-read int $first_time Первая синхронизация
 * 
 * @property-read int|null $nat NAT Режим
 * 
 * @property-read string|null $ip IP домофона
 * 
 * @property-read string|null $comment Комментарий
 * 
 * @property-read string|null $config Конфигурация домофона
 * 
 * @property-read bool|null $hidden Скрытый домофон
 */
readonly class IntercomUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'enabled' => rule()->required()->int()->nonNullable(),

            'model' => rule()->required()->in(array_keys(IntercomModel::models()))->nonNullable(),
            'server' => rule()->required()->string()->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),
            'credentials' => rule()->required()->string()->nonNullable(),

            'first_time' => rule()->int()->exist(),

            'nat' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string(),

            'config' => rule()->string(),

            'hidden' => rule()->bool()
        ];
    }
}