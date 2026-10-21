<?php

namespace App\Application\Projects\Dto;

final readonly class UpdateProjectInput
{
    /** @param array{name?:string,description?:?string,color?:string,archived?:bool} $changes */
    private function __construct(public array $changes) {}

    /** @param array<string,mixed> $data already-validated payload */
    public static function fromArray(array $data): self
    {
        $changes = array_intersect_key($data, array_flip(['name', 'description', 'color', 'archived']));

        if (isset($changes['name'])) {
            $changes['name'] = trim($changes['name']);
        }
        if (array_key_exists('archived', $changes)) {
            $changes['archived'] = (bool) $changes['archived'];
        }

        return new self($changes);
    }
}
