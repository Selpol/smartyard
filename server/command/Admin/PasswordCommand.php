<?php declare(strict_types=1);

namespace Selpol\Command\Admin;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\DatabaseService;

#[Executable('admin:password', 'Обновить пароль администратора')]
class PasswordCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(CliIO $io, DatabaseService $service): void
    {
        $io->getOutput()->write('Введите пароль: ');

        $password = password_hash($io->getInput()->readHiddenLine(), PASSWORD_DEFAULT);

        $io->getOutputCursor()->moveCursorUp(2);
        $io->getOutputCursor()->eraseLine();
        $io->getOutputCursor()->eraseSave();

        $coreUser = CoreUser::findById(0);

        if ($coreUser) {
            $coreUser->password = $password;

            if ($coreUser->update())
                $this->getLogger()->debug('Пароль администратора обновлен');
            else
                $this->getLogger()->info('Не удалось обновить пароль администратора');
        } else {
            $statement = $service->getConnection()->prepare('INSERT INTO core_users(uid, login, password) VALUES (0, :login, :password)');

            if ($statement->execute(['login' => 'admin', 'password' => $password]))
                $this->getLogger()->debug('Пароль администратора обновлен');
            else
                $this->getLogger()->info('Не удалось обновить пароль администратора');
        }
    }
}