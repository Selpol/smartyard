<?php

namespace Selpol\Kernel\Runner;

use Exception;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;
use Selpol\Cache\FileCache;
use Selpol\Container\ContainerConfigurator;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Kernel\Trait\ConfigTrait;
use Selpol\Kernel\Trait\EnvTrait;
use Selpol\Logger\EchoLogger;
use Selpol\Logger\GroupLogger;
use Selpol\Router\RouterConfigurator;
use Selpol\Service\DatabaseService;
use Selpol\Task\Tasks\ReindexTask;
use Throwable;

class CliRunner implements KernelRunner
{
    private array $argv;

    private LoggerInterface $logger;

    public function __construct(array $argv, ?LoggerInterface $logger = null)
    {
        $this->argv = $argv;

        $this->logger = $logger ?? new GroupLogger([new EchoLogger(), logger('cli')]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    function __invoke(Kernel $kernel): int
    {
        $arguments = $this->getArguments();

        if ($this->isCommand($arguments, '--init-db', max: 2)) $this->initDb($arguments);

        else if ($this->isCommand($arguments, '--check-db')) return $this->checkDb();
        else if ($this->isCommand($arguments, '--check-amqp')) return $this->checkAmqp();

        else if ($this->isCommand($arguments, '--cleanup')) $this->cleanup();
        else if ($this->isCommand($arguments, '--reindex')) $this->reindex();
        else if ($this->isCommand($arguments, '--admin-password', true)) $this->adminPassword($arguments['--admin-password']);

        else if ($this->isCommand($arguments, '--cron', true)) $this->cron($arguments);
        else if ($this->isCommand($arguments, '--install-crontabs')) $this->installCron();
        else if ($this->isCommand($arguments, '--uninstall-crontabs')) $this->uninstallCron();

        else if ($this->isCommand($arguments, '--container-kernel')) $this->containerKernel();
        else if ($this->isCommand($arguments, '--router-kernel')) $this->routerKernel();
        else if ($this->isCommand($arguments, '--optimize-kernel')) $this->optimizeKernel($kernel);
        else if ($this->isCommand($arguments, '--clear-kernel')) $this->clearKernel();

        else echo $this->help();

        return 0;
    }

    public function onFailed(Throwable $throwable, bool $fatal): int
    {
        echo $throwable->getMessage();

        $this->logger->error($throwable, ['fatal' => $fatal]);

        return 0;
    }

    private function getArguments(): array
    {
        $args = [];

        for ($i = 1; $i < count($this->argv); $i++) {
            $a = explode('=', $this->argv[$i]);

            $args[$a[0]] = @$a[1];
        }

        return $args;
    }

    private function isCommand(array $arguments, string $command, bool $isset = false, int $max = 1): bool
    {
        return (count($arguments) <= $max) && array_key_exists($command, $arguments) && ($isset ? isset($arguments[$command]) : !isset($arguments[$command]));
    }

    /**
     * @throws Exception
     */
    private function initDb(array $arguments): void
    {
        $initDbVersion = array_key_exists('--version', $arguments) ? $arguments['--version'] : null;

        $db = container(DatabaseService::class);

        try {
            $query = $db->query("SELECT var_value FROM core_vars where var_name = 'dbVersion'", PDO::FETCH_ASSOC);

            $version = $query ? (int)($query->fetch())['var_value'] : 0;
        } catch (Throwable) {
            $version = 0;
        }

        $files = scandir(path('migration/pgsql/up/'));

        $migrations = array_reduce($files, static function (array $previous, string $file) {
            if (!str_starts_with($file, 'v'))
                return $previous;

            $segments = explode('_', $file);

            if (count($segments) !== 3)
                return $previous;

            $version = (int)substr($segments[0], 1);
            $step = (int)$segments[1];

            if (!array_key_exists($version, $previous))
                $previous[$version] = [];

            $previous[$version][$step] = $file;

            return $previous;
        }, []);

        $this->logger->debug($this->table(['Version', 'Count'], array_map(static fn(int $migrationVersion, array $migrationSteps) => ['Version' => $migrationVersion . ($migrationVersion === $version ? '*' : ''), 'Count' => count($migrationSteps)], array_keys($migrations), array_values($migrations))));

        $db->beginTransaction();

        $versionCount = 0;
        $stepCount = 0;

        foreach ($migrations as $migrationVersion => $migrationValues) {
            if ($migrationVersion <= $version)
                continue;

            $versionCount++;

            try {
                foreach ($migrationValues as $migrationStep) {
                    $stepCount++;

                    $sql = trim(file_get_contents(path('migration/pgsql/' . $migrationStep)));

                    $db->exec($sql);
                }
            } catch (Throwable $throwable) {
                $db->rollBack();

                throw new RuntimeException($throwable->getMessage(), previous: $throwable);
            }

            $db->modify("UPDATE core_vars SET var_value = :version WHERE var_name = 'dbVersion'", ['version' => $migrationVersion]);
        }

        $db->commit();

        $this->logger->debug('Migrated versions ' . $versionCount . ' and steps ' . $stepCount);
    }

    private function checkDb(): int
    {
        try {
            $db = container(DatabaseService::class);
            $result = $db->get('SELECT 1 as result', options: ['singlify']);

            $lastError = last_error();

            if ($lastError !== null) {
                echo $lastError;

                return 1;
            }

            $result = $result['result'] == 1 ? 0 : 1;

            echo $result . PHP_EOL;

            return $result;
        } catch (Throwable $throwable) {
            echo $throwable->getMessage();

            return 1;
        }
    }

    private function checkAmqp(): int
    {
        return 0;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function cleanup(): void
    {
        $backends = config('backends');

        foreach ($backends as $backend => $_) {
            $b = backend($backend);

            if ($b) {
                $n = $b->cleanup();

                echo "$backend: $n items cleaned\n";
            } else echo "$backend: not found\n";
        }
    }

    /**
     * @throws Exception
     */
    private function reindex(): void
    {
        task(new ReindexTask())->sync();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function adminPassword(string $password): void
    {
        $db = container(DatabaseService::class);

        try {
            $db->exec("insert into core_users (uid, login, password) values (0, 'admin', 'admin')");
        } catch (Exception) {
        }

        try {
            $sth = $db->prepare("update core_users set password = :password, login = 'admin', enabled = 1 where uid = 0");
            $sth->execute([":password" => password_hash($password, PASSWORD_DEFAULT)]);

            $this->logger->debug('Update admin password');

            echo "admin account updated\n";
        } catch (Exception) {
            echo "admin account update failed\n";
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function cron(array $arguments): void
    {
        $parts = ["minutely", "5min", "hourly", "daily", "monthly"];
        $part = false;

        foreach ($parts as $p)
            if (in_array($p, $arguments)) {
                $part = $p;

                break;
            }

        if ($part) {
            $start = microtime(true) * 1000;
            $cronBackends = config('backends');

            $this->logger->debug('Processing cron', ['part' => $part, 'backends' => array_keys($cronBackends)]);

            foreach ($cronBackends as $backend_name => $cfg) {
                $backend = backend($backend_name);

                if ($backend) {
                    try {
                        if ($backend->cron($part))
                            $this->logger->debug('Success', ['backend' => $backend_name, 'part' => $part]);
                        else
                            $this->logger->error('Fail', ['backend' => $backend_name, 'part' => $part]);
                    } catch (Exception $e) {
                        $this->logger->error('Error cron' . PHP_EOL . $e, ['backend' => $backend_name, 'part' => $part]);
                    }
                } else $this->logger->error('Backend not found', ['backend' => $backend_name, 'part' => $part]);
            }

            $this->logger->debug('Cron done', ['ellapsed_ms' => microtime(true) * 1000 - $start]);
        } else echo $this->help();
    }

    private function installCron(): void
    {
        $crontab = [];

        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $cli = PHP_BINARY . " " . __FILE__ . " --cron";

        $lines = 0;

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont touch!!!")
                $skip = true;

            if (!$skip)
                $clean[] = $line;

            if ($line === "## RBT crons end, dont touch!!!")
                $skip = false;
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        $clean[] = "";

        $clean[] = "## RBT crons start, dont touch!!!";
        $lines++;
        $clean[] = "*/1 * * * * $cli=minutely";
        $lines++;
        $clean[] = "*/5 * * * * $cli=5min";
        $lines++;
        $clean[] = "1 */1 * * * $cli=hourly";
        $lines++;
        $clean[] = "1 1 */1 * * $cli=daily";
        $lines++;
        $clean[] = "1 1 1 */1 * $cli=monthly";
        $lines++;
        $clean[] = "## RBT crons end, dont touch!!!";
        $lines++;

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        echo "$lines crontabs lines added\n";

        $this->logger->debug('Install crontabs', ['lines' => $lines]);
    }

    private function uninstallCron(): void
    {
        $crontab = [];

        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $lines = 0;

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont touch!!!")
                $skip = true;

            if (!$skip) $clean[] = $line;
            else $lines++;

            if ($line === "## RBT crons end, dont touch!!!")
                $skip = false;
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        echo "$lines crontabs lines removed\n";

        $this->logger->debug('Uninstall crontabs', ['lines' => $lines]);
    }

    private function containerKernel(): void
    {
        if (file_exists(path('config/container.php'))) {
            $callback = require path('config/container.php');
            $builder = new ContainerConfigurator();
            $callback($builder);

            $factories = $builder->getFactories();

            $headers = ['TYPE', 'ID', 'FACTORY'];
            $result = [];

            foreach ($factories as $id => $factory)
                $result[] = ['TYPE' => $factory[0] ? 'SINGLETON' : 'FACTORY', 'ID' => $id, 'FACTORY' => $factory[1] ?: ''];

            $this->logger->debug('CONTAINER TABLE:');
            $this->logger->debug($this->table($headers, $result));
        }
    }

    private function routerKernel(): void
    {
        if (file_exists(path('config/router.php'))) {
            $callback = require path('config/router.php');
            $builder = new RouterConfigurator();
            $callback($builder);

            $routes = $builder->collect();

            var_dump($routes);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function optimizeKernel(Kernel $kernel): void
    {
        $cache = container(FileCache::class);

        $kernelEnv = $kernel->getEnv();
        $kernelConfig = $kernel->getConfig();

        if (file_exists(path('.env'))) {
            $env = new class {
                use EnvTrait;

                public function __construct()
                {
                    $this->loadEnv(false);
                }
            };

            $kernel->setEnv($env->getEnv());
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

            $kernel->setConfig($config->getConfig());
            $cache->set('config', $config->getConfig());
        }

        if (file_exists(path('config/container.php'))) {
            $callback = require path('config/container.php');
            $builder = new ContainerConfigurator();
            $callback($builder);

            $cache->set('container', $builder->getFactories());
        }

        if (file_exists(path('config/router.php'))) {
            $callback = require path('config/router.php');
            $builder = new RouterConfigurator();
            $callback($builder);

            $cache->set('router', $builder->collect());
        }

        $kernel->setEnv($kernelEnv);
        $kernel->setConfig($kernelConfig);

        $this->logger->debug('Kernel optimized');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function clearKernel(): void
    {
        $cache = container(FileCache::class);

        $cache->clear();

        $this->logger->debug('Kernel cleared');
    }

    private function help(): string
    {
        return "initialization:
        db:
            [--init-db [--version=<version>]]
            [--check-db]

        rbt:
            [--cleanup]
            [--reindex]
            [--admin-password=<password>]

        cron:
            [--cron=<minutely|5min|hourly|daily|monthly>]

            [--install-crontabs]
            [--uninstall-crontabs]

        kernel:
            [--container-kernel]
            [--router-kernel]
            [--optimize-kernel]
            [--clear-kernel]
        \n";
    }

    /**
     * @param string[] $headers
     * @param array $values
     * @return string
     */
    private function table(array $headers, array $values): string
    {
        $mask = array_reduce($headers, static function (string $previous, string $header) use ($values) {
                $max = strlen($header);

                foreach ($values as $value) {
                    if (strlen($value[$header]) > $max)
                        $max = strlen($value[$header]);
                }

                return $previous . ' | %' . $max . '.' . $max . 's';
            }, '') . ' | ';

        $result = sprintf($mask, ...$headers);
        $result .= PHP_EOL . str_repeat('-', strlen($result)) . PHP_EOL;

        foreach ($values as $value)
            $result .= sprintf($mask, ...array_map(static fn(string $header) => $value[$header], $headers)) . PHP_EOL;

        return $result;
    }
}