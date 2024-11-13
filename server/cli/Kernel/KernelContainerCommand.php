<?php declare(strict_types=1);

namespace Selpol\Cli\Kernel;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Container\Trait\ContainerTrait;

#[Executable('kernel:container', 'Конфигурация контейнера')]
class KernelContainerCommand
{
    #[Execute]
    public function execute(CliIO $io): void
    {
        if (file_exists(path('config/container.php'))) {
            $container = new class {
                use ContainerTrait;

                public function __construct()
                {
                    $this->loadContainer(false);
                }
            };

            $factories = $container->getContainer()->getFactories();

            $headers = ['TYPE', 'ID', 'FACTORY'];
            $result = [];

            foreach ($factories as $id => $factory) {
                $result[] = ['TYPE' => $factory[0] ? 'SINGLETON' : 'FACTORY', 'ID' => $id, 'FACTORY' => $factory[1] ?: ''];
            }

            $io->writeLine('CONTAINER TABLE:');
            $io->getOutput()->table($headers, $result);
        } else {
            $io->writeLine('Container not configured');
        }
    }
}