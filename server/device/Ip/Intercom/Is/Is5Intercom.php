<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is;

use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\Setting\Common\Syslog;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoDisplay;

class Is5Intercom extends IsIntercom
{
    public function getVideoDisplay(): VideoDisplay
    {
        $response = $this->get('/panelDisplay/settings');

        return new VideoDisplay($response['imgStr']);
    }

    public function setVideoDisplay(VideoDisplay $videoDisplay): void
    {
        if ($videoDisplay->title === "") $this->put('/panelDisplay/settings', ['strDisplay' => false]);
        else $this->put('/panelDisplay/settings', ['strDisplay' => true, 'speed' => 500, 'imgStr' => $videoDisplay->title]);
    }

    public function getSyslog(): Syslog
    {
        $response = $this->get('/v1/network/syslog');

        return new Syslog($response['addr'], $response['port']);
    }

    public function setSyslog(Syslog $syslog): void
    {
        $this->put('/v1/network/syslog', ['addr' => $syslog->server, 'port' => $syslog->port]);
    }

    public function clearCms(string $cms): void
    {
        $cms = IntercomCms::model($cms);

        if (!$cms)
            return;

        $length = count($cms->cms);

        for ($i = 1; $i <= $length; $i++) {
            $matrix = $this->get('/switch/matrix/' . $i);

            $matrix['capacity'] = $cms->capacity;

            for ($j = 0; $j < count($matrix['matrix']); $j++)
                for ($k = 0; $k < count($matrix['matrix'][$j]); $k++)
                    $matrix['matrix'][$j][$k] = 0;

            $this->put('/switch/matrix/' . $i, $matrix);
        }
    }
}