<?php

namespace Selpol\Kernel\Runner;

use Exception;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
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
use Selpol\Task\Tasks\Migration\MigrationDownTask;
use Selpol\Task\Tasks\Migration\MigrationUpTask;
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

        if (count($arguments) === 0) {
            echo $this->help();

            return 0;
        }

        $line = explode(':', array_key_first($arguments), 2);

        if (count($line) !== 2) {
            echo $this->help();

            return 0;
        }

        $group = $line[0];
        $command = $line[1];

        if ($group === 'db') {
            if ($command === 'init') $this->initDb($arguments);
            else if ($command === 'check') return $this->checkDb();
            else echo $this->help('db');
        } else if ($group === 'amqp') {
            if ($command === 'check') return $this->checkAmqp();
            else echo $this->help('amqp');
        } else if ($group === 'rbt') {
            if ($command === 'cleanup') $this->cleanup();
            else if ($command === 'reindex') $this->reindex();
            else echo $this->help('rbt');
        } else if ($group === 'admin') {
            if ($command === 'password') $this->adminPassword($arguments['admin:password']);
            else echo $this->help('admin');
        } else if ($group === 'cron') {
            if ($command === 'run') $this->cron($arguments);
            else if ($command === 'install') $this->installCron();
            else if ($command === 'uninstall') $this->uninstallCron();
            else echo $this->help('cron');
        } else if ($group === 'kernel') {
            if ($command === 'container') $this->containerKernel();
            else if ($command === 'router') $this->routerKernel();
            else if ($command === 'optimize') $this->optimizeKernel($kernel);
            else if ($command === 'clear') $this->clearKernel();
            else echo $this->help('kernel');
        } else echo $this->help();

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

        if ($initDbVersion !== null) {
            if ($initDbVersion > $version) {
                if (task(new MigrationUpTask($version, $initDbVersion))->sync())
                    $this->logger->debug('Upgrade migration from ' . $version . ' to ' . $initDbVersion);
            } else if ($initDbVersion < $version)
                if (task(new MigrationDownTask($version, $initDbVersion))->sync())
                    $this->logger->debug('Downgrade migration from ' . $version . ' to ' . $initDbVersion);
        } else {
            if (task(new MigrationUpTask($version, null))->sync())
                $this->logger->debug('Upgrade migration from ' . $version . ' to latest');
        }
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

        $cli = PHP_BINARY . " " . __FILE__ . " cron:run";

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

            $headers = ['METHOD', 'PATH', 'CLASS', 'MIDDLEWARES'];
            $result = [];

            foreach ($routes as $method => $methodRoutes) {
                foreach ($methodRoutes as $methodPath => $pathRoutes) {
                    $result += $this->routeCompact($method, $methodPath, $pathRoutes);
                }
            }

            $this->logger->debug($this->table($headers, $result));
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

    private function help(?string $group = null): string
    {
        $result = [];

        if ($group === null || $group === 'db')
            $result[] = implode("\n", [
                '',
                'db:init [--version=<version>]    - Инициализация базы данных',
                'db:check                         - Проверка доступности базы данных'
            ]);

        if ($group === null || $group === 'amqp')
            $result[] = implode("\n", [
                '',
                'amqp:check                       - Проверка доступности AMQP'
            ]);

        if ($group === null || $group === 'rbt')
            $result[] = implode("\n", [
                '',
                'rbt:cleanup                      - Очистить кэши РБТ',
                'rbt:reindex                      - Обновить маршрутизацию API'
            ]);

        if ($group === null || $group === 'admin')
            $result[] = implode("\n", [
                '',
                'admin:password=<password>        - Обновить пароль администратора'
            ]);

        if ($group === null || $group === 'cron')
            $result[] = implode("\n", [
                '',
                'cron:run=<type>                  - Выполнить задачи по времени',
                'cron:install                     - Установить задачи по времени',
                'cron:uninstall                   - Удалить задачи по времени'
            ]);

        if ($group === null || $group === 'kernel')
            $result[] = implode("\n", [
                '',
                'kernel:container                 - Показать зависимости приложения',
                'kernel:router                    - Показать маршруты приложения',
                'kernel:optimize                  - Оптимизировать приложение',
                'kernel:clear                     - Очистить приложение'
            ]);

        return trim(implode("\n", $result));
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

    private function routeCompact(string $method, string $path, array $value): array
    {
        $result = [];

        foreach ($value as $valuePath => $route) {
            if (array_key_exists('class', $route))
                $result[] = [
                    'METHOD' => $method,
                    'PATH' => $path . $valuePath,
                    'CLASS' => substr($route['class'], 17) . '@' . $route['method'],
                    'MIDDLEWARES' => count($route['middlewares'])
                ];
            else $result += $this->routeCompact($method, $path . $valuePath, $route);
        }

        return $result;
    }
}