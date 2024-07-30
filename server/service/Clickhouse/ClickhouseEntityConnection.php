<?php declare(strict_types=1);

namespace Selpol\Service\Clickhouse;

use Selpol\Framework\Client\ClientOption;
use Selpol\Framework\Entity\Database\EntityConnectionInterface;
use Selpol\Framework\Entity\Database\EntityStatementInterface;

readonly class ClickhouseEntityConnection implements EntityConnectionInterface
{
    private string $endpoint;
    private string $username;
    private string $password;

    public function __construct(string $endpoint, string $username, string $password)
    {
        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->password = $password;
    }

    public function statement(string $value): EntityStatementInterface
    {
        $value = trim($value);

        $option = new ClientOption();
        $option->basic($this->username, $this->password);

        if (str_starts_with(strtoupper(substr($value, 0, 6)), 'SELECT'))
            $value .= ' FORMAT JSON';

        return new ClickhouseEntityStatement($option, request('POST', uri($this->endpoint), ['Content-Type' => ['text/plain; charset=UTF-8']]), $value);
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