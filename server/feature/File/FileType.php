<?php declare(strict_types=1);

enum FileType
{
    case Screenshot;
    case Face;
    case Archive;
    case Group;
    case Other;

    case OldScreenshot;
    case OldFace;
}
