<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;

trait ApartmentTrait
{
    /** @var array<int, Apartment> */
    private array $apartments;

    public function getDefaultAnswerLevel(): int
    {
        return 330;
    }

    public function getDefaultQuiescentLevel(): int
    {
        return 530;
    }

    /**
     * @return Apartment[]
     */
    public function getApartments(): array
    {
        if (isset($this->apartments)) {
            return $this->apartments;
        }

        $response = $this->parseParamValueHelp($this->get('/cgi-bin/apartment_cgi', ['action' => 'list'], parse: false));

        if (count($response) == 0) {
            return [];
        }

        $end = intval(substr((string)array_key_last($response), 5, -2));

        $result = [];

        for ($i = 1; $i <= $end; ++$i) {
            if (!array_key_exists('Number' . $i, $response)) {
                continue;
            }

            $number = $response['Number' . $i];

            $result[intval($number)] = new Apartment(
                intval($number),
                $response['BlockCMS' . $i] === 'off',
                $response['PhonesActive' . $i] === 'on',
                intval($response['HandsetUpLevel' . $i]),
                intval($response['DoorOpenLevel' . $i]),
                array_values($response['PhonesActive' . $i] === 'on' ? array_filter(array_map(static fn(string $value) => $response[$value], ['Phone' . $i . '_2', 'Phone' . $i . '_3', 'Phone' . $i . '_4', 'Phone' . $i . '_5']), static fn(string $value): bool => $value !== '') : [])
            );
        }

        $this->apartments = $result;

        return $result;
    }

    public function getApartment(int $apartment): ?Apartment
    {
        $response = $this->parseParamValueHelp($this->get('/cgi-bin/apartment_cgi', ['action' => 'get', 'Number' => $apartment], parse: false));

        return new Apartment(
            intval($response['Number']),
            $response['BlockCMS'] === 'off',
            $response['PhonesActive'] === 'on',
            intval($response['HandsetUpLevel']),
            intval($response['DoorOpenLevel']),
            $response['PhonesActive'] ? array_filter(array_map(static fn(string $value) => $response[$value], ['Phone1', 'Phone2', 'Phone3', 'Phone4', 'Phone5']), static fn(string $value): bool => $value !== '') : []
        );
    }

    public function addApartment(Apartment $apartment): void
    {
        $this->setApartment($apartment);
    }

    public function setApartment(Apartment $apartment): void
    {
        $currentApartment = isset($this->apartments) && array_key_exists($apartment->apartment, $this->apartments) ? $this->apartments[$apartment->apartment] : null;

        $params = [
            'action' => 'set',
            'Number' => $apartment->apartment,
            'RegCodeActive' => 'off',
        ];

        if ($currentApartment === null || $currentApartment->handset !== $apartment->handset) {
            $params['BlockCMS'] = $apartment->handset ? 'off' : 'on';
        }

        if ($currentApartment === null || $currentApartment->sip !== $apartment->sip) {
            $params['PhonesActive'] = $apartment->sip ? 'on' : 'off';
        }

        if ($currentApartment === null || $currentApartment->answer !== $apartment->answer) {
            $params['HandsetUpLevel'] = $apartment->answer;
        }

        if ($currentApartment === null || $currentApartment->quiescent !== $apartment->quiescent) {
            $params['DoorOpenLevel'] = $apartment->quiescent;
        }

        if (($currentApartment === null || $currentApartment->numbers !== $apartment->numbers) && $apartment->numbers !== []) {
            $sipNumbers = array_merge([(string)$apartment->apartment], $apartment->numbers);
            $counter = count($sipNumbers);

            for ($i = 1; $i <= $counter; ++$i) {
                $params['Phone' . $i] = $sipNumbers[$i - 1];
            }
        }

        $this->get('/cgi-bin/apartment_cgi', $params);

        if (!isset($this->apartments)) {
            $this->apartments = [];
        }

        $this->apartments[$apartment->apartment] = $apartment;
    }

    public function setApartmentHandset(int $apartment, bool $value): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'set', 'BlockCMS' => $value ? 'off' : 'on']);
    }

    public function removeApartment(Apartment|int $apartment): void
    {
        if ($apartment instanceof Apartment) {
            $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => $apartment->apartment]);
        } else {
            $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => $apartment]);
        }

        if (isset($this->apartments) && array_key_exists($apartment instanceof Apartment ? $apartment->apartment : $apartment, $this->apartments)) {
            unset($this->apartments[$apartment instanceof Apartment ? $apartment->apartment : $apartment]);
        }
    }

    public function clearApartments(): void
    {
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'clear', 'FirstNumber' => 1, 'LastNumber' => 9999]);

        unset($this->apartments);
    }
}