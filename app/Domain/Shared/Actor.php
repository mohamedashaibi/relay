<?php

namespace App\Domain\Shared;

final readonly class Actor
{
    public function __construct(
        public int $userId,
        public Channel $channel = Channel::Web,
        public ?string $client = null,
    ) {}

    public static function web(int $userId): self
    {
        return new self($userId);
    }

    public static function agent(int $userId, ?string $client): self
    {
        return new self($userId, Channel::Mcp, $client);
    }
}
