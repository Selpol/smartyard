<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Video;

interface VideoInterface
{
    public function getVideoEncoding(): VideoEncoding;

    public function getVideoDetection(): VideoDetection;

    public function getVideoDisplay(): VideoDisplay;

    public function getVideoOverlay(): VideoOverlay;

    public function setVideoEncoding(VideoEncoding $videoEncoding): void;

    public function setVideoDetection(VideoDetection $videoDetection): void;

    public function setVideoDisplay(VideoDisplay $videoDisplay): void;

    public function setVideoOverlay(VideoOverlay $videoOverlay): void;
}