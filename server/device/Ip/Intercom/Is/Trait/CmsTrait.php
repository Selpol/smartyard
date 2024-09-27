<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsApartment;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsLevels;

trait CmsTrait
{
    private bool $updateCmses = false;
    
    private ?array $tempCmses = null;
    
    private ?array $cmses = null;

    public function getLineDialStatus(int $apartment, bool $info): array|int
    {
        $response = $this->get(sprintf('/panelCode/%d/resist', $apartment));

        if (!$response || isset($response['errors'])) {
            return $info ? ['resist' => 0, 'status' => $response['errors'][0]['message']] : 0;
        }

        if ($info) {
            $status = match ($response['status']) {
                'up' => 'Трубка снята',
                'down' => 'Трубка лежит',
                default => 'Не определено'
            };

            return ['resist' => intval($response['resist']), 'status' => $status];
        }

        return intval($response['resist']);
    }

    public function getAllLineDialStatus(int $from, int $to, bool $info): array
    {
        return $this->post('/panelCode/diag', range($from, $to));
    }

    public function getCmsModel(): string
    {
        $response = $this->get('/switch/settings');

        return $response['modelId'];
    }

    public function getCmsLevels(): CmsLevels
    {
        $response = $this->get('/levels');
        $resistances = $response['resistances'];

        return new CmsLevels([$response['error'], $resistances['break'], $resistances['quiescent'], $resistances['answer']]);
    }

    public function setCmsModel(string $cms): void
    {
        if (array_key_exists(strtoupper($cms), $this->model->cmsesMap)) {
            $this->put('/switch/settings', ['modelId' => $this->model->cmsesMap[strtoupper($cms)], 'usingCom3' => true]);
        }
    }

    public function setCmsLevels(CmsLevels $cmsLevels): void
    {
        $this->put('/levels', [
            'resistances' => [
                'error' => array_key_exists(0, $cmsLevels->value) ? $cmsLevels->value[0] : 255,
                'break' => array_key_exists(1, $cmsLevels->value) ? $cmsLevels->value[1] : 255,
                'quiescent' => array_key_exists(2, $cmsLevels->value) ? $cmsLevels->value[2] : 255,
                'answer' => array_key_exists(3, $cmsLevels->value) ? $cmsLevels->value[3] : 255,
            ],
        ]);
    }

    public function setCmsApartmentDeffer(CmsApartment $cmsApartment): void
    {
        if ($this->tempCmses === null) {
            $this->tempCmses = [];
        }

        if ($this->cmses === null) {
            $this->cmses = [];
        }

        if (!array_key_exists($cmsApartment->index, $this->tempCmses)) {
            $this->tempCmses[$cmsApartment->index] = $this->get('/switch/matrix/' . $cmsApartment->index);
        }

        if (!array_key_exists($cmsApartment->index, $this->cmses)) {
            if (!$this->updateCmses && $this->tempCmses[$cmsApartment->index]['matrix'][$cmsApartment->dozen][$cmsApartment->unit] !== $cmsApartment->apartment) {
                $this->updateCmses = true;
            }

            $matrix = $this->tempCmses[$cmsApartment->index];
            $counter = count($matrix['matrix']);

            for ($j = 0; $j < $counter; ++$j) {
                for ($k = 0; $k < count($matrix['matrix'][$j]); ++$k) {
                    $matrix['matrix'][$j][$k] = 0;
                }
            }

            $this->cmses[$cmsApartment->index] = $matrix;
        }

        $this->cmses[$cmsApartment->index]['matrix'][$cmsApartment->dozen][$cmsApartment->unit] = $cmsApartment->apartment;
    }

    public function defferCms(): void
    {
        if ($this->updateCmses && $this->cmses) {
            foreach ($this->cmses as $index => $value) {
                $this->put('/switch/matrix/' . $index, ['capacity' => $value['capacity'], 'matrix' => $value['matrix']]);
            }

            $this->tempCmses = null;
            $this->cmses = null;
        }
    }

    public function clearCms(string $cms): void
    {
        $cms = IntercomCms::model($cms);

        if (!$cms instanceof IntercomCms) {
            return;
        }

        $length = count($cms->cms);

        for ($i = 1; $i <= $length; ++$i) {
            $matrix = $this->get('/switch/matrix/' . $i);

            $matrix['capacity'] = $cms->capacity;
            $counter = count($matrix['matrix']);

            for ($j = 0; $j < $counter; ++$j) {
                for ($k = 0; $k < count($matrix['matrix'][$j]); ++$k) {
                    $matrix['matrix'][$j][$k] = 0;
                }
            }

            if (count($matrix['matrix']) > 0) {
                $this->put('/switch/matrix/' . $i, $matrix);
            }
        }
    }
}