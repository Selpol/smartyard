<?php declare(strict_types=1);

namespace Selpol\Controller\Api\contractor;

use Selpol\Entity\Model\Contractor;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Framework\Http\Response;
use Selpol\Task\Tasks\Contractor\ContractorSyncTask;

readonly class sync extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'remove_subscriber' => [filter()->default(false), rule()->bool()],
            'remove_key' => [filter()->default(false), rule()->bool()]
        ]);

        $contactor = Contractor::findById($validate['_id'], setting: setting()->columns(['id']));

        if ($contactor instanceof Contractor) {
            task(new ContractorSyncTask(
                $contactor->id,
                $validate['remove_subscriber'] ?: false,
                $validate['remove_key'] ?: false
            ))->high()->dispatch();

            return self::success();
        }

        return self::error('Подрядчик не найден', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Deprecated] [Подрядчики] Синхронизация подрядчика'];
    }
}