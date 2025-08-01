<?php

declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Code\Code;

trait CodeTrait
{
    public function getCodes(?int $apartment): array
    {
        $response = $this->get(is_null($apartment) ? '/openCode' : ('/openCode/' . $apartment));

        if (!is_array($response)) {
            return [];
        }

        $result = array_map(static fn(array $code): Code => new Code($code['code'], $code['panelCode']), $response);

        usort($result, static fn(Code $a, Code $b): bool => $a->code > $b->code);

        return $result;
    }

    public function addCode(Code $code): void
    {
        $this->post('/openCode', ['code' => $code->code, 'panelCode' => $code->apartment]);
    }

    public function removeCode(Code $code): void
    {
        if ($code->code) {
            $this->delete('/openCode/' . $code->apartment . '/' . $code->code);
        } else {
            $this->delete('/openCode/' . $code->apartment);
        }
    }

    public function clearCode(): void
    {
        $this->delete('/openCode/clear');
    }
}
