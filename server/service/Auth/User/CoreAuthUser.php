<?php declare(strict_types=1);

namespace Selpol\Service\Auth\User;

use Selpol\Service\Auth\AuthUserInterface;

/**
 * @implements AuthUserInterface<array>
 */
readonly class CoreAuthUser implements AuthUserInterface
{
    public function __construct(private array $value)
    {
    }

    public function getIdentifier(): string|int
    {
        return $this->value['uid'];
    }

    public function getUsername(): ?string
    {
        return $this->value['login'];
    }

    public function getOriginalValue(): array
    {
        return $this->value;
    }

    public function canScope(): bool
    {
        return true;
    }
}