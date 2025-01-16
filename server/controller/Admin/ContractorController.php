<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Device\ContractIndexRequest;
use Selpol\Controller\Request\Admin\Device\ContractStoreRequest;
use Selpol\Controller\Request\Admin\Device\ContractSyncRequest;
use Selpol\Controller\Request\Admin\Device\ContractUpdateRequest;
use Selpol\Entity\Model\Contractor;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Task\Tasks\Contractor\ContractorSyncTask;

/**
 * Подрядчик
 */
#[Controller('/admin/contractor')]
readonly class ContractorController extends AdminRbtController
{
    /**
     * Получить список подрядчиков
     */
    #[Get]
    public function index(ContractIndexRequest $request): ResponseInterface
    {
        $criteria = criteria()->like('title', $request->title)->equal('flat', $request->flat)->asc('id');

        return self::success(Contractor::fetchPage($request->page, $request->size, $criteria));
    }

    /**
     * Получить подрядчика
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $contractor = Contractor::findById($id);

        if ($contractor) {
            return self::success($contractor);
        }

        return self::error('Не удалось найти подрядчика', 404);
    }

    /**
     * Синхронизация подрядчика
     */
    #[Get('/sync')]
    public function sync(ContractSyncRequest $request)
    {
        $contactor = Contractor::findById($request->id, setting: setting()->columns(['id']));

        if (!$contactor) {
            return self::error('Не удалось найти подрядчика', 404);
        }

        task(new ContractorSyncTask($contactor->id, $request->remove_subscriber, $request->remove_key))->high()->dispatch();

        return self::success();
    }

    /**
     * Создать нового подрядчика
     */
    #[Post]
    public function store(ContractStoreRequest $request): ResponseInterface
    {
        $contractor = new Contractor();

        $contractor->title = $request->title;
        $contractor->flat = $request->flat;
        $contractor->code = $request->code;

        $contractor->insert();

        return self::success($contractor->id);
    }

    /**
     * Обновить подрядчика
     */
    #[Put('/{id}')]
    public function update(ContractUpdateRequest $request): ResponseInterface
    {
        $contractor = Contractor::findById($request->id);

        if (!$contractor) {
            return self::error('Не удалось найти подрядчика', 404);
        }

        $contractor->title = $request->title;
        $contractor->flat = $request->flat;
        $contractor->code = $request->code;

        $contractor->update();

        return self::success();
    }

    /**
     * Удалить подрядчика
     * 
     * @param int $id Идентификатор подрядчика 
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $contractor = Contractor::findById($id);

        if (!$contractor) {
            return self::error('Не удалось найти подрядчика', 404);
        }

        $contractor->delete();

        return self::success();
    }
}