<?php declare(strict_types=1);

namespace Selpol\Controller\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Entity\Repository\RoleRepository;
use Selpol\Http\Response;
use Selpol\Validator\Exception\ValidatorException;

class TestController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function index(): Response
    {
        $repository = container(RoleRepository::class);
        $role = $repository->findById(35);

        $role->description = 'Test';

        $repository->updateAndRefresh($role);

        return $this->rbtResponse(data: $role);
    }
}