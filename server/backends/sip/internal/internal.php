<?php

/**
 * backends sip namespace
 */

namespace backends\sip {
    class internal extends sip
    {
        public function server(string $by, string|int|null $query = null): array
        {
            return match ($by) {
                "all" => $this->config["backends"]["sip"]["servers"],
                default => $this->config["backends"]["sip"]["servers"][0],
            };
        }

        public function stun(string|int $extension): bool|string
        {
            if (@$this->config["backends"]["sip"]["stuns"]) {
                return $this->config["backends"]["sip"]["stuns"][rand(0, count($this->config["backends"]["sip"]["stuns"]) - 1)];
            } else {
                return false;
            }
        }
    }
}
