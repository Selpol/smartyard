<?php declare(strict_types=1);

namespace Selpol\Feature\Config\Internal;

use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Framework\Cache\FileCache;
use Throwable;

readonly class InternalConfigFeature extends ConfigFeature
{
    public function clearCacheConfigForIntercom(?int $id = null): void
    {
        try {
            $cache = container(FileCache::class);

            if ($id !== null) {
                $cache->delete('intercom.config.' . $id);

                return;
            }

            $files = scandir(path('var/cache'));

            if ($files) {
                foreach ($files as $file) {
                    if (str_starts_with($file, 'intercom.config.') && str_ends_with($file, '.php')) {
                        $cache->delete(substr($file, 0, -4));
                    }
                }
            }
        } catch (Throwable) {

        }
    }

    public function getConfigForIntercom(string $model, ?string $intercom): Config
    {
        $value = new Config();

        $coreVar = container(CoreVarRepository::class)->findByName('intercom.config');

        if ($coreVar && $coreVar->var_value) {
            $value->load($coreVar->var_value);
        }

        if ($model) {
            $value->load($model);
        }

        if ($intercom) {
            $value->load($intercom);
        }

        return $value;
    }
}