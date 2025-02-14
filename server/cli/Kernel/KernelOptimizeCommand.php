<?php declare(strict_types=1);

namespace Selpol\Cli\Kernel;

use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Framework\Cache\FileCache;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Cli\Trait\CliTrait;
use Selpol\Framework\Container\Trait\ContainerTrait;
use Selpol\Framework\Kernel\Trait\ConfigTrait;
use Selpol\Framework\Kernel\Trait\EnvTrait;
use Selpol\Framework\Router\Trait\RouterTrait;

#[Executable('kernel:optimize', 'Оптимизация ядра')]
class KernelOptimizeCommand
{
    /**
     * @throws InvalidArgumentException
     */
    #[Execute]
    public function execute(CliIO $io, FileCache $cache): void
    {
        $kernelEnv = kernel()->getEnv();
        $kernelConfig = kernel()->getConfig();

        if (file_exists(path('.env'))) {
            $env = new class {
                use EnvTrait;

                public function __construct()
                {
                    $this->loadEnv(false);
                }
            };

            kernel()->setEnv($env->getEnv());
            $cache->set('env', $env->getEnv());
        }

        if (file_exists(path('config/config.php'))) {
            $config = new class {
                use ConfigTrait;

                public function __construct()
                {
                    $this->loadConfig(false);
                }
            };

            kernel()->setConfig($config->getConfig());
            $cache->set('config', $config->getConfig());
        }

        if (file_exists(path('config/container.php'))) {
            $container = new class {
                use ContainerTrait;

                public function __construct()
                {
                    $this->loadContainer(false);
                }
            };

            $cache->set('container', ['factories' => $container->getContainer()->getFactories(), 'tags' => $container->getContainer()->getTags()]);
        }

        if (file_exists(path('config/router.php'))) {
            $router = new class {
                use RouterTrait;

                public function __construct()
                {
                    $this->loadRouter(false);
                }
            };

            $cache->set('router', $router->getRouter()->getRoutes());
        }

        if (file_exists(path('config/internal.php'))) {
            $router = new class {
                use RouterTrait;

                public function __construct()
                {
                    $this->loadRouter(false, 'internal');
                }
            };

            $cache->set('internal', $router->getRouter()->getRoutes());
        }

        if (file_exists(path('config/admin.php'))) {
            $router = new class {
                use RouterTrait;

                public function __construct()
                {
                    $this->loadRouter(false, 'admin');
                }
            };

            $cache->set('admin', $router->getRouter()->getRoutes());
        }

        if (file_exists(path('config/cli.php'))) {
            $cli = new class {
                use CliTrait;

                public function __construct()
                {
                    $this->loadCli(false);
                }
            };

            $cache->set('cli', $cli->getCli()->getCommands());
        }

        kernel()->setEnv($kernelEnv);
        kernel()->setConfig($kernelConfig);

        $io->writeLine('Kernel optimized');
    }
}