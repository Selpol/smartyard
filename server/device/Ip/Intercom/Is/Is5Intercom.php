<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is;

use Selpol\Device\Ip\Intercom\Setting\Common\DDns;
use Selpol\Device\Ip\Intercom\Setting\Common\Syslog;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoEncoding;

class Is5Intercom extends IsIntercom
{
    public function setVideoEncoding(VideoEncoding $videoEncoding): void
    {
        list($width, $height) = explode('x', $videoEncoding->quality ?? '1920x1080');

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
                    "RcMode" => "AVBR",
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
                    "RcMode" => "AVBR",
                    "IFrameInterval" => 30,
                    "Framerate" => 30,
                    "MaxBitrate" => $videoEncoding->secondaryBitrate
                ]
            ]
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