<?php

declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr;

use JsonSerializable;

readonly class DvrCamera implements JsonSerializable
{
    /**
     * Идентификатор камеры на сервере
     */
    public string $id;

    /**
     * Название камеры
     */
    public string $title;

    /**
     * Url камеры с которого сервер забирает поток, в идеале RTSP
     */
    public string $url;

    /**
     * Ip адрес камеры
     */
    public string $ip;

    /**
     * Пользователь от камеры
     */
    public string $user;

    /**
     * Пароль от камеры
     */
    public string $password;

    public function __construct(string $id, string $title, string $url, string $ip, string $user, string $password)
    {
        $this->id = $id;
        $this->title = $title;
        $this->url = $url;
        $this->ip = $ip;

        $this->user = $user;
        $this->password = $password;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'ip' => $this->ip,

            'user' => $this->user,
            'password' => $this->password
        ];
    }
}
