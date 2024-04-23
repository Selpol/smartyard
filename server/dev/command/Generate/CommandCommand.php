<?php declare(strict_types=1);

namespace Selpol\Command\Generate;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Poet\Poet;
use Selpol\Poet\Type\Type;

#[Executable('generate:command', 'Создать новую команду')]
class CommandCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(CliIO $io): void
    {
        $io->writeLine('Укажите заголовок новой команды');

        $readCommand = trim($io->readLine('> '));

        $segments = explode(':', $readCommand);

        if (count($segments) > 1) {
            $array = array_map('ucfirst', array_slice($segments, 0, -1));

            $namespace = 'Selpol\\Command\\' . implode('\\', $array) . '\\';
            $path = path('command/' . implode('/', $array));
        } else {
            $namespace = 'Selpol\\Command\\';
            $path = path('command');
        }

        $poet = new Poet($readCommand . ' Command', $namespace, $path);

        $commandFile = $poet->file(ucfirst($segments[count($segments) - 1]))->strict();

        $commandFile
            ->use(Type::class(Executable::class))
            ->use(Type::class(Execute::class))
            ->use(Type::class(LoggerCommandTrait::class))
            ->use(Type::class(CliIO::class));

        $command = $commandFile->class();

        $command->attribute(Type::class(Executable::class))->initial('%S, %S', [$readCommand, '']);

        $command->use(Type::class(LoggerCommandTrait::class));

        $execute = $command->method('execute');

        $execute->attribute(Type::class(Execute::class));
        $execute->parameter('io')->type(Type::class(CliIO::class));

        $poet->write();
    }
}