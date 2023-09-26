<?php declare(strict_types=1);

namespace Selpol\Service\Auth;

/**
 * @template T
 */
interface AuthTokenInterface
{
    /**
     * Получить имя уникаольного идентификатора токена для пользователя
     * @return string
     */
    public function getIdentifierName(): string;

    /**
     * Получить уникальный идентификатор токена для пользователя
     * @return string|int
     */
    public function getIdentifier(): string|int;

    /**
     * Получить оригинальное значение токена
     * @return T
     */
    public function getOriginalValue(): mixed;
}