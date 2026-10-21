<?php

namespace App\Application\Tokens;

use App\Domain\Shared\Actor;
use App\Domain\Shared\NotFound;

final class RevokeAccessTokenUseCase
{
    public function __construct(private readonly AccessTokenGateway $tokens) {}

    public function execute(Actor $actor, string $tokenId): void
    {
        if (! $this->tokens->revoke($actor->userId, $tokenId)) {
            throw NotFound::of('Connection', $tokenId);
        }
    }
}
