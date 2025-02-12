<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Server\ServerVariableUpdateRequest;
use Selpol\Controller\Request\PageRequest;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Сервер-Переменные
 */
#[Controller('/admin/server/variable')]
readonly class ServerVariableController extends AdminRbtController
{
    /**
     * Получить список переменных
     */
    #[Get]
    public function index(PageRequest $request): ResponseInterface
    {
        return self::success(CoreVar::fetchPage($request->page, $request->size, criteria()->equal('hidden', false)->asc('var_id')));
    }

    /**
     * Обновить переменную
     */
    #[Put('/{var_id}')]
    public function update(ServerVariableUpdateRequest $request): ResponseInterface
    {
        $coreVar = CoreVar::findById($request->var_id);

        if (!$coreVar) {
            return self::error('Не удалось найти переменную', 404);
        }

        if (!$coreVar->editable) {
            return self::error('Переменную нельзя редактировать', 403);
        }

        $coreVar->var_value = $request->var_value;
        $coreVar->update();

        return self::success();
    }
}
