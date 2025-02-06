<?php declare(strict_types=1);

namespace Selpol\Cli\Plog;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\Clickhouse\ClickhouseEntityConnection;

#[Executable('plog:migrate', 'Миграция событий')]
class PlogMigrateCommand
{
    #[Execute]
    public function execute(CliIO $io): void
    {
        $time = time();
        $io->writeLine('Migration start');

        $oldConnection = new ClickhouseEntityConnection(env('OLD_CLICKHOUSE_ENDPOINT'), env('OLD_CLICKHOUSE_USERNAME'), env('OLD_CLICKHOUSE_PASSWORD'));
        $oldConnection = new ClickhouseEntityConnection(env('NEW_CLICKHOUSE_ENDPOINT'), env('NEW_CLICKHOUSE_USERNAME'), env('NEW_CLICKHOUSE_PASSWORD'));

        $select = $oldConnection->statement('SELECT date, event_uuid, hidden, image_uuid, flat_id, toJSONString(domophone) as domophone, event, opened, toJSONString(face) as face, rfid, code, toJSONString(phones) as phones, preview FROM prod.plog WHERE hidden = 0 AND date >= 1738824267 LIMIT :offset, :limit');

        $processed = 0;
        $count = 0;

        while (true) {
            $start = time();

            if (!$select->execute(['offset' => 1000 * $count++, 'limit' => 1000])) {
                $io->writeLine('Select error: ' . json_encode($select->error()));

                break;
            }

            $values = $select->fetchAll();
            $length = count($values);

            $query = 'INSERT INTO prod.plog(`date`, `event_uuid`, `hidden`, `image_uuid`, `flat_id`, `domophone`, `event`, `opened`, `face`, `rfid`, `code`, `phones`, `preview`) VALUES ';

            for ($i = 0; $i < $length; $i++) {
                $value = $values[$i];

                $value['domophone'] = json_decode($value['domophone'], true);
                $value['face'] = json_decode($value['face'], true);
                $value['phones'] = json_decode($value['phones'], true);

                $query .= '(';

                $query .= $value['date'] . ', ';
                $query .= "'" . $value['event_uuid'] . "', ";
                $query .= $value['hidden'] . ', ';
                $query .= "'" . $value['image_uuid'] . "', ";
                $query .= $value['flat_id'] . ', ';
                $query .= "tuple('" . $value['domophone']['domophone_description'] . "', " . $value['domophone']['domophone_id'] . ', ' . $value['domophone']['domophone_output'] . '), ';
                $query .= $value['event'] . ', ';
                $query .= $value['opened'] . ', ';
                $query .= "tuple('" . $value['face']['faceId'] . "', " . ($value['face']['height'] ?? 0) . ', ' . ($value['face']['left'] ?? 0) . ', ' . ($value['face']['top'] ?? 0) . ', ' . ($value['face']['width'] ?? 0) . '), ';
                $query .= "'" . $value['rfid'] . "', ";
                $query .= "'" . $value['code'] . "', ";
                $query .= 'tuple(' . ($value['phones']['user_phone'] == '' ? 'NULL' : $value['phones']['user_phone']) . '), ';
                $query .= "'" . $value['preview'] . "'";

                $query .= ')';

                if ($i + 1 < $length) {
                    $query .= ',' . PHP_EOL;
                }
            }

            $insert = $oldConnection->statement($query);

            if (!$insert->execute()) {
                $io->writeLine('Insert error: ' . json_encode($insert->error()));

                break;
            }

            $processed += $length;

            $io->writeLine('Processed: ' . $processed . ', ' . (time() - $start) . 's');

            if (count($values) < 1000) {
                break;
            }
        }

        $io->writeLine('Migration done: ' . (time() - $time) . 's');
    }
}