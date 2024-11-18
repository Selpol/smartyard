<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\KeyIndexRequest;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\DatabaseService;

/**
 * Ключи
 */
#[Controller('/admin/key')]
readonly class KeyController extends AdminRbtController
{
    /**
     * Получить список ключей
     */
    #[Get]
    public function index(KeyIndexRequest $request): ResponseInterface
    {
        $page = HouseKey::fetchPage($request->page, $request->size, criteria()->like('rfid', $request->rfid)->orLike('comments', $request->comments)->asc('house_rfid_id')->asc('access_to'));
        $data = $page->getData();

        $flats = [];
        $houses = [];

        $db = container(DatabaseService::class);

        foreach ($data as $key) {
            if ($key->access_type === 2) {
                if (!array_key_exists($key->access_to, $flats)) {
                    $flats[$key->access_to] = $db->get('SELECT address_house_id, flat FROM houses_flats WHERE house_flat_id = :house_flat_id', ['house_flat_id' => $key->access_to], options: ['singlify']);
                }

                if (!array_key_exists($flats[$key->access_to]['address_house_id'], $houses)) {
                    $houses[$flats[$key->access_to]['address_house_id']] = $db->get('SELECT house_full FROM addresses_houses WHERE address_house_id = :address_house_id', ['address_house_id' => $flats[$key->access_to]['address_house_id']], options: ['singlify'])['house_full'];
                }

                $key->flat = $flats[$key->access_to]['flat'];

                $key->house_id = $flats[$key->access_to]['address_house_id'];
                $key->house_address = $houses[$flats[$key->access_to]['address_house_id']];
            }
        }

        return self::success(new EntityPage($data, $page->getTotal(), $page->getPage(), $page->getSize()));
    }
}