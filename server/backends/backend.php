<?php

namespace backends;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Redis;
use Selpol\Service\DatabaseService;

abstract class backend
{
    protected int $uid;

    protected array $config;

    protected DatabaseService $db;
    protected Redis $redis;

    protected string $login;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(array $config, DatabaseService $db, Redis $redis, bool $login = false)
    {
        global $params;

        $this->config = $config;

        $this->db = $db;
        $this->redis = $redis;

        $this->login = $login ?: ((is_array($params) && array_key_exists("_login", $params)) ? $params["_login"] : "-");

        $this->uid = match ($this->login) {
            "-" => -1,
            "admin" => 0,
            default => backend("users")->getUidByLogin($this->login),
        };
    }

    /**
     * returns class capabilities
     *
     * @return array|bool
     */
    public function capabilities(): array|bool
    {
        return false;
    }

    /**
     * garbage collector
     *
     * @return int
     */
    public function cleanup(): int
    {
        return 0;
    }

    /**
     * access rights regulator
     *
     * @param $params
     * @return boolean
     */
    public function allow($params): bool
    {
        return false;
    }

    /**
     * @param $part = [ 'minutely', '5min', 'hourly', 'daily', 'monthly' ]
     * @return false
     */
    public function cron(string $part): bool
    {
        return true;
    }

    /**
     * @param $uid integer
     * @param $login string
     * @return void
     */
    public function setCredentials(int $uid, string $login): void
    {
        $this->uid = $uid;
        $this->login = $login;
    }
}
