<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is;

use Selpol\Device\Ip\Intercom\IntercomCms;

class Is5Intercom extends IsIntercom
{
    public function setSyslog(string $server, int $port): static
    {
        $this->put('/v1/network/syslog', ['addr' => $server, 'port' => $port]);

        return $this;
    }

    public function setDDns(bool $value, array $options = []): static
    {
        if (!$value)
            $this->put('/v1/ddns', ['enabled' => false]);

        return $this;
    }

    public function setDisplayText(string $title): static
    {
        if ($title === "") $this->put('/panelDisplay/settings', ['strDisplay' => false]);
        else $this->put('/panelDisplay/settings', ['strDisplay' => true, 'speed' => 500, 'imgStr' => $title]);

        return $this;
    }

    public function clearCms(string $model): void
    {
        $length = $this->model->cmsesMap[strtoupper($model)] === 'VISIT' ? 3 : 4;

        for ($i = 1; $i <= $length; $i++) {
            $matrix = $this->get('/switch/matrix/' . $i);

            if ($cms = IntercomCms::model($model))
                $matrix['capacity'] = $cms->capacity;

            for ($j = 0; $j < count($matrix['matrix']); $j++)
                for ($k = 0; $k < count($matrix['matrix'][$j]); $k++)
                    $matrix['matrix'][$j][$k] = 0;

            $this->put('/switch/matrix/' . $i, $matrix);
        }
    }
}