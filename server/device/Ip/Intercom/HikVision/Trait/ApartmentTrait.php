<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision\Trait;

use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;

trait ApartmentTrait
{
    /**
     * @return Apartment[]
     */
    public function getApartments(): array
    {
        return [];
    }

    public function getApartment(int $apartment): ?Apartment
    {
        return null;
    }

    public function addApartment(Apartment $apartment): void
    {
    }

    public function setApartment(Apartment $apartment): void
    {
    }

    public function setApartmentHandset(int $apartment, bool $value): void
    {

    }

    public function removeApartment(Apartment|int $apartment): void
    {
    }

    public function clearApartments(): void
    {
    }
}