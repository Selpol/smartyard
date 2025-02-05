<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Apartment;

interface ApartmentInterface
{
    public function getDefaultAnswerLevel(): int;

    public function getDefaultQuiescentLevel(): int;

    /**
     * @return Apartment[]
     */
    public function getApartments(): array;

    public function getApartment(int $apartment): ?Apartment;

    public function addApartment(Apartment $apartment): void;

    public function setApartment(Apartment $apartment): void;

    public function setApartmentAudio(int $apartment, array $audios): void;

    public function setApartmentHandset(int $apartment, bool $value): void;

    public function removeApartment(Apartment|int $apartment): void;

    public function clearApartments(): void;
}