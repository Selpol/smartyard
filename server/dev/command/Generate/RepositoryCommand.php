<?php declare(strict_types=1);

namespace Selpol\Command\Generate;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;
use Selpol\Poet\Poet;
use Selpol\Poet\Type\Type;
use SplFileInfo;

#[Executable('generate:repository', 'Создать новый репозиторий для сущности')]
class RepositoryCommand
{
    use LoggerCommandTrait;

    private const MODELS = [
        'entity/Model/' => 'Selpol\\Entity\\Model\\'
    ];

    #[Execute]
    public function execute(CliIO $io): void
    {
        $io->writeLine('Укажите название модели, либо введите полностью вместе с namespace');

        $entity = trim($io->readLine('> '));

        if (!class_exists($entity)) {
            /**
             * @var string[] $values
             */
            $values = [];

            foreach (self::MODELS as $path => $namespace) {
                $path = path($path);

                $directory = new RecursiveDirectoryIterator($path);
                $iterator = new RecursiveIteratorIterator($directory);

                foreach ($iterator as $value) {
                    /** @var SplFileInfo $value */

                    if ($value->isDir() || $value->getExtension() != 'php' || !str_contains($value->getFilename(), $entity))
                        continue;

                    $values[] = $namespace . str_replace('/', '\\', substr($value->getPathname(), strlen($path), -4));
                }
            }

            if (count($values) === 0) {
                $this->getLogger()->debug('Модель не найдена');

                return;
            } else if (count($values) === 1)
                $entity = $values[0];
            else {
                $index = $io->toggle($values);

                if ($index < 0) {
                    $this->getLogger()->debug('Модель не найдена');

                    return;
                }

                $entity = $values[$index];
            }
        }

        if (!class_exists($entity)) {
            $this->getLogger()->debug('Модель не найдена');

            return;
        }

        $reflection = new ReflectionClass($entity);

        $namespace = str_replace('Model', 'Repository', $reflection->getNamespaceName());
        $path = str_replace('Model', 'Repository', substr($reflection->getFileName(), 0, -4 - strlen($reflection->getShortName())));

        if (class_exists($namespace . '\\' . $reflection->getShortName() . 'Repository')) {
            $this->getLogger()->debug('Репозиторий уже существует');

            return;
        }

        $poet = new Poet($entity . ' Repository', $namespace . '\\', $path);

        $type = Type::class($entity);

        $repositoryFile = $poet->file($reflection->getShortName() . 'Repository')->strict();

        $repositoryFile
            ->use($type)
            ->use(Type::class(Singleton::class))
            ->use(Type::class(EntityCriteria::class))
            ->use(Type::class(EntityPage::class))
            ->use(Type::class(EntityRepository::class))
            ->use(Type::class(EntitySetting::class))
            ->use(Type::class(EntityCriteria::class));

        $io->write('Введите имя аудита (пусто пропустить) >');
        $auditName = trim($io->readLine());

        if ($auditName !== '')
            $repositoryFile->use(Type::class(AuditTrait::class));

        $repository = $repositoryFile->class()->readonly()->extend(Type::class(EntityRepository::class));

        $repository->doc()
            ->push('@method %T|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)', [$type])
            ->push('@method %T[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)', [$type])
            ->push('@method EntityPage<%T> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)', [$type])
            ->push('')
            ->push('@method %T|null findById(mixed $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)', [$type])
            ->push('')
            ->push('@extends EntityRepository<mixed, %T>', [$type]);

        $repository->attribute(Type::class(Singleton::class));

        if ($auditName !== '') {
            $repository->use(Type::class(AuditTrait::class));

            $repository->usesDoc()
                ->push('@use AuditTrait<%T>', [$type]);
        }

        $construct = $repository->construct()->code()
            ->line('parent::__construct(%T::class);', [$type]);

        if ($auditName !== '')
            $construct->line('')->line('$this->auditName = %S;', [$auditName]);

        $poet->write();
    }
}