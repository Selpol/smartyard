<?php declare(strict_types=1);

namespace Selpol\Feature\Config\Internal;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Framework\Cache\FileCache;
use Throwable;

readonly class InternalConfigFeature extends ConfigFeature
{
    public function clearConfigForIntercom(?int $id = null): void
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

    public function getConfigForIntercom(IntercomModel $model, DeviceIntercom $intercom, bool $cache = true): Config
    {
        if ($cache) {
            try {
                $values = container(FileCache::class)->get('intercom.config.' . $intercom->house_domophone_id);

                if ($values !== null) {
                    return new Config($values);
                }

                $value = $this->getConfigForIntercomConfig($model->config, $intercom->config);

                container(FileCache::class)->set('intercom.config.' . $intercom->house_domophone_id, $value->getValues());

                return $value;
            } catch (Throwable) {

            }
        }

        return $this->getConfigForIntercomConfig($model->config, $intercom->config);
    }

    private function getConfigForIntercomConfig(string $model, ?string $intercom): Config
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