<?php

namespace Selpol\Service\Auth\Token;

use Selpol\Service\Auth\AuthTokenInterface;

/**
 * @implements AuthTokenInterface<array>
 */
class JwtAuthToken implements AuthTokenInterface
{
    private array $value;

    public function __construct(array $value)
    {
        $this->value = $value;
    }

    public function getIdentifierName(): string
    {
        return $this->value['aud'];
    }

    public function getIdentifier(): string
    {
        return $this->value['sub'];
    }

    public function getOriginalValue(): array
    {
        return $this->value;
    }
}