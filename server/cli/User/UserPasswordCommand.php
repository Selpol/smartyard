<?php declare(strict_types=1);

namespace Selpol\Cli\User;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\DatabaseService;
use Throwable;

#[Executable('user:password', 'Обновление пароля пользователя')]
class UserPasswordCommand
{
    #[Execute]
    public function execute(CliIO $io, DatabaseService $service, int $id, string $password): void
    {
        $connection = $service->getConnection();

        try {
            $sth = $connection->prepare("update core_users set password = :password where uid = :uid");
            $sth->execute(["password" => password_hash($password, PASSWORD_DEFAULT), 'uid' => $id]);

            $io->writeLine('Update user password successes');
        } catch (Throwable) {
            $io->writeLine('Update user password failed');
        }
    }
}