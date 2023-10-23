<?php

namespace Selpol\Feature\Push;

use Selpol\Feature\Feature;
use Selpol\Feature\Push\Internal\InternalPushFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalPushFeature::class)]
readonly abstract class PushFeature extends Feature
{
    public abstract function push(array $push): bool|string;

    public abstract function message(array $push): bool|string;

    public abstract function logout(array $push): bool|string;
}