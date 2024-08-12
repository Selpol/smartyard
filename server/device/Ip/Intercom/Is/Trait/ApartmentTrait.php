<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;

trait ApartmentTrait
{
    public function getApartments(): array
    {
        $response = $this->get('/panelCode');

        return array_map(static fn(array $value) => new Apartment(
            $value['panelCode'],
            $value['callsEnabled']['handset'],
            $value['callsEnabled']['sip'],
            $value['resistances']['answer'],
            $value['resistances']['quiescent'],
            $value['callsEnabled']['sip'] ? [sprintf('1%09d', $value['panelCode'])] : []
        ), $response);
    }

    public function getApartment(int $apartment): ?Apartment
    {
        $response = $this->get('/panelCode/' . $apartment);

        if (!array_key_exists('panelCode', $response)) {
            return null;
        }

        return new Apartment(
            $response['panelCode'],
            $response['callsEnabled']['handset'],
            $response['callsEnabled']['sip'],
            $response['resistances']['answer'],
            $response['resistances']['quiescent'],
            $response['callsEnabled']['sip'] ? [sprintf('1%09d', $response['panelCode'])] : []
        );
    }

    public function addApartment(Apartment $apartment): void
    {
        $this->post('/panelCode/' . $apartment->apartment, [
            'panelCode' => $apartment->apartment,
            'callsEnabled' => ['handset' => $apartment->handset, 'sip' => $apartment->sip],
            'soundOpenTh' => null,
            'typeSound' => 3,
            'resistances' => ['answer' => $apartment->answer, 'quiescent' => $apartment->quiescent]
        ]);
    }

    public function setApartment(Apartment $apartment): void
    {
        $this->put('/panelCode/' . $apartment->apartment, [
            'panelCode' => $apartment->apartment,
            'callsEnabled' => ['handset' => $apartment->handset, 'sip' => $apartment->sip],
            'soundOpenTh' => null,
            'typeSound' => 3,
            'resistances' => ['answer' => $apartment->answer, 'quiescent' => $apartment->quiescent]
        ]);
    }

    public function setApartmentHandset(int $apartment, bool $value): void
    {
        $this->put('/panelCode/' . $apartment, ['callsEnabled' => ['handset' => $value]]);
    }

    public function removeApartment(Apartment|int $apartment): void
    {
        if ($apartment instanceof Apartment) {
            $this->delete('panelCode/' . $apartment->apartment);
        } else {
            $this->delete('panelCode/' . $apartment);
        }
    }

    public function clearApartments(): void
    {
        $this->delete('/panelCode/clear');
    }
}