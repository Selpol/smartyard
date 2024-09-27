<?php declare(strict_types=1);

namespace Selpol\Feature\Config\Internal;

use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigFeature;

readonly class InternalConfigFeature extends ConfigFeature
{
    public function getDefaultConfigForIntercom(): Config
    {
        $value = new Config();

        $value->load('');

        return $value;
    }
}