<?php

namespace Selpol\Service;

use Exception;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\Database\EntityStatementInterface;
use Selpol\Service\Clickhouse\ClickhouseEntityConnection;

#[Singleton]
readonly class ClickhouseService
{
    private ClickhouseEntityConnection $connection;

    public string $database;

    public function __construct()
    {
        $config = config_get('clickhouse');

        $this->connection = new ClickhouseEntityConnection($config['endpoint'], $config['username'], $config['password']);

        $this->database = config_get('feature.plog.database', 'default') ?? 'default';
    }

    public function statement(string $query): EntityStatementInterface
    {
        return $this->connection->statement($query);
    }

    public function select(string $query): array|bool
    {
        $plog = config_get('feature.plog');

        $host = $plog['host'];
        $port = $plog['port'];

        $username = $plog['username'];
        $password = $plog['password'];

        $curl = curl_init();
        $headers = [];

        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/plain; charset=UTF-8', 'X-ClickHouse-User: ' . $username, 'X-ClickHouse-Key: ' . $password]);

        curl_setopt(
            $curl,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers): int {
                $len = strlen($header);
                $header = explode(':', $header, 2);

                if (count($header) < 2) {
                    return $len;
                }

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, trim($query) . " FORMAT JSON");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, sprintf('http://%s:%s', $host, $port));
        curl_setopt($curl, CURLOPT_POST, true);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_VERBOSE, false);

        try {
            $raw = curl_exec($curl);

            $data = @json_decode($raw, true)['data'];
        } catch (Exception $exception) {
            file_logger('clickhouse')->error($exception);

            return false;
        }

        curl_close($curl);

        if (array_key_exists('x-clickhouseService-exception-code', $headers) && $headers['x-clickhouseService-exception-code'] !== []) {
            file_logger('clickhouse')->error(trim($raw));

            return false;
        }

        if (is_array($data)) {
            return $data;
        }

        return false;
    }
}