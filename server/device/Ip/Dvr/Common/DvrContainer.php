<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

enum DvrContainer: string
{
    case RTSP = 'rtsp';
    case HLS = 'hls';
    case STREAMER_RTC = 'streamer_rtc';
    case STREAMER_RTSP = 'streamer_rtsp';
}