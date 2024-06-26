<?php declare(strict_types=1);

namespace Selpol\Service\Auth;

/**
 * @template T
 */
interface AuthUserInterface
{
    /**
     * Получить уникальный идентификатор пользователя
     * @return string|int
     */
    public function getIdentifier(): string|int;

    /**
     * Получить уникальный логин пользователя
     * @return string|null
     */
    public function getUsername(): ?string;

    /**
     * Получить оригинальное значение пользователя
     * @return T
     */
    public function getOriginalValue(): mixed;

    /**
     * Имеет ли пользователь возможность доступа к правам
     * @return bool
     */
    public function canScope(): bool;
}