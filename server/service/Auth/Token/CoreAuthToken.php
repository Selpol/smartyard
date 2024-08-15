<?php declare(strict_types=1);

namespace Selpol\Service\Auth\Token;

use Selpol\Service\Auth\AuthTokenInterface;

/**
 * @implements AuthTokenInterface<string>
 */
readonly class CoreAuthToken implements AuthTokenInterface
{
    public function __construct(private string $value, private ?string $audJti)
    {
    }

    public function getIdentifierName(): string
    {
        return 'redis';
    }

    public function getIdentifier(): string|int
    {
        return $this->value;
    }

    public function getAudJti(): string|null
    {
        return $this->audJti;
    }

    public function getOriginalValue(): mixed
    {
        return $this->value;
    }
}