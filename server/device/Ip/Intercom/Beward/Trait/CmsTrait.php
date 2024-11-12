<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use CURLFile;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsApartment;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsLevels;

trait CmsTrait
{
    private ?array $models = null;

    private ?array $cmses = null;

    public function getLineDialStatus(int $apartment, bool $info): array|int
    {
        $value = (int)$this->get('/cgi-bin/intercom_cgi', ['action' => 'linelevel', 'Apartment' => $apartment]);

        return $info ? ['resist' => $value, 'status' => 'Не определено'] : $value;
    }

    public function getAllLineDialStatus(int $from, int $to, bool $info): array
    {
        $result = [];

        for ($i = $from; $i <= $to; ++$i) {
            $result[$i] = $info ? $this->getLineDialStatus($i, true) : ['resist' => $this->getLineDialStatus($i, false)];
        }

        return $result;
    }

    public function getCmsModels(): array
    {
        if ($this->models != null) {
            return $this->models;
        }

        $resources = $this->get('/xml/kmnducfg.xml', parse: ['type' => 'xml']);

        if ($resources == null) {
            return [];
        }

        if (array_key_exists('Resources', $resources)) {
            $resources = $resources['Resources'];
        }

        if (!is_array($resources)) {
            return [];
        }

        foreach ($resources as $resource) {
            if ($resource['@attributes']['lan'] == 'en') {
                $resources = $resource['Page'];

                break;
            }
        }

        $keys = array_keys($resources);
        $result = [];

        foreach ($keys as $key) {
            if (str_starts_with($key, 'opkmntype')) {
                $result[$resources[$key]] = intval(substr($key, 9));
            }
        }

        $this->models = $result;

        return $result;
    }

    public function getCmsModel(): string
    {
        $content = $this->get('/cgi-bin/intercomdu_cgi', ['action' => 'export'], parse: false);
        $lines = explode(PHP_EOL, (string)$content);

        $model = $lines[0];

        $models = $this->getCmsModels();

        foreach ($models as $cms => $value) {
            if ($value === $model) {
                return $cms;
            }
        }

        return '';
    }

    public function getCmsLevels(): CmsLevels
    {
        $response = $this->getIntercomCgi();

        return new CmsLevels([$response['HandsetUpLevel'], $response['DoorOpenLevel']]);
    }

    public function setCmsModel(string $cms): void
    {
        $models = $this->getCmsModels();

        if (array_key_exists(strtoupper($cms), $models)) {
            $this->get('/webs/kmnDUCfgEx', ['kmntype' => $models[strtoupper($cms)]]);
        }
    }

    public function setCmsLevels(CmsLevels $cmsLevels): void
    {
        $handset = array_key_exists(0, $cmsLevels->value) ? $cmsLevels->value[0] : 330;
        $open = array_key_exists(1, $cmsLevels->value) ? $cmsLevels->value[1] : 530;

        $this->get('/cgi-bin/intercom_cgi', ['action' => 'set', 'HandsetUpLevel' => $handset, 'DoorOpenLevel' => $open]);
        $this->get('/cgi-bin/apartment_cgi', ['action' => 'levels', 'HandsetUpLevel' => $handset, 'DoorOpenLevel' => $open]);
    }

    public function setCmsApartmentDeffer(CmsApartment $cmsApartment): void
    {
        if ($this->cmses === null) {
            $this->cmses = [];
        }

        $this->cmses[] = ['index' => $cmsApartment->index, 'dozen' => $cmsApartment->dozen, 'unit' => $cmsApartment->unit, 'apartment' => $cmsApartment->apartment];
    }

    public function defferCms(): void
    {
        if ($this->cmses) {
            ['model' => $model, 'cmses' => $cmses] = $this->cmsExport();

            $modify = false;

            foreach ($this->cmses as $cms) {
                if ($cmses[$cms['index'] - 1][$cms['unit']][$cms['dozen']] != $cms['apartment']) {
                    $modify = true;
                }

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
        $counter = count($cmses);

        for ($index = 0; $index < $counter; ++$index) {
            for ($unit = 0; $unit < count($cmses[$index]); ++$unit) {
                for ($dozen = 0; $dozen < count($cmses[$index][$unit]); ++$dozen) {
                    $cmses[$index][$unit][$dozen] = 0;
                }
            }
        }

        $this->cmsImport($model, $cmses);
    }

    private function cmsExport(): array
    {
        $content = $this->get('/cgi-bin/intercomdu_cgi', ['action' => 'export'], parse: false);
        $lines = explode(PHP_EOL, (string)$content);

        $model = 0;
        $cmses = [];
        $counter = count($lines);

        for ($i = 0; $i < $counter; ++$i) {
            if ($i === 0) {
                $model = intval($lines[$i]);
            } elseif ($lines[$i] === '') {
                $cmses[] = [];
            } else {
                $count = count(explode(',', $lines[$i]));

                if ($count !== 0) {
                    $cmses[count($cmses) - 1][] = array_fill(0, $count, 0);
                }
            }
        }

        return ['model' => $model, 'cmses' => array_filter($cmses, static fn(array $cms): bool => $cms !== [])];
    }

    private function cmsImport(int $model, array $cmses): void
    {
        $content = $model . PHP_EOL . PHP_EOL;

        foreach ($cmses as $cms) {
            foreach ($cms as $cm) {
                $content .= implode(',', $cm) . PHP_EOL;
            }

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