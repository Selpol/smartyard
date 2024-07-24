<?php declare(strict_types=1);

namespace Selpol\Service\Clickhouse;

use Selpol\Framework\Client\Client;
use Selpol\Framework\Client\ClientOption;
use Selpol\Framework\Entity\Database\EntityConnectionInterface;
use Selpol\Framework\Entity\Database\EntityStatementInterface;

readonly class ClickhouseEntityConnection implements EntityConnectionInterface
{
    private Client $client;

    private string $endpoint;
    private string $username;
    private string $password;

    public function __construct(Client $client, string $endpoint, string $username, string $password)
    {
        $this->client = $client;

        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->password = $password;
    }

    public function statement(string $value): EntityStatementInterface
    {
        $value = trim($value);

        $request = request('POST', $this->endpoint);

        $option = new ClientOption();
        $option->basic($this->username, $this->password);

        if (str_starts_with(strtoupper(substr($value, 0, 6)), 'SELECT'))
            $value .= ' FORMAT JSON';

        return new ClickhouseEntityStatement($this->client, $option, $request, $value);
    }

    public function lastInsertId(string $value): mixed
    {
        return null;
    }

    public function beginTransaction(): bool
    {
        return false;
    }

    public function inTransaction(): bool
    {
        return false;
    }

    public function commit(): bool
    {
        return false;
    }

    public function rollBack(): bool
    {
        return false;
    }
}