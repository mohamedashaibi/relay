<?php

namespace App\Application\Tokens;

/**
 * Credentials an AI client uses to reach the MCP server: personal tokens the user mints,
 * plus OAuth grants created when Claude.ai / ChatGPT connect.
 */
interface AccessTokenGateway
{
    /** @return list<array{id:string,name:string,kind:'personal'|'oauth',last_used_at:?string,created_at:string,expires_at:?string}> */
    public function listActive(int $userId): array;

    /** @return array{id:string,name:string,token:string,expires_at:?string} the plain token is only ever returned here */
    public function issuePersonal(int $userId, string $name): array;

    public function revoke(int $userId, string $tokenId): bool;
}
