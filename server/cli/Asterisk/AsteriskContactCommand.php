<?php declare(strict_types=1);

namespace Selpol\Cli\Asterisk;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\Asterisk\Contact;
use Selpol\Service\AsteriskService;

#[Executable('asterisk:contact', 'Активные контакты в телефонии')]
class AsteriskContactCommand
{
    #[Execute]
    public function execute(CliIO $io, AsteriskService $service): int
    {
        $contacts = $service->contacts();

        $io->getOutput()->table(
            [
                'value',
                'ip'
            ],
            array_map(static fn(Contact $contact) => ['value' => $contact->value, 'ip' => $contact->ip], $contacts)
        );

        return 0;
    }
}