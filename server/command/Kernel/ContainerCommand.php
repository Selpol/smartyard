<?php declare(strict_types=1);

namespace Selpol\Command\Kernel;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Container\Trait\ContainerTrait;
use Selpol\Runner\CliRunner;

#[Executable('kernel:container', 'Посмотреть элементы контейнера')]
class ContainerCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(): void
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

            foreach ($factories as $id => $factory)
                $result[] = ['TYPE' => $factory[0] ? 'SINGLETON' : 'FACTORY', 'ID' => $id, 'FACTORY' => $factory[1] ?: ''];

            $this->getLogger()->debug('Элементы контейнера:');
            $this->getLogger()->debug(CliRunner::table($headers, $result));
        }
    }
}