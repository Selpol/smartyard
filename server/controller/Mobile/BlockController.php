<?php declare(strict_types=1);

namespace Selpol\Controller\Mobile;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

#[Controller('/mobile/block')]
readonly class BlockController extends RbtController
{
    #[Get]
    public function index(ServerRequestInterface $request, BlockFeature $blockFeature): ResponseInterface
    {
        $query = $request->getQueryParams();

        if (array_key_exists('services', $query))
            return user_response(data: $blockFeature->getBlocksForSubscriber($this->getUser()->getIdentifier(), $query['services']));

        return user_response(data: $blockFeature->getBlocksForSubscriber($this->getUser()->getIdentifier(), null));
    }

    #[Get('/{id}')]
    public function show(int $id, ServerRequestInterface $request, BlockFeature $blockFeature): ResponseInterface
    {
        $query = $request->getQueryParams();

        if (array_key_exists('services', $query))
            return user_response(data: $blockFeature->getBlocksForFlat($id, $query['services']));

        return user_response(data: $blockFeature->getBlocksForFlat($id, null));
    }
}