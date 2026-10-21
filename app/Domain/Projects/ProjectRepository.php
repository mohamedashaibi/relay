<?php

namespace App\Domain\Projects;

interface ProjectRepository
{
    public function find(int $id, int $viewerId): ?ProjectView;

    /** @return list<ProjectView> */
    public function listForUser(int $userId, bool $includeArchived): array;

    /** @return list<int> */
    public function projectIdsForUser(int $userId): array;

    public function roleOf(int $projectId, int $userId): ?ProjectRole;

    public function keyExists(string $key): bool;

    public function idForKey(string $key): ?int;

    public function create(int $ownerId, string $name, string $key, ?string $description, string $color): int;

    /** @param array{name?:string,description?:?string,color?:string,archived?:bool} $changes */
    public function update(int $id, array $changes): void;

    public function delete(int $id): void;

    /** @return list<MemberView> */
    public function members(int $projectId): array;

    public function upsertMember(int $projectId, int $userId, ProjectRole $role): void;

    public function removeMember(int $projectId, int $userId): void;
}
