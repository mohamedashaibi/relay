<?php

namespace App\Application\Tokens;

use App\Domain\Shared\Actor;
use App\Domain\Shared\RuleViolation;

final class IssueAccessTokenUseCase
{
    private const MAX_ACTIVE = 20;

    public function __construct(private readonly AccessTokenGateway $tokens) {}

    public function execute(Actor $actor, string $name): array
    {
        if (count($this->tokens->listActive($actor->userId)) >= self::MAX_ACTIVE) {
            throw new RuleViolation('You have too many active connections. Revoke one you no longer use.');
        }

        return $this->tokens->issuePersonal($actor->userId, trim($name));
    }
}
