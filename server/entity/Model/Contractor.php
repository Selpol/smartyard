<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Repository\ContractorRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title Название подрядчика
 *
 * @property int $flat Сервисная квартира подрядчика
 * @property int $flat_flag Флаги для квартиры
 *
 * @property string|null $code Код сервисной квартиры
 *
 * @property string $created_at
 * @property string $updated_at
 */
class Contractor extends Entity
{
    public const FLAG_INTERCOM_BLOCK = 1;
    public const FLAG_INTERCOM_CAMERA = 2;

    /**
     * @use RepositoryTrait<ContractorRepository>
     */
    use RepositoryTrait;

    public static string $table = 'contractor';

    public static ?string $columnCreateAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    public function isFlatFlag(int $flag): bool
    {
        return ($this->flat_flag & $flag) == $flag;
    }

    public function isFlatFlagIntercomBlock(): bool
    {
        return $this->isFlatFlag(self::FLAG_INTERCOM_BLOCK);
    }

    public function isFlatFlagIntercomCamera(): bool
    {
        return $this->isFlatFlag(self::FLAG_INTERCOM_CAMERA);
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->max(1000)->nonNullable(),

            'flat' => rule()->required()->int()->clamp(0, 10000)->nonNullable(),
            'flat_flag' => rule()->int()->min()->nonNullable(),

            'code' => rule()->string(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}