<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Group\GroupIndexRequest;
use Selpol\Controller\Request\Admin\Group\GroupStoreRequest;
use Selpol\Controller\Request\Admin\Group\GroupUpdateRequest;
use Selpol\Feature\Group\GroupFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Группы
 */
#[Controller('/admin/group')]
readonly class GroupController extends AdminRbtController
{
    /**
     * Получить список групп
     */
    #[Get]
    public function index(GroupIndexRequest $request, GroupFeature $feature): ResponseInterface
    {
        $result = $feature->find(
            $request->name,
            $request->type ? GroupFeature::TYPE_MAP[$request->type] : null,
            $request->for ? GroupFeature::FOR_MAP[$request->for] : null,
            $request->id,
            $request->page,
            $request->size
        );

        if ($result) {
            return self::success($result);
        }

        return self::success([]);
    }

    /**
     * Получить группу
     * 
     * @param string $oid Индентификатор группы
     */
    #[Get('/{oid}')]
    public function show(string $oid, GroupFeature $feature): ResponseInterface
    {
        $group = $feature->get($oid);

        if ($group) {
            return self::success($group);
        }

        return self::error('Не удалось найти группу', 404);
    }

    /**
     * Создать новую группу
     */
    #[Post]
    public function store(GroupStoreRequest $request, GroupFeature $feature): ResponseInterface
    {
        $result = $feature->insert(
            $request->name,
            GroupFeature::TYPE_MAP[$request->type],
            GroupFeature::FOR_MAP[$request->for],
            $request->id,
            $request->value
        );

        return $result ? self::success($result) : self::error('Не удалось создать группу', 400);
    }

    /**
     * Обновить группу
     */
    #[Put('/{oid}')]
    public function update(GroupUpdateRequest $request, GroupFeature $feature): ResponseInterface
    {
        $result = $feature->update(
            $request->oid,
            $request->name,
            GroupFeature::TYPE_MAP[$request->type],
            GroupFeature::FOR_MAP[$request->for],
            $request->id,
            $request->value
        );

        return $result ? self::success() : self::error('Не удалось обновить группу', 400);
    }

    /**
     * Удалить группу
     */
    #[Delete('/{oid}')]
    public function delete(string $oid, GroupFeature $feature): ResponseInterface
    {
        if ($feature->delete($oid)) {
            return self::success();
        }

        return self::error('Не удалось удалить группуп', 404);
    }
}
