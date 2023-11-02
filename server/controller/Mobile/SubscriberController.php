<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Validator\Exception\ValidatorException;

#[Controller('/mobile/subscriber')]
readonly class SubscriberController extends RbtController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Get('/{flatId}')]
    public function index(int $flatId): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            return user_response(404, message: 'Квартира не найдена у абонента');

        $subscribers = container(HouseFeature::class)->getSubscribers('flatId', $flat['flatId']);

        return user_response(
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
    #[Post('/{flatId}')]
    public function store(ServerRequestInterface $request, int $flatId): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator($request->getParsedBody() ?? [], [
            'mobile' => rule()->required()->int()->min(70000000000)->max(79999999999)->nonNullable()
        ]);

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            return user_response(404, message: 'Квартира не найдена у абонента');
        if ($flat['role'] !== 0)
            return user_response(403, message: 'Недостаточно прав для добавления нового жителя');

        $households = container(HouseFeature::class);

        $subscribers = $households->getSubscribers('mobile', $validate['mobile']);

        if (!$subscribers || count($subscribers) === 0) {
            $id = $households->addSubscriber($validate['mobile'], 'Имя', 'Отчество', flatId: $flatId);

            if (!$id)
                return user_response(400, message: 'Неудалось зарегестрировать жителя');

            $subscribers = $households->getSubscribers('id', $id);
        }

        $subscriber = $subscribers[0];

        $subscriberFlat = $this->getFlat($subscriber['flats'], $flat['flatId']);

        if ($subscriberFlat)
            return user_response(400, message: 'Житель уже добавлен');

        if (!$households->addSubscriberToFlat($flat['flatId'], $subscriber['subscriberId']))
            return user_response(400, message: 'Житель не был добавлен');

        return user_response();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Delete('/{flatId}')]
    public function delete(ServerRequestInterface $request, int $flatId): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator(['subscriberId' => $request->getQueryParams()['subscriberId']], ['subscriberId' => rule()->id()]);

        $flat = $this->getFlat($user['flats'], $flatId);

        if ($flat === null)
            return user_response(404, message: 'Квартира не найдена');

        if ($flat['role'] !== 0)
            return user_response(403, message: 'Недостаточно прав для удаления жителя');

        $households = container(HouseFeature::class);

        $subscribers = $households->getSubscribers('id', $validate['subscriberId']);

        if (!$subscribers || count($subscribers) === 0)
            return user_response(404, message: 'Житель не зарегестрирован');

        $subscriber = $subscribers[0];

        $subscriberFlat = $this->getFlat($subscriber['flats'], $flat['flatId']);

        if (!$subscriberFlat)
            return user_response(400, message: 'Житель не заселен в данной квартире');

        if ($subscriberFlat['role'] == 0)
            return user_response(403, message: 'Житель является владельцем квартиры');

        if (!$households->removeSubscriberFromFlat($flat['flatId'], $subscriber['subscriberId']))
            return user_response(400, message: 'Житель не был удален');

        return user_response();
    }

    private function getFlat(array $value, int $id): ?array
    {
        foreach ($value as $item)
            if ($item['flatId'] === $id)
                return $item;

        return null;
    }
}