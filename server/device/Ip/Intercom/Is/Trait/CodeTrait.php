<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Code\Code;

trait CodeTrait
{
    public function getCodes(int $apartment): array
    {
        $response = $this->get('/openCode/' . $apartment);
        $result = array_map(static fn(array $code) => new Code($code['code'], $code['panelCode']), $response);

        usort($result, static fn(Code $a, Code $b) => $a->code > $b->code);

        return $result;
    }

    public function addCode(Code $code): void
    {
        $this->post('/openCode', ['code' => $code->code, 'panelCode' => $code->apartment]);
    }

    public function removeCode(Code $code): void
    {
        $this->delete('/openCode/' . $code->apartment . '/' . $code->code);
    }

    public function clearCode(): void
    {
        $this->delete('/openCode/clear');
    }
}