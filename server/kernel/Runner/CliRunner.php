<?php

namespace Selpol\Kernel\Runner;

use Exception;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;
use Selpol\Cache\FileCache;
use Selpol\Container\ContainerConfigurator;
use Selpol\Entity\Model\Permission;
use Selpol\Entity\Repository\AuditRepository;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Kernel\Trait\ConfigTrait;
use Selpol\Kernel\Trait\EnvTrait;
use Selpol\Logger\EchoLogger;
use Selpol\Logger\GroupLogger;
use Selpol\Router\RouterConfigurator;
use Selpol\Service\DatabaseService;
use Selpol\Service\PrometheusService;
use Selpol\Task\Tasks\Migration\MigrationDownTask;
use Selpol\Task\Tasks\Migration\MigrationUpTask;
use Selpol\Validator\Exception\ValidatorException;
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
        var_dump(container(AuditRepository::class)->fetchPaginate(2, 10));

        chdir(path(''));

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
            if ($command === 'init') $this->dbInit($arguments);
            else if ($command === 'check') return $this->dbCheck();
            else echo $this->help('db');
        } else if ($group === 'amqp') {
            if ($command === 'check') return $this->amqpCheck();
            else echo $this->help('amqp');
        } else if ($group === 'admin') {
            if ($command === 'password') $this->adminPassword($arguments['admin:password']);
            else echo $this->help('admin');
        } else if ($group === 'cron') {
            if ($command === 'run') $this->cronRun($arguments);
            else if ($command === 'install') $this->cronInstall();
            else if ($command === 'uninstall') $this->cronUninstall();
            else echo $this->help('cron');
        } else if ($group === 'kernel') {
            if ($command === 'container') $this->kernelContainer();
            else if ($command === 'router') $this->kernelRouter();
            else if ($command === 'optimize') $this->kernelOptimize($kernel);
            else if ($command === 'clear') $this->kernelClear();
            else if ($command === 'wipe') $this->kernelWipe();
            else echo $this->help('kernel');
        } else if ($group === 'audit') {
            if ($command === 'clear') $this->auditClear();
            else echo $this->help('audit');
        } else if ($group === 'role') {
            if ($command === 'init') $this->roleInit();
            else if ($command === 'clear') $this->roleClear();
            else echo $this->help('role');
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

    /**
     * @throws Exception
     */
    private function dbInit(array $arguments): void
    {
        $initDbVersion = array_key_exists('--version', $arguments) ? $arguments['--version'] : null;

        $db = container(DatabaseService::class)->getConnection();

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

    private function dbCheck(): int
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

    private function amqpCheck(): int
    {
        return 0;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function adminPassword(string $password): void
    {
        $connection = container(DatabaseService::class)->getConnection();

        try {
            $connection->exec("insert into core_users (uid, login, password) values (0, 'admin', 'admin')");
        } catch (Exception) {
        }

        try {
            $sth = $connection->prepare("update core_users set password = :password, login = 'admin', enabled = 1 where uid = 0");
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
    private function cronRun(array $arguments): void
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
            $this->logger->debug('Processing cron', ['part' => $part]);

            try {
                if (container(FrsFeature::class)->cron($part))
                    $this->logger->debug('Success', ['feature' => FrsFeature::class, 'part' => $part]);
                else
                    $this->logger->error('Fail', ['feature' => FrsFeature::class, 'part' => $part]);
            } catch (Throwable $throwable) {
                $this->logger->error('Error cron' . PHP_EOL . $throwable, ['feature' => FrsFeature::class, 'part' => $part]);
            }

            $this->logger->debug('Cron done', ['ellapsed_ms' => microtime(true) * 1000 - $start]);
        } else echo $this->help();
    }

    private function cronInstall(): void
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

    private function cronUninstall(): void
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

    private function kernelContainer(): void
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

    private function kernelRouter(): void
    {
        if (file_exists(path('config/router.php'))) {
            $callback = require path('config/router.php');
            $builder = new RouterConfigurator();
            $callback($builder);

            $routes = $builder->collect();

            $headers = ['METHOD', 'PATH', 'CLASS', 'MIDDLEWARES'];
            $result = [];

            foreach ($routes as $method => $methodRoutes)
                foreach ($methodRoutes as $methodPath => $pathRoutes)
                    $this->routeCompact($result, $method, $methodPath, $pathRoutes);

            usort($result, static fn(array $a, array $b) => strcmp($a['PATH'], $b['PATH']));

            $this->logger->debug($this->table($headers, $result));
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function kernelOptimize(Kernel $kernel): void
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
    private function kernelClear(): void
    {
        $cache = container(FileCache::class);

        $cache->clear();

        $this->logger->debug('Kernel cleared');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    private function kernelWipe(): void
    {
        container(PrometheusService::class)->wipe();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    private function auditClear(): void
    {
        container(AuditFeature::class)->clear();;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    private function roleInit(): void
    {
        require_once path('/controller/api/api.php');

        $db = container(DatabaseService::class);

        /** @var array<string, Permission> $titlePermissions */
        $titlePermissions = array_reduce(container(RoleFeature::class)->permissions(), static function (array $previous, Permission $current) {
            $previous[$current->title] = $current;

            return $previous;
        }, []);

        $dir = path('controller/api');
        $apis = scandir($dir);

        foreach ($apis as $api) {
            if ($api != "." && $api != ".." && is_dir($dir . "/$api")) {
                $methods = scandir($dir . "/$api");

                foreach ($methods as $method) {
                    if ($method != "." && $method != ".." && str_ends_with($method, ".php") && is_file($dir . "/$api/$method")) {
                        $method = substr($method, 0, -4);

                        require_once $dir . "/$api/$method.php";

                        if (class_exists("\\api\\$api\\$method")) {
                            $request_methods = call_user_func(["\\api\\$api\\$method", "index"]);

                            if ($request_methods) {
                                $keys = array_keys($request_methods);

                                foreach ($keys as $key) {
                                    $permission = $api . '-' . $method . '-' . strtolower(is_int($key) ? $request_methods[$key] : $key);

                                    if (!array_key_exists($permission, $titlePermissions)) {
                                        $id = $db->get("SELECT NEXTVAL('permission_id_seq')", options: ['singlify'])['nextval'];

                                        $db->insert('INSERT INTO permission(id, title, description) VALUES(:id, :title, :description)', ['id' => $id, 'title' => $permission, 'description' => $permission]);
                                    }

                                    unset($titlePermissions[$permission]);
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($titlePermissions as $permission)
            container(PermissionRepository::class)->delete($permission);
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    private function roleClear(): void
    {
        $permissions = container(RoleFeature::class)->permissions();

        $repository = container(PermissionRepository::class);

        foreach ($permissions as $permission)
            $repository->delete($permission);
    }

    private function help(?string $group = null): string
    {
        $result = [];

        if ($group === null || $group === 'db')
            $result[] = implode(PHP_EOL, [
                '',
                'db:init [--version=<version>]    - Инициализация базы данных',
                'db:check                         - Проверка доступности базы данных'
            ]);

        if ($group === null || $group === 'amqp')
            $result[] = implode(PHP_EOL, [
                '',
                'amqp:check                       - Проверка доступности AMQP'
            ]);

        if ($group === null || $group === 'admin')
            $result[] = implode(PHP_EOL, [
                '',
                'admin:password=<password>        - Обновить пароль администратора'
            ]);

        if ($group === null || $group === 'cron')
            $result[] = implode(PHP_EOL, [
                '',
                'cron:run=<type>                  - Выполнить задачи по времени',
                'cron:install                     - Установить задачи по времени',
                'cron:uninstall                   - Удалить задачи по времени'
            ]);

        if ($group === null || $group === 'kernel')
            $result[] = implode(PHP_EOL, [
                '',
                'kernel:container                 - Показать зависимости приложения',
                'kernel:router                    - Показать маршруты приложения',
                'kernel:optimize                  - Оптимизировать приложение',
                'kernel:clear                     - Очистить приложение',
                'kernel:wipe                      - Очистить метрики приложения'
            ]);

        if ($group === null || $group === 'audit')
            $result[] = implode(PHP_EOL, [
                '',
                'audit:clear                      - Очистить данные аудита'
            ]);

        if ($group === null || $group === 'role')
            $result[] = implode(PHP_EOL, [
                '',
                'role:init                        - Инициализация групп',
                'role:clear                       - Удалить группы'
            ]);

        return trim(implode(PHP_EOL, $result)) . PHP_EOL;
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

    private function routeCompact(array &$result, string $method, string $path, array $value): void
    {
        foreach ($value as $valuePath => $route) {
            if (array_key_exists('class', $route))
                $result[] = [
                    'METHOD' => $method,
                    'PATH' => $path . $valuePath,
                    'CLASS' => substr($route['class'], 17) . '@' . $route['method'],
                    'MIDDLEWARES' => count($route['middlewares'])
                ];
            else $this->routeCompact($result, $method, $path . $valuePath, $route);
        }
    }
}