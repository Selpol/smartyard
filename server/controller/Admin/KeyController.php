<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Key\KeyIndexRequest;
use Selpol\Controller\Request\Admin\Key\KeyStoreRequest;
use Selpol\Controller\Request\Admin\Key\KeyUpdateRequest;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\DatabaseService;
use Selpol\Task\Tasks\Intercom\Key\IntercomAddKeyTask;

/**
 * Ключ
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

    /**
     * Получить ключ
     * 
     * @param int $id Идентификатор ключа 
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $key = HouseKey::findById($id);

        if (!$key) {
            return self::error('Не удалось найти ключ', 404);
        }

        return self::success($key);
    }

    /**
     * Добавить ключ
     */
    #[Post]
    public function store(KeyStoreRequest $request): ResponseInterface
    {
        $key = HouseKey::fetch(criteria()->equal('rfid', $request->rfid)->equal('access_type', $request->access_type)->equal('access_to', $request->access_to));

        if ($key instanceof HouseKey) {
            task(new IntercomAddKeyTask($key->rfid, $key->access_to))->sync();

            return self::success($key->house_rfid_id);
        }

        $key = new HouseKey();

        $key->rfid = $request->rfid;

        $key->access_type = $request->access_type;
        $key->access_to = $request->access_to;

        $key->comments = $request->comments;

        $key->insert();

        task(new IntercomAddKeyTask($key->rfid, $key->access_to))->sync();

        return self::success($key->house_rfid_id);
    }

    /**
     * Обновить ключ
     * 
     * @param int $id Идентификатор ключа 
     */
    #[Put('/{id}')]
    public function update(KeyUpdateRequest $request): ResponseInterface
    {
        $key = HouseKey::findById($request->id);

        if (!$key) {
            return self::error('Не удалось найти ключ', 404);
        }

        $key->comments = $request->comments;

        $key->update();

        return self::success();
    }

    /**
     * Удалить ключ
     * 
     * @param int $id Идентификатор ключа
     */
    #[Delete('/{id}')]
    public function delete(int $id)
    {
        $key = HouseKey::findById($id);

        if (!$key) {
            return self::error('Не удалось найти ключ', 404);
        }

        $key->delete();

        return self::success();
    }
}
