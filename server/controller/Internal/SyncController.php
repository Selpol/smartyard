<?php

namespace Selpol\Controller\Internal;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Validator\Rule;
use Selpol\Validator\ValidatorException;

class SyncController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getHouseId(): Response
    {
        $fias = $this->getRoute()->getParam('fias');

        $validate = validator(['fias' => $fias], ['fias' => [Rule::required(), Rule::uuid(), Rule::nonNullable()]]);

        $house = backend('addresses')->getHouseByFias($validate['fias']);

        if ($house)
            return $this->rbtResponse(data: $house['houseId']);

        return $this->rbtResponse(404);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addSubscriber(): Response
    {
        $body = $this->request->getParsedBody();

        $validate = validator($body, [
            'id' => [Rule::required(), Rule::length(11, 11), Rule::nonNullable()],
            'audJti' => [Rule::required(), Rule::nonNullable()],
            'name' => [Rule::required(), Rule::length(1, 32), Rule::nonNullable()],
            'patronymic' => [Rule::required(), Rule::length(1, 32), Rule::nonNullable()]
        ]);

        $subscriberId = backend('households')->addSubscriber($validate['id'], $validate['name'], $validate['patronymic'], $validate['audJti']);

        if ($subscriberId)
            return $this->rbtResponse(data: $subscriberId);

        return $this->rbtResponse(400, message: 'Абонент не создан');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function deleteSubscriber(): Response
    {
        $id = $this->getRoute()->getParamIdOrThrow('id');

        if (backend('households')->deleteSubscriber($id))
            return $this->rbtResponse();

        return $this->rbtResponse(404);
    }
}