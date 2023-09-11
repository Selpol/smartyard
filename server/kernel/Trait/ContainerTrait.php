<?php

namespace Selpol\Kernel\Trait;

use RuntimeException;
use Selpol\Container\Container;
use Selpol\Container\ContainerConfigurator;

trait ContainerTrait
{
    private Container $container;

    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @template T
     * @psalm-param class-string<T> $id
     * @return T
     */
    public function getContainerValue(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    private function loadContainer(bool $cache = true): void
    {
        if ($cache && file_exists(path('var/cache/container.php')))
            $this->setContainer(new Container(factories: require path('var/cache/container.php')));
        else if (file_exists(path('config/container.php'))) {
            $callback = require path('config/container.php');
            $configurator = new ContainerConfigurator();

            $callback($configurator);

            $this->setContainer(new Container(factories: $configurator->getFactories()));
        } else throw new RuntimeException('Container is not loaded');
    }
}