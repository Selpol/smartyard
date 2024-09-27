<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Setting\Cms;

interface CmsInterface
{
    public function getLineDialStatus(int $apartment, bool $info): array|int;

    public function getAllLineDialStatus(int $from, int $to, bool $info): array;

    public function getCmsModel(): string;

    public function getCmsLevels(): CmsLevels;

    public function setCmsModel(string $cms): void;

    public function setCmsLevels(CmsLevels $cmsLevels): void;

    public function setCmsApartmentDeffer(CmsApartment $cmsApartment): void;

    public function defferCms(): void;

    public function clearCms(string $cms): void;
}