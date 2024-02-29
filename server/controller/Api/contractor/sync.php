<?php declare(strict_types=1);

namespace Selpol\Controller\Api\contractor;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Framework\Http\Response;
use Selpol\Task\Tasks\Contractor\ContractorSyncTask;

readonly class sync extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $contactor = \Selpol\Entity\Model\Contractor::findById(rule()->id()->onItem('_id', $params), setting: setting()->columns(['id']));

        if ($contactor) {
            task(new ContractorSyncTask($contactor->id))->high()->dispatch();

            return self::success();
        }

        return self::error('Подрядчик не найден', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Подрядчики] Синхронизация подрядчика'];
    }
}