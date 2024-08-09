<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use CURLFile;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsApartment;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsLevels;

trait CmsTrait
{
    private ?array $cmses = null;

    public function getLineDialStatus(int $apartment, bool $info): array|int
    {
        $value = (int)$this->get('/cgi-bin/intercom_cgi', ['action' => 'linelevel', 'Apartment' => $apartment]);

        return $info ? ['resist' => $value, 'status' => 'Не определено'] : $value;
    }

    public function getAllLineDialStatus(int $from, int $to, bool $info): array
    {
        $result = [];

        for ($i = $from; $i <= $to; $i++)
            $result[$i] = $info ? $this->getLineDialStatus($i, true) : ['resist' => $this->getLineDialStatus($i, false)];

        return $result;
    }

    public function getCmsModel(): string
    {
        $content = $this->get('/cgi-bin/intercomdu_cgi', ['action' => 'export'], parse: false);
        $lines = explode(PHP_EOL, $content);

        $model = $lines[0];

        foreach ($this->model->cmsesMap as $cms => $value)
            if ($value === $model)
                return $cms;

        return '';
    }

    public function getCmsLevels(): CmsLevels
    {
        $response = $this->parseParamValueHelp($this->get('/cgi-bin/intercom_cgi', ['action' => 'get']));

        return new CmsLevels([$response['HandsetUpLevel'], $response['DoorOpenLevel']]);
    }

    public function setCmsModel(string $cms): void
    {
        if (array_key_exists(strtoupper($cms), $this->model->cmsesMap))
            $this->get('/webs/kmnDUCfgEx', ['kmntype' => $this->model->cmsesMap[strtoupper($cms)]]);
    }

    public function setCmsLevels(CmsLevels $cmsLevels): void
    {
        if (count($cmsLevels->value) == 2) {
            $this->get('/cgi-bin/intercom_cgi', ['action' => 'set', 'HandsetUpLevel' => $cmsLevels->value[0], 'DoorOpenLevel' => $cmsLevels->value[1]]);
            $this->get('/cgi-bin/apartment_cgi', ['action' => 'levels', 'HandsetUpLevel' => $cmsLevels->value[0], 'DoorOpenLevel' => $cmsLevels->value[1]]);
        }
    }

    public function setCmsApartmentDeffer(CmsApartment $cmsApartment): void
    {
        if ($this->cmses === null)
            $this->cmses = [];

        $this->cmses[] = ['index' => $cmsApartment->index, 'dozen' => $cmsApartment->dozen, 'unit' => $cmsApartment->unit, 'apartment' => $cmsApartment->apartment];
    }

    public function defferCms(): void
    {
        if ($this->cmses) {
            ['model' => $model, 'cmses' => $cmses] = $this->cmsExport();

            $modify = false;

            foreach ($this->cmses as $cms) {
                if ($cmses[$cms['index'] - 1][$cms['unit']][$cms['dozen']] != $cms['apartment'])
                    $modify = true;

                $cmses[$cms['index'] - 1][$cms['unit']][$cms['dozen']] = $cms['apartment'];
            }

            if (!$modify) {
                $this->cmses = null;

                return;
            }

            $this->cmsImport($model, $cmses);

            $this->cmses = null;
        }
    }

    public function clearCms(string $cms): void
    {
        ['model' => $model, 'cmses' => $cmses] = $this->cmsExport();

        for ($index = 0; $index < count($cmses); $index++) {
            for ($unit = 0; $unit < count($cmses[$index]); $unit++) {
                for ($dozen = 0; $dozen < count($cmses[$index][$unit]); $dozen++) {
                    $cmses[$index][$unit][$dozen] = 0;
                }
            }
        }

        $this->cmsImport($model, $cmses);
    }

    private function cmsExport(): array
    {
        $content = $this->get('/cgi-bin/intercomdu_cgi', ['action' => 'export'], parse: false);
        $lines = explode(PHP_EOL, $content);

        $model = 0;
        $cmses = [];

        for ($i = 0; $i < count($lines); $i++) {
            if ($i === 0) $model = intval($lines[$i]);
            else if ($lines[$i] === '') $cmses[] = [];
            else {
                $count = count(explode(',', $lines[$i]));

                if ($count)
                    $cmses[count($cmses) - 1][] = array_fill(0, $count, 0);
            }
        }

        return ['model' => $model, 'cmses' => array_filter($cmses, static fn(array $cms) => count($cms) > 0)];
    }

    private function cmsImport(int $model, array $cmses): void
    {
        $content = $model . PHP_EOL . PHP_EOL;

        foreach ($cmses as $cms) {
            foreach ($cms as $cm)
                $content .= implode(',', $cm) . PHP_EOL;

            $content .= PHP_EOL;
        }

        $filename = tempnam(sys_get_temp_dir(), 'dks-matrik');

        $stream = fopen($filename, 'w');
        fwrite($stream, $content);
        fclose($stream);

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $this->uri . '/cgi-bin/intercomdu_cgi?action=import',
                CURLOPT_POST => 1,
                CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
                CURLOPT_USERPWD => $this->login . ':' . $this->password,
                CURLOPT_HTTPHEADER => ['Content-Type:multipart/form-data'],
                CURLOPT_POSTFIELDS => ['data-binary' => new CURLFile($filename, posted_filename: 'matrix.csv'), 'text/csv'],
                CURLOPT_INFILESIZE => strlen($content)
            ]);

            curl_exec($ch);
            curl_close($ch);
        } finally {
            unlink($filename);
        }
    }
}