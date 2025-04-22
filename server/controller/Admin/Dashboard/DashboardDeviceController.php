<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Dashboard;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Task\Tasks\Intercom\IntercomSipTask;

/**
 * Панель-Устройства
 */
#[Controller('/admin/dashboard/device')]
readonly class DashboardDeviceController extends AdminRbtController
{
    /**
     * Формирования списка устройств, без регистрации
     */
    #[Get('/sip')]
    public function sip(): ResponseInterface
    {
        task(new IntercomSipTask())->high()->dispatch();

        return self::success();
    }
}