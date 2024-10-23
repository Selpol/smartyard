<?php declare(strict_types=1);

namespace Selpol\Service\Auth\User;

use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Service\Auth\AuthUserInterface;

/**
 * @implements AuthUserInterface<CoreUser>
 */
readonly class CoreAuthUser implements AuthUserInterface
{
    public function __construct(private CoreUser $value)
    {
    }

    public function getIdentifier(): string|int
    {
        return $this->value->uid;
    }

    public function getUsername(): ?string
    {
        return $this->value->login;
    }

    public function getOriginalValue(): CoreUser
    {
        return $this->value;
    }

    public function canScope(): bool
    {
        return true;
    }
}