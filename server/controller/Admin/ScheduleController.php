<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Schedule\ScheduleIndexRequest;
use Selpol\Controller\Request\Admin\Schedule\ScheduleStoreRequest;
use Selpol\Controller\Request\Admin\Schedule\ScheduleUpdateRequest;
use Selpol\Entity\Model\Schedule;
use Selpol\Feature\Schedule\ScheduleFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Расписание
 */
#[Controller('/admin/schedule')]
readonly class ScheduleController extends AdminRbtController
{
    /**
     * Получить список расписания
     * @param ScheduleIndexRequest $request
     * @return ResponseInterface
     */
    #[Get]
    public function index(ScheduleIndexRequest $request): ResponseInterface
    {
        return self::success(Schedule::fetchPage($request->page, $request->size, criteria()->like('title', $request->title)->equal('status', $request->status)->asc('id')));
    }

    /**
     * Получить расписание
     * @param int $id Идентификатор расписания
     * @return ResponseInterface
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $schedule = Schedule::findById($id);

        if (!$schedule) {
            return self::error('Не удалось найти расписание', 404);
        }

        return self::success($schedule);
    }

    /**
     * Создать новое расписание
     * @param ScheduleStoreRequest $request
     * @param ScheduleFeature $feature
     * @return ResponseInterface
     */
    #[Post]
    public function store(ScheduleStoreRequest $request, ScheduleFeature $feature): ResponseInterface
    {
        $schedule = new Schedule();
        $schedule->fill($request->all());

        try {
            $feature->check($schedule);
        } catch (KernelException $throwable) {
            return self::error($throwable->getLocalizedMessage(), 400);
        }

        $schedule->insert();

        return self::success($schedule->id);
    }

    /**
     * Обновить расписание
     * @param ScheduleUpdateRequest $request
     * @param ScheduleFeature $feature
     * @return ResponseInterface
     */
    #[Put('/{id}')]
    public function update(ScheduleUpdateRequest $request, ScheduleFeature $feature): ResponseInterface
    {
        $schedule = Schedule::findById($request->id);

        if (!$schedule) {
            return self::error('Не удалось найти расписание', 404);
        }

        $schedule->fill($request->all());

        try {
            $feature->check($schedule);
        } catch (KernelException $throwable) {
            return self::error($throwable->getLocalizedMessage(), 400);
        }

        $schedule->update();

        return self::success();
    }

    /**
     * Удалить расписание
     * @param int $id Идентификатор расписания
     * @return ResponseInterface
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $schedule = Schedule::findById($id);

        if (!$schedule) {
            return self::error('Не удалось найти расписание', 404);
        }

        $schedule->delete();

        return self::success();
    }
}
