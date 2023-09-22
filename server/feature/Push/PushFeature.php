<?php

namespace Selpol\Feature\Push;

use Selpol\Feature\Feature;

abstract class PushFeature extends Feature
{
    public abstract function push(array $push): bool|string;

    public abstract function message(array $push): bool|string;

    public abstract function logout(array $push): bool|string;
}