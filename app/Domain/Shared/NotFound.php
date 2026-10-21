<?php

namespace App\Domain\Shared;

final class NotFound extends DomainException
{
    public static function of(string $what, string|int $ref): self
    {
        return new self("{$what} [{$ref}] was not found or you do not have access to it.");
    }
}
