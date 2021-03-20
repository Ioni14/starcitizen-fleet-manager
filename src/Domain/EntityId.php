<?php

namespace App\Domain;

use Symfony\Component\Uid\Ulid;

abstract class EntityId
{
    public function __construct(
        private Ulid $ulid
    ) {
    }

    public function getId(): Ulid
    {
        return $this->ulid;
    }

    public function __toString(): string
    {
        return (string)$this->ulid;
    }
}