<?php declare(strict_types=1);

namespace Selpol\Feature\External;

use Selpol\Feature\Feature;
use Selpol\Feature\External\Internal\InternalExternalFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalExternalFeature::class)]
readonly abstract class ExternalFeature extends Feature
{
    public abstract function push(array $push): bool|string;

    public abstract function message(array $push): bool|string;

    public abstract function logout(array $push): bool|string;

    public abstract function qr(string $fias, int $flat, int $mobile, string $fio, string $note): bool|string;
}