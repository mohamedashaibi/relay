<?php

namespace App\Application\Tokens;

use App\Domain\Shared\Actor;

final class ListConnectionsUseCase
{
    public function __construct(private readonly AccessTokenGateway $tokens) {}

    public function execute(Actor $actor): array
    {
        return $this->tokens->listActive($actor->userId);
    }
}
