<?php

namespace App\Application\Tasks\Dto;

final class LabelList
{
    /** Lowercase, trimmed, unique, at most 8. */
    public static function normalize(array $labels): array
    {
        $clean = array_map(fn ($l) => mb_strtolower(trim((string) $l)), $labels);

        return array_slice(array_values(array_unique(array_filter($clean, fn ($l) => $l !== ''))), 0, 8);
    }
}
