<?php

namespace Selpol\Service;

use Exception;
use PDO;
use PDOException;
use Selpol\Container\ContainerDispose;

class DatabaseService implements ContainerDispose
{
    private ?PDO $connection;

    public function __construct()
    {
        $this->connection = new PDO(config('db.dsn'), config('db.username'), config('db.password'), config('db.options'));

        $this->connection->setAttribute(PDO::ATTR_TIMEOUT, 60);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getConnection(): ?PDO
    {
        return $this->connection;
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func([$this->connection, $name], $arguments);
    }

    public function insert(string $query, array|bool $params = [], array|bool $options = []): bool|int|string
    {
        if (is_bool($params))
            $params = [];

        if (is_bool($options))
            $options = [];

        try {
            $sth = $this->connection->prepare($query);

            if ($sth->execute($this->remap($params))) {
                try {
                    return $this->connection->lastInsertId();
                } catch (Exception) {
                    return -1;
                }
            } else return false;
        } catch (PDOException $e) {
            if (!in_array("silent", $options)) {
                logger('database')->error($e);

                last_error($e->errorInfo[2] ?: $e->getMessage());
                error_log(print_r($e, true));
            }

            return false;
        } catch (Exception $e) {
            logger('database')->error($e);

            last_error($e->getMessage());
            error_log(print_r($e, true));

            return false;
        }
    }

    public function modify(string $query, array|bool $params = [], array|bool $options = []): bool|int
    {
        if (is_bool($params))
            $params = [];

        if (is_bool($options))
            $options = [];

        try {
            $sth = $this->connection->prepare($query);

            if ($sth->execute($this->remap($params)))
                return $sth->rowCount();
            else return false;
        } catch (PDOException $e) {
            if (!in_array("silent", $options)) {
                logger('database')->error($e);

                last_error($e->errorInfo[2] ?: $e->getMessage());
                error_log(print_r($e, true));
            }

            return false;
        } catch (Exception $e) {
            logger('database')->error($e);

            last_error($e->getMessage());
            error_log(print_r($e, true));

            return false;
        }
    }

    public function modifyEx(string $query, array|bool $map, array|bool $params, array|bool $options = []): bool
    {
        if (is_bool($map))
            $map = [];

        if (is_bool($params))
            $params = [];

        if (is_bool($options))
            $options = [];

        $mod = false;

        try {
            foreach ($map as $db => $param) {
                if (array_key_exists($param, $params)) {
                    $sth = $this->connection->prepare(sprintf($query, $db, $db));

                    if ($sth->execute($this->remap([$db => $params[$param]])))
                        if ($sth->rowCount())
                            $mod = true;

                }
            }
            return $mod;
        } catch (PDOException $e) {
            if (!in_array("silent", $options)) {
                logger('database')->error($e);

                last_error($e->errorInfo[2] ?: $e->getMessage());
                error_log(print_r($e, true));
            }

            return false;
        } catch (Exception $e) {
            logger('database')->error($e);

            last_error($e->getMessage());
            error_log(print_r($e, true));

            return false;
        }
    }

    public function get(string $query, array|bool $params = [], array|bool $map = [], array $options = []): bool|array
    {
        if (is_bool($params))
            $params = [];

        if (is_bool($map))
            $map = [];

        try {
            if ($params) {
                $sth = $this->connection->prepare($query);

                if ($sth->execute($params))
                    $a = $sth->fetchAll(PDO::FETCH_ASSOC);
                else return false;
            } else $a = $this->connection->query($query, PDO::FETCH_ASSOC)->fetchAll();

            $r = [];

            if ($map) {
                foreach ($a as $f) {
                    $x = [];

                    foreach ($map as $k => $l)
                        $x[$l] = $f[$k];

                    $r[] = $x;
                }
            } else $r = $a;

            if (in_array('singlify', $options)) {
                if (count($r) === 1) return $r[0];
                else return false;
            }

            if (in_array('fieldlify', $options)) {
                if (count($r) === 1) return $r[0][array_key_first($r[0])];
                else return false;
            }

            return $r;
        } catch (PDOException $e) {
            if (!in_array("silent", $options)) {
                logger('database')->error($e);

                last_error($e->errorInfo[2] ?: $e->getMessage());
                error_log(print_r($e, true));
            }

            return false;
        } catch (Exception $e) {
            logger('database')->error($e);

            last_error($e->getMessage());
            error_log(print_r($e, true));

            return false;
        }
    }

    public function dispose(): void
    {
        $this->connection = null;
    }

    private function remap(array|bool $map): array
    {
        $result = [];

        if ($map) {
            foreach ($map as $key => $value) {
                if (is_null($value)) $result[$key] = $value;
                else $result[$key] = is_string($value) ? trim($value) : $value;
            }
        }

        return $result;
    }
}