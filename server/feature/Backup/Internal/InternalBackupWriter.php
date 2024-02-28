<?php declare(strict_types=1);

namespace Selpol\Feature\Backup\Internal;

readonly class InternalBackupWriter
{
    public const SECTION = '---- ---- ---- ----';

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function section(): void
    {
        $this->write(self::SECTION);
    }

    /**
     * @param string $name
     * @param string[] $columns
     * @return void
     */
    public function table(string $name, array $columns): void
    {
        $this->section();
        $this->write('TABLE - ' . $name . ' - ' . implode(', ', $columns));
    }

    public function row(array $values): void
    {
        $this->write(json_encode($values, JSON_UNESCAPED_UNICODE));
    }

    public function sequence(string $name, int $value): void
    {
        $this->section();
        $this->write('SEQUENCE - ' . $name . ' - ' . $value);
    }

    public function write(string $value): void
    {
        file_put_contents($this->path, $value . PHP_EOL, FILE_APPEND);
    }
}