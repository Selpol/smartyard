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
        $validate = validator($params, [
            '_id' => rule()->id(),

            'remove_subscriber' => [filter()->default(false), rule()->bool()],
            'remove_key' => [filter()->default(false), rule()->bool()]
        ]);

        $contactor = \Selpol\Entity\Model\Contractor::findById($validate['_id'], setting: setting()->columns(['id']));

        if ($contactor) {
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
        return ['GET' => '[Подрядчики] Синхронизация подрядчика'];
    }
}