<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\House\HouseFeature;
use Selpol\Http\Response;
use Selpol\Validator\Exception\ValidatorException;
use Selpol\Validator\Rule;

class SubscriberController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function index(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $flatId = $this->getRoute()->getParamIntOrThrow('flatId');

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            return $this->rbtResponse(404, message: 'Квартира не найдена у абонента');

        $subscribers = container(HouseFeature::class)->getSubscribers('flatId', $flat['flatId']);

        return $this->rbtResponse(
            data: array_map(
                fn(array $subscriber) => [
                    'subscriberId' => $subscriber['subscriberId'],
                    'name' => $subscriber['subscriberName'] . ' ' . $subscriber['subscriberPatronymic'],
                    'mobile' => substr($subscriber['mobile'], -4),
                    'role' => @($this->getFlat($subscriber['flats'], $flat['flatId'])['role'])
                ],
                $subscribers
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function store(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $flatId = $this->getRoute()->getParamIntOrThrow('flatId');

        $validate = validator($this->request->getParsedBody() ?? [], [
            'mobile' => [Rule::required(), Rule::int(), Rule::min(70000000000), Rule::max(79999999999), Rule::nonNullable()]
        ]);

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            return $this->rbtResponse(404, message: 'Квартира не найдена у абонента');
        if ($flat['role'] !== 0)
            return $this->rbtResponse(403, message: 'Недостаточно прав для добавления нового жителя');

        $households = container(HouseFeature::class);

        $subscribers = $households->getSubscribers('mobile', $validate['mobile']);

        if (!$subscribers || count($subscribers) === 0) {
            $id = $households->addSubscriber($validate['mobile'], 'Имя', 'Отчество', flatId: $flatId);

            if (!$id)
                return $this->rbtResponse(400, message: 'Неудалось зарегестрировать жителя');

            $subscribers = $households->getSubscribers('id', $id);
        }

        $subscriber = $subscribers[0];

        $subscriberFlat = $this->getFlat($subscriber['flats'], $flat['flatId']);

        if ($subscriberFlat)
            $this->rbtResponse(400, message: 'Житель уже добавлен');

        if (!$households->addSubscriberToFlat($flat['flatId'], $subscriber['subscriberId']))
            $this->rbtResponse(400, message: 'Житель не был добавлен');

        return $this->rbtResponse();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function delete(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $flatId = $this->getRoute()->getParamIntOrThrow('flatId');

        $validate = validator(['subscriberId' => $this->request->getQueryParam('subscriberId')], ['subscriberId' => [Rule::id()]]);

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            return $this->rbtResponse(404, message: 'Квартира не найдена');

        if ($flat['role'] !== 0)
            return $this->rbtResponse(403, message: 'Недостаточно прав для удаления жителя');

        $households = container(HouseFeature::class);

        $subscribers = $households->getSubscribers('id', $validate['subscriberId']);

        if (!$subscribers || count($subscribers) === 0)
            return $this->rbtResponse(404, message: 'Житель не зарегестрирован');

        $subscriber = $subscribers[0];

        $subscriberFlat = $this->getFlat($subscriber['flats'], $flat['flatId']);

        if (!$subscriberFlat)
            return $this->rbtResponse(400, message: 'Житель не заселен в данной квартире');

        if ($subscriberFlat['role'] == 0)
            return $this->rbtResponse(403, message: 'Житель является владельцем квартиры');

        if (!$households->removeSubscriberFromFlat($flat['flatId'], $subscriber['subscriberId']))
            return $this->rbtResponse(400, message: 'Житель не был удален');

        return $this->rbtResponse();
    }

    private function getFlat(array $value, int $id): ?array
    {
        foreach ($value as $item)
            if ($item['flatId'] === $id)
                return $item;

        return null;
    }
}