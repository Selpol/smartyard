<?php declare(strict_types=1);

namespace Selpol\Feature\File;

enum FileStorage
{
    case Screenshot;
    case Archive;
    case Group;
    case Other;
}
