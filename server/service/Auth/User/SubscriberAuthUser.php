<?php declare(strict_types=1);

namespace Selpol\Service\Auth\User;

use Selpol\Service\Auth\AuthUserInterface;

/**
 * @implements AuthUserInterface<array>
 */
readonly class SubscriberAuthUser implements AuthUserInterface
{
    public function __construct(private array $value)
    {
    }

    public function getIdentifier(): string|int
    {
        return $this->value['subscriberId'];
    }

    public function getUsername(): ?string
    {
        return null;
    }

    public function getOriginalValue(): array
    {
        return $this->value;
    }

    public function getRole(): int {
        return $this->value['role'];
    }

    public function canScope(): bool
    {
        return false;
    }
}