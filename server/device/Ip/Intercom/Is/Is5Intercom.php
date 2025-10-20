<?php

declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is;

use Selpol\Device\Ip\Intercom\Setting\Common\DDns;
use Selpol\Device\Ip\Intercom\Setting\Common\Syslog;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoDetection;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoEncoding;
use Selpol\Feature\Config\ConfigKey;

class Is5Intercom extends IsIntercom
{
    public function setVideoEncoding(VideoEncoding $videoEncoding): void
    {
        list($width, $height) = explode('x', $videoEncoding->quality ?? '1920x1080');

        $rate = $this->resolver->string(ConfigKey::VideoRate, 'VBR');

        $this->put('/camera/codec', [
            'Channels' => [
                [
                    "Channel" => 0,
                    "Type" => "H264",
                    "Profile" => 0,
                    "ByFrame" => true,
                    "Width" => intval($width),
                    "Height" => intval($height),
                    "GopMode" => "NormalP",
                    "IPQpDelta" => 2,
                    "RcMode" => $rate,
                    "IFrameInterval" => 30,
                    "Framerate" => 30,
                    "MaxBitrate" => $videoEncoding->primaryBitrate
                ],
                [
                    "Channel" => 1,
                    "Type" => "H264",
                    "Profile" => 0,
                    "ByFrame" => true,
                    "Width" => 640,
                    "Height" => 480,
                    "GopMode" => "NormalP",
                    "IPQpDelta" => 2,
                    "RcMode" => $rate,
                    "IFrameInterval" => 30,
                    "Framerate" => 30,
                    "MaxBitrate" => $videoEncoding->secondaryBitrate
                ]
            ]
        ]);
    }

    public function setVideoDetection(VideoDetection $videoDetection): void
    {
        $this->put('/camera/md', [
            'md_enable' => $videoDetection->enable,
            'md_area_thr' => 30000,
            'md_send_snapshot_enable' => false,
            'md_send_snapshot_interval' => 1,
            'md_rects_enable' => false,
            'md_logs_enable' => true,
            'md_rect_color' => '0xFF0000',
            'md_frame_int' => 30,
            'md_frame_shift' => 1,
            'md_max_rect_ratio' => 8.0,

            'snap_send_url' => '',
        ]);
    }

    public function setSyslog(Syslog $syslog): void
    {
        $this->put('/v1/network/syslog', ['addr' => $syslog->server, 'port' => $syslog->port]);
    }

    public function setDDns(DDns $dDns): void
    {
        $this->put('/v1/ddns', ['enabled' => $dDns->enable]);
    }
}
