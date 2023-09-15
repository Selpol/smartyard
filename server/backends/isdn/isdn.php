<?php

namespace backends\isdn;

use backends\backend;

abstract class isdn extends backend
{
    abstract function push(array $push): bool|string;

    abstract function message(array $push): bool|string;
}
