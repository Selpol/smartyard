<?php declare(strict_types=1);

namespace Selpol\Service\Auth\Token;

use Selpol\Service\Auth\AuthTokenInterface;

/**
 * @implements AuthTokenInterface<string>
 */
class RedisAuthToken implements AuthTokenInterface
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getIdentifierName(): string
    {
        return 'redis';
    }

    public function getIdentifier(): string|int
    {
        return $this->value;
    }

    public function getOriginalValue(): mixed
    {
        return $this->value;
    }
}