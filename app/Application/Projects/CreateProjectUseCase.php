<?php

namespace App\Application\Projects;

use App\Application\Projects\Dto\CreateProjectInput;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectRole;
use App\Domain\Projects\ProjectView;
use App\Domain\Shared\Actor;
use App\Domain\Shared\RuleViolation;

final class CreateProjectUseCase
{
    private const PALETTE = ['#0F766E', '#C2410C', '#4338CA', '#B91C1C', '#0369A1', '#7E22CE', '#15803D', '#A16207'];

    public function __construct(private readonly ProjectRepository $projects) {}

    public function execute(Actor $actor, CreateProjectInput $input): ProjectView
    {
        if ($input->key !== null && $this->projects->keyExists($input->key)) {
            throw new RuleViolation("The key {$input->key} is already used by another project.");
        }

        $key = $input->key ?? $this->deriveKey($input->name);
        $color = $input->color ?? self::PALETTE[crc32($key) % count(self::PALETTE)];

        $id = $this->projects->create($actor->userId, $input->name, $key, $input->description, $color);
        $this->projects->upsertMember($id, $actor->userId, ProjectRole::Owner);

        return $this->projects->find($id, $actor->userId);
    }

    /** "Mobile App Relaunch" → "MAR"; single words take their first three letters; collisions get a digit. */
    private function deriveKey(string $name): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: ['PRJ'];
        $base = count($words) > 1
            ? implode('', array_map(fn ($w) => $w[0], array_slice($words, 0, 4)))
            : substr($words[0], 0, 3);
        $base = strtoupper(preg_match('/^[A-Za-z]/', $base) ? $base : 'P'.$base);
        $base = str_pad(substr($base, 0, 5), 2, 'X');

        $key = $base;
        for ($i = 2; $this->projects->keyExists($key); $i++) {
            $key = substr($base, 0, 4).$i;
        }

        return $key;
    }
}
