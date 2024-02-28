<?php declare(strict_types=1);

namespace Selpol\Feature\Backup\Internal;

readonly class InternalBackupWriter
{
    private const SECTION = '---- ---- ---- ----';

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param string $name
     * @param string[] $columns
     * @return void
     */
    public function table(string $name, array $columns): void
    {
        $this->write(self::SECTION);
        $this->write('TABLE - ' . $name . ' - ' . implode(', ', $columns));
    }

    public function row(array $values): void
    {
        $this->write(json_encode($values));
    }

    public function sequence(string $name, int $value): void
    {
        $this->write(self::SECTION);
        $this->write('SEQUENCE - ' . $name . ' - ' . $value);
    }

    public function write(string $value): void
    {
        file_put_contents($this->path, $value . PHP_EOL, FILE_APPEND);
    }
}