<?php

namespace Selpol\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Permission;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Framework\Cache\FileCache;
use Selpol\Framework\Container\Trait\ContainerTrait;
use Selpol\Framework\Kernel\Trait\ConfigTrait;
use Selpol\Framework\Kernel\Trait\EnvTrait;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Router\Trait\RouterTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Service\DatabaseService;
use Selpol\Service\PrometheusService;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;
use Selpol\Task\Tasks\Migration\MigrationDownTask;
use Selpol\Task\Tasks\Migration\MigrationUpTask;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

class CliRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerKernelTrait;

    public function __construct()
    {
        $this->setLogger(stack_logger([echo_logger(), file_logger('cli')]));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    function run(array $arguments): int
    {
        chdir(path(''));

        $arguments = $this->getArguments($arguments);

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
            else if ($command === 'optimize') $this->kernelOptimize();
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
        } else if ($group === 'device') {
            if ($command === 'sync') $this->deviceSync(intval($arguments['device:sync']));
            else if ($command === 'call') $this->deviceCall(intval($arguments['device:call']));
            else if ($command === 'reboot') $this->deviceReboot(intval($arguments['device:reboot']));
            else if ($command === 'reset') $this->deviceReset(intval($arguments['device:reset']));
            else echo $this->help('device');
        } else echo $this->help();

        return 0;
    }

    public function error(Throwable $throwable): int
    {
        $this->logger->error($throwable);

        return 0;
    }

    private function getArguments(array $arguments): array
    {
        $args = [];

        for ($i = 1; $i < count($arguments); $i++) {
            $a = explode('=', $arguments[$i]);

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
        $force = array_key_exists('--force', $arguments);

        try {
            $coreVar = CoreVar::getRepository()->findByName('database.version');

            $version = intval($coreVar->var_value) ?? 0;
        } catch (Throwable) {
            $version = 0;
        }

        if ($initDbVersion !== null) {
            if ($initDbVersion > $version) {
                $this->logger->debug('Start upgrading migration from ' . $version . ' to ' . $initDbVersion);

                if (task(new MigrationUpTask($version, $initDbVersion, $force))->sync())
                    $this->logger->debug('Upgrade migration from ' . $version . ' to ' . $initDbVersion);
            } else if ($initDbVersion < $version) {
                $this->logger->debug('Start downgrading migration from ' . $version . ' to ' . $initDbVersion);

                if (task(new MigrationDownTask($version, $initDbVersion, $force))->sync())
                    $this->logger->debug('Downgrade migration from ' . $version . ' to ' . $initDbVersion);
            }
        } else {
            $this->logger->debug('Start upgrading migration from ' . $version . ' to latest');

            if (task(new MigrationUpTask($version, null, $force))->sync())
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

            $this->logger->debug('CONTAINER TABLE:');
            $this->logger->debug($this->table($headers, $result));
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function kernelOptimize(): void
    {
        $cache = container(FileCache::class);

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

            $cache->set('container', $container->getContainer()->getFactories());
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

        if (file_exists(path('config/frontend.php'))) {
            $router = new class {
                use RouterTrait;

                public function __construct()
                {
                    $this->loadRouter(false, 'frontend');
                }
            };

            $cache->set('frontend', $router->getRouter()->getRoutes());
        }

        kernel()->setEnv($kernelEnv);
        kernel()->setConfig($kernelConfig);

        $this->logger->debug('Kernel optimized');
    }

    private function kernelClear(): void
    {
        $cache = container(FileCache::class);

        $cache->clear();

        $this->logger->debug('Kernel cleared');
    }

    private function kernelWipe(): void
    {
        container(PrometheusService::class)->wipe();
    }

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
        $db = container(DatabaseService::class);

        /** @var array<string, Permission> $titlePermissions */
        $titlePermissions = array_reduce(Permission::fetchAll(), static function (array $previous, Permission $current) {
            $previous[$current->title] = $current;

            return $previous;
        }, []);

        $dir = path('controller/Api');
        $apis = scandir($dir);

        foreach ($apis as $api) {
            if ($api != "." && $api != ".." && is_dir($dir . "/$api")) {
                $methods = scandir($dir . "/$api");

                foreach ($methods as $method) {
                    if ($method != "." && $method != ".." && str_ends_with($method, ".php") && is_file($dir . "/$api/$method")) {
                        $method = substr($method, 0, -4);

                        require_once $dir . "/$api/$method.php";

                        /** @var class-string<Api> $class */
                        $class = "Selpol\\Controller\\Api\\$api\\$method";

                        if (class_exists($class)) {
                            $request_methods = $class::index();

                            if ($request_methods) {
                                $keys = array_keys($request_methods);

                                foreach ($keys as $key) {
                                    $title = $api . '-' . $method . '-' . strtolower(is_int($key) ? $request_methods[$key] : $key);
                                    $description = is_int($key) ? $title : $request_methods[$key];

                                    if (!array_key_exists($title, $titlePermissions)) {
                                        $permission = new Permission();

                                        $permission->title = $title;
                                        $permission->description = $description;

                                        $permission->insert();
                                    } else if ($titlePermissions[$title]->description != $description) {
                                        $titlePermissions[$title]->description = $description;
                                        $titlePermissions[$title]->update();
                                    }

                                    unset($titlePermissions[$title]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $requirePermissions = [
            'intercom-hidden' => '[Домофон] Доступ к скрытым устройствам',
            'camera-hidden' => '[Камера] Доступ к скрытым устройствам'
        ];

        foreach ($requirePermissions as $title => $description) {
            if (!array_key_exists($title, $titlePermissions)) {
                $permission = new Permission();

                $permission->title = $title;
                $permission->description = $description;

                $permission->insert();
            } else if ($titlePermissions[$title]->description != $description) {
                $titlePermissions[$title]->description = $description;
                $titlePermissions[$title]->update();
            }

            unset($titlePermissions[$title]);
        }

        foreach ($titlePermissions as $permission)
            $permission->delete();
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    private function roleClear(): void
    {
        Permission::getRepository()->deleteSql();
    }

    private function deviceSync(int $id): void
    {
        try {
            $deviceIntercom = DeviceIntercom::findById($id, setting: setting()->columns(['house_domophone_id']));

            if ($deviceIntercom)
                task(new IntercomConfigureTask($deviceIntercom->house_domophone_id))->sync();
            else echo 'Домофон не найден' . PHP_EOL;
        } catch (Throwable $throwable) {
            echo 'Ошибка синхронизации. ' . $throwable . PHP_EOL;
        }
    }

    private function deviceReboot(int $id): void
    {
        if ($device = intercom($id)) $device->reboot();
        else echo 'Домофон не найден' . PHP_EOL;
    }

    private function deviceReset(int $id): void
    {
        if ($device = intercom($id)) $device->reset();
        else echo 'Домофон не найден' . PHP_EOL;
    }

    private function deviceCall(int $id): void
    {
        if ($device = intercom($id)) $device->callStop();
        else echo 'Домофон не найден' . PHP_EOL;
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

        if ($group === null || $group === 'device')
            $result[] = implode(PHP_EOL, [
                '',
                'device:sync=<id>                 - Синхронизация домофона',
                'device:call=<id>                 - Остановить звонки на домофоне',
                'device:reboot=<id>               - Перезапуск домофона',
                'device:reset=<id>                - Сбросить домофон'
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
}