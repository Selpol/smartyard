<?php declare(strict_types=1);

namespace Selpol\Feature\Streamer;

class ApiStream
{
    public string $id;

    public string $input;

    public string $input_type;
    public string $output_type;

    public string|null $created_at;
    public string|null $updated_at;

    public function __construct(string $id, string $input, string $input_type, string $output_type, string|null $created_at, string|null $updated_at)
    {
        $this->id = $id;

        $this->input = $input;

        $this->input_type = $input_type;
        $this->output_type = $output_type;

        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }
}