<?php declare(strict_types=1);

namespace Selpol\Cli\Admin;

use Exception;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\DatabaseService;

#[Executable('admin:password', 'Обновление пароля администратора')]
class AdminPasswordCommand
{
    #[Execute]
    public function execute(CliIO $io, DatabaseService $service, string $value): void
    {
        $connection = $service->getConnection();

        try {
            $connection->exec("insert into core_users (uid, login, password) values (0, 'admin', 'admin')");
        } catch (Exception) {
        }

        try {
            $sth = $connection->prepare("update core_users set password = :password, login = 'admin', enabled = 1 where uid = 0");
            $sth->execute([":password" => password_hash($value, PASSWORD_DEFAULT)]);

            $io->writeLine('Admin account update successes');
        } catch (Exception) {
            $io->writeLine('Admin account update failed');
        }
    }
}