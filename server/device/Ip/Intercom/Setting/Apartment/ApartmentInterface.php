<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Apartment;

interface ApartmentInterface
{
    /**
     * @return Apartment[]
     */
    public function getApartments(): array;

    public function addApartment(Apartment $apartment): void;

    public function setApartment(Apartment $apartment): void;

    public function removeApartment(Apartment|int $apartment): void;

    public function clearApartments(): void;
}