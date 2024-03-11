<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr\Common;

enum DvrCommand: string
{
    case PLAY = "play";
    case PAUSE = "pause";

    case SEEK = "seek";

    case SPEED = "speed";
}