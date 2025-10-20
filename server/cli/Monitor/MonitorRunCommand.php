<?php declare(strict_types=1);

namespace Selpol\Cli\Monitor;

use Exception;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\RedisService;

#[Executable('monitor:run', 'Запустить проверку мониторинга')]
class MonitorRunCommand
{
    /**
     * @throws Exception
     */
    #[Execute]
    public function execute(CliIO $io, RedisService $service): void
    {
        $monitoring = $service->monitor();

        /** @var DeviceCamera[] $cameras */
        $cameras = DeviceCamera::fetchAll();

        /** @var array<int, array<string, bool>> $dvrs */
        $dvrs = [];

        $result = [];
        $length = count($cameras);
        $step = 100 / $length;

        $bar = $io->getOutput()->getBar('Обработано 0/' . $length);

        $bar->show();

        $count = 0;

        foreach ($cameras as $camera) {
            $count++;

            if (!$camera->dvr_server_id || !$camera->dvr_stream) {
                $monitoring->set('status:' . $camera->camera_id, false);
                $result[] = ['camera' => $camera->camera_id, 'status' => 'f0'];

                $bar->advance($step);
                $bar->label('Обработано ' . $count . '/' . $length);

                continue;
            }

            if (!array_key_exists($camera->dvr_server_id, $dvrs)) {
                $dvr = dvr($camera->dvr_server_id);

                if (!$dvr) {
                    $monitoring->set('status:' . $camera->camera_id, false);
                    $result[] = ['camera' => $camera->camera_id, 'status' => 'f1'];

                    $bar->advance($step);
                    $bar->label('Обработано ' . $count . '/' . $length);

                    continue;
                }

                $dvrs[$camera->dvr_server_id] = $dvr->getStatuses(null);
            }

            if (!array_key_exists($camera->dvr_stream, $dvrs[$camera->dvr_server_id])) {
                $monitoring->set('status:' . $camera->camera_id, false);
                $result[] = ['camera' => $camera->camera_id, 'status' => 'f2'];

                $bar->advance($step);
                $bar->label('Обработано ' . $count . '/' . $length);

                continue;
            }

            $monitoring->set('status:' . $camera->camera_id, $dvrs[$camera->dvr_server_id][$camera->dvr_stream]);
            $result[] = ['camera' => $camera->camera_id, 'status' => $dvrs[$camera->dvr_server_id][$camera->dvr_stream] ? 't' : 'f3'];

            $bar->advance($step);
            $bar->label('Обработано ' . $count . '/' . $length);

        }

        $bar->hide();

        $io->getOutputCursor()->erase();
        $io->getOutput()->table(['camera', 'status'], $result);
    }
}
