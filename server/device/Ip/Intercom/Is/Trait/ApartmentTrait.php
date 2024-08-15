<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;

trait ApartmentTrait
{
    public function getApartments(): array
    {
        $response = $this->get('/panelCode');

        return array_map(static fn(array $value): Apartment => new Apartment(
            $value['panelCode'],
            $value['callsEnabled']['handset'],
            $value['callsEnabled']['sip'],
            $value['resistances']['answer'] ?? 255,
            $value['resistances']['quiescent'] ?? 255,
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
            $response['resistances']['answer'] ?? 255,
            $response['resistances']['quiescent'] ?? 255,
            $response['callsEnabled']['sip'] ? [sprintf('1%09d', $response['panelCode'])] : []
        );
    }

    public function addApartment(Apartment $apartment): void
    {
        $body = [
            'panelCode' => $apartment->apartment,
            'callsEnabled' => ['handset' => $apartment->handset, 'sip' => $apartment->sip],
        ];

        if ($apartment->handset) {
            $body['soundOpenTh'] = null;
            $body['typeSound'] = 3;
            $body['resistances'] = ['answer' => $apartment->answer, 'quiescent' => $apartment->quiescent];
        }

        $this->post('/panelCode', $body);
    }

    public function setApartment(Apartment $apartment): void
    {
        $body = [
            'panelCode' => $apartment->apartment,
            'callsEnabled' => ['handset' => $apartment->handset, 'sip' => $apartment->sip],
        ];

        if ($apartment->handset) {
            $body['soundOpenTh'] = null;
            $body['typeSound'] = 3;
            $body['resistances'] = ['answer' => $apartment->answer, 'quiescent' => $apartment->quiescent];
        }

        $this->put('/panelCode/' . $apartment->apartment, $body);
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