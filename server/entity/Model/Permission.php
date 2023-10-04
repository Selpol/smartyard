<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $id
 *
 * @property string $title
 * @property string $description
 *
 * @property string $created_at
 * @property string $updated_at
 */
class Permission extends Entity
{
    public static string $table = 'permission';

    public static string $columnIdStrategy = 'permission_id_seq';

    public static ?string $columnCreate = 'created_at';
    public static ?string $columnUpdate = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'title' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'description' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'created_at' => [Rule::length(32)],
            'updated_at' => [Rule::length(32)]
        ];
    }

//    public function update(): bool
//    {
//        $result = parent::update();
//
//        if ($result)
//            container(AuditFeature::class)->audit(strval($this->id), Permission::class, 'update', 'Обновление прав');
//
//        return $result;
//    }
}