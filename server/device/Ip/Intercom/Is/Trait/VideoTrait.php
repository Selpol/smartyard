<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Video\VideoDetection;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoDisplay;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoEncoding;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoOverlay;

trait VideoTrait
{
    public function getVideoEncoding(): VideoEncoding
    {
        $response = $this->get('/camera/codec');

        return new VideoEncoding($response['Channels'][0]['Width'] . 'x' . $response['Channels'][0]['Height'], $response['Channels'][0]['MaxBitrate'] ?? 0, $response['Channels'][1]['MaxBitrate'] ?? 0);
    }

    public function getVideoDetection(): VideoDetection
    {
        $response = $this->get('/camera/md');

        return new VideoDetection($response['md_enable'], null, null, null, null);
    }

    public function getVideoDisplay(): VideoDisplay
    {
        return new VideoDisplay('');
    }

    public function getVideoOverlay(): VideoOverlay
    {
        $response = $this->get('/v2/camera/osd');

        return new VideoOverlay($response[1]['text']);
    }

    public function setVideoEncoding(VideoEncoding $videoEncoding): void
    {
        list($width, $height) = explode('x', $videoEncoding->quality ?? '1280x720');

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
                    "MaxBitrate" => $videoEncoding->secondaryBitrate
                ]
            ]
        ]);

        $this->put('/camera/picture', ['u8Contr' => 50, 'u8Luma' => 50, 'u8Satu' => 50]);
    }

    public function setVideoDetection(VideoDetection $videoDetection): void
    {
        $this->put('/camera/md', [
            'md_enable' => $videoDetection->enable,
            'md_frame_shift' => 1,
            'md_area_thr' => 100000,
            'md_rect_color' => '0xFF0000',
            'md_frame_int' => 30,
            'md_rects_enable' => false,
            'md_logs_enable' => true,
            'md_send_snapshot_enable' => true,
            'md_send_snapshot_interval' => 1,

            'snap_send_url' => '',
        ]);
    }

    public function setVideoDisplay(VideoDisplay $videoDisplay): void
    {
    }

    public function setVideoOverlay(VideoOverlay $videoOverlay): void
    {
        $this->put('/v2/camera/osd', [
            [
                'size' => 1,
                'text' => '',
                'color' => '0xFFFFFF',
                'date' => ['enable' => true, 'format' => '%d-%m-%Y'],
                'time' => ['enable' => true, 'format' => '%H:%M:%S'],
                'position' => ['x' => 10, 'y' => 10],
                'background' => ['enable' => true, 'color' => '0x000000'],
            ],
            [
                'size' => 1,
                'text' => $videoOverlay->title,
                'color' => '0xFFFFFF',
                'date' => ['enable' => false, 'format' => '%d-%m-%Y'],
                'time' => ['enable' => false, 'format' => '%H:%M:%S'],
                'position' => ['x' => 10, 'y' => 693],
                'background' => ['enable' => true, 'color' => '0x000000'],
            ],
            [
                'size' => 1,
                'text' => '',
                'color' => '0xFFFFFF',
                'date' => ['enable' => false, 'format' => '%d-%m-%Y'],
                'time' => ['enable' => false, 'format' => '%H:%M:%S'],
                'position' => ['x' => 10, 'y' => 693,],
                'background' => ['enable' => false, 'color' => '0x000000'],
            ],
        ]);
    }
}