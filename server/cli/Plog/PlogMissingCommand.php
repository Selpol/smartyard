<?php declare(strict_types=1);

namespace Selpol\Cli\Plog;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Entity\Database\EntityStatementInterface;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Service\ClickhouseService;
use Selpol\Task\Tasks\Plog\PlogOpenTask;
use Throwable;

#[Executable('plog:missing', 'Импорт потеренных событий')]
class PlogMissingCommand
{
    #[Execute]
    public function execute(CliIO $io, ClickhouseService $service): void
    {
        $io->writeLine('Загрузка логов событий');

        $is = $this->loadIsLogEvents($io, $service);
        $beward = $this->loadBewardLogEvents($io, $service);

        $events = array_merge($is, $beward);

        $io->writeLine('Логов событий загружено ' . count($events));

        $length = count($events);
        $step = 100 / $length;
        $count = 0;

        $io->getOutputCursor()->erase();

        $bar = $io->getOutput()->getBar('Событий 0/' . $length);

        $bar->show();

        $missing = 0;

        $database = $service->database;
        $statement = $service->statement("SELECT * FROM $database.plog WHERE date BETWEEN :start AND :end AND tupleElement(domophone, 2) = :intercom AND rfid = :rfid AND event = 3");

        foreach ($events as $event) {
            try {
                if ($this->uploadMissingEvent($io, $statement, $event)) {
                    $missing += 1;
                }
            } catch (Throwable $throwable) {
                $io->writeLine($throwable->getMessage());

                break;
            }

            $count++;

            $bar->label('Событий ' . $count . '/' . $length);
            $bar->advance($step);
        }

        $bar->hide();
        $io->writeLine('Missing ' . $missing);
    }

    private function loadIsLogEvents(CliIO $io, ClickhouseService $service): array
    {
        $database = $service->database;

        $statement = $service->statement("SELECT date, ip, msg FROM $database.syslog WHERE unit = 'is' AND msg LIKE 'Opening door by RFID%, apartment%' AND date >= 1756242000");

        return $this->loadLogEvents($io, $statement);
    }

    private function loadBewardLogEvents(CliIO $io, ClickhouseService $service): array
    {
        $database = $service->database;

        $statement = $service->statement("SELECT date, ip, msg FROM $database.syslog WHERE unit = 'beward' AND msg LIKE 'Opening door by RFID%, apartment%' AND date >= 1756242000");

        return $this->loadLogEvents($io, $statement);
    }

    private function loadLogEvents(CliIO $io, EntityStatementInterface $statement): array
    {
        if (!$statement->execute()) {
            $errors = $statement->error();

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $io->writeLine($error->message);
                }
            } else {
                $io->writeLine('Не удалось загрузить логи');
            }

            return [];
        }

        /**
         * Массив ip => intercom
         * @var array<string, int>
         */
        $intercoms = [];

        $values = $statement->fetchAll();

        return array_reduce($values, static function (array $previous, array $current) use (&$intercoms): array {
            if (!array_key_exists($current['ip'], $intercoms)) {
                $intercom = DeviceIntercom::fetch(criteria()->equal('ip', $current['ip']), setting()->columns(['house_domophone_id']));

                if (!$intercom) {
                    return $previous;
                }

                $intercoms[$current['ip']] = $intercom->house_domophone_id;
            }

            $rfid = substr($current['msg'], 21, 14);
            $apartment = substr($current['msg'], 47);

            if ($apartment == '0') {
                return $previous;
            }

            $previous[] = [
                'intercom' => $intercoms[$current['ip']],
                'date' => $current['date'],
                'rfid' => $rfid,
                'apartment' => $apartment,
            ];

            return $previous;
        }, []);
    }

    private function uploadMissingEvent(CliIO $io, EntityStatementInterface $statement, array $event): bool
    {
        $start = $event['date'] - 20;
        $end = $event['date'] + 20;

        $statement
            ->bind('start', $start)
            ->bind('end', $end)
            ->bind('intercom', $event['intercom'])
            ->bind('rfid', $event['rfid']);

        if (!$statement->execute()) {
            $errors = $statement->error();

            foreach ($errors as $error) {
                throw new KernelException($error->message, $error->message, $error->code);
            }

            return false;
        }

        if ($statement->fetch() != null) {
            return false;
        }

        task(new PlogOpenTask($event['intercom'], PlogFeature::EVENT_OPENED_BY_KEY, 0, $event['date'], $event['rfid']))->sync();

        $io->writeLine('Event(' . $event['date'] . ', ' . $event['intercom'] . ', ' . $event['rfid'] . ')');

        return true;
    }
}