<?php

namespace Selpol\Device\Ip\Trait;

use SensitiveParameter;

trait HikVisionTrait
{
    public function setLoginPassword(#[SensitiveParameter] string $password): static
    {
        $this->put('/Security/users/1', "<User><id>1</id><userName>$this->login</userName><password>$password</password><userLevel>Administrator</userLevel><loginPassword>$this->password</loginPassword></User>", ['Content-Type' => 'application/xml']);

        return $this;
    }
}