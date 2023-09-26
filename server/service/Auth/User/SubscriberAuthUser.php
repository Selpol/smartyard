<?php declare(strict_types=1);

namespace Selpol\Service\Auth\User;

use Selpol\Service\Auth\AuthUserInterface;

/**
 * @implements AuthUserInterface<array>
 */
class SubscriberAuthUser implements AuthUserInterface
{
    private array $value;

    public function __construct(array $value)
    {
        $this->value = $value;
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
}