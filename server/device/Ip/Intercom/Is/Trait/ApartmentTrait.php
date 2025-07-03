<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Apartment\Apartment;
use Selpol\Feature\Config\ConfigKey;
use Throwable;

trait ApartmentTrait
{
    public function getDefaultAnswerLevel(): int
    {
        return 255;
    }

    public function getDefaultQuiescentLevel(): int
    {
        return 255;
    }

    public function getApartments(): array
    {
        try {
            $response = $this->get('/panelCode');

            $answer = $this->resolver->int(ConfigKey::ApartmentAnswer, $this->getDefaultAnswerLevel());
            $quiescent = $this->resolver->int(ConfigKey::ApartmnetQuiescent, $this->getDefaultQuiescentLevel());

            return array_map(static fn(array $value): Apartment => new Apartment(
                $value['panelCode'],
                $value['callsEnabled']['handset'],
                $value['callsEnabled']['sip'],
                $value['resistances']['answer'] ?? $answer,
                $value['resistances']['quiescent'] ?? $quiescent,
                $value['callsEnabled']['sip'] ? [sprintf('1%09d', $value['panelCode'])] : []
            ), $response);
        } catch (Throwable) {
            return [];
        }
    }

    public function getApartment(int $apartment): ?Apartment
    {
        try {
            $response = $this->get('/panelCode/' . $apartment);

            if (!array_key_exists('panelCode', $response)) {
                return null;
            }

            $answer = $this->resolver->int(ConfigKey::ApartmentAnswer, $this->getDefaultAnswerLevel());
            $quiescent = $this->resolver->int(ConfigKey::ApartmnetQuiescent, $this->getDefaultQuiescentLevel());

            return new Apartment(
                $response['panelCode'],
                $response['callsEnabled']['handset'],
                $response['callsEnabled']['sip'],
                $response['resistances']['answer'] ?? $answer,
                $response['resistances']['quiescent'] ?? $quiescent,
                $response['callsEnabled']['sip'] ? [sprintf('1%09d', $response['panelCode'])] : []
            );
        } catch (Throwable) {
            return null;
        }
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

        if ($audio = $this->resolver->string(ConfigKey::AudioVolume->with($apartment->apartment))) {
            $audios = array_map('intval', explode(',', $audio));

            $body['volumes'] = [
                'thCall' => $audios !== [] ? $audios[0] : null,
                'thTalk' => count($audios) > 1 ? $audios[1] : null,
                'uartFrom' => count($audios) > 2 ? $audios[2] : null,
                'uartTo' => count($audios) > 3 ? $audios[3] : null,
                'panelCall' => count($audios) > 4 ? $audios[4] : null,
                'panelTalk' => count($audios) > 5 ? $audios[5] : null
            ];
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

        if ($audio = $this->resolver->string(ConfigKey::AudioVolume->with($apartment->apartment))) {
            $audios = array_map('intval', explode(',', $audio));

            $body['volumes'] = [
                'thCall' => $audios !== [] ? $audios[0] : null,
                'thTalk' => count($audios) > 1 ? $audios[1] : null,
                'uartFrom' => count($audios) > 2 ? $audios[2] : null,
                'uartTo' => count($audios) > 3 ? $audios[3] : null,
                'panelCall' => count($audios) > 4 ? $audios[4] : null,
                'panelTalk' => count($audios) > 5 ? $audios[5] : null
            ];
        }

        $this->put('/panelCode/' . $apartment->apartment, $body);
    }

    public function setApartmentAudio(int $apartment, array $audios): void
    {
        $body = [
            'volumes' => [
                'thCall' => $audios !== [] ? $audios[0] : null,
                'thTalk' => count($audios) > 1 ? $audios[1] : null,
                'uartFrom' => count($audios) > 2 ? $audios[2] : null,
                'uartTo' => count($audios) > 3 ? $audios[3] : null,
                'panelCall' => count($audios) > 4 ? $audios[4] : null,
                'panelTalk' => count($audios) > 5 ? $audios[5] : null
            ]
        ];

        $this->put('/panelCode/' . $apartment, $body);
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