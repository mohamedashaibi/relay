<?php

namespace App\Infrastructure\Auth;

use App\Application\Tokens\AccessTokenGateway;
use App\Models\User;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * Personal tokens are listed one row each. OAuth grants (Claude.ai, ChatGPT…) rotate access tokens
 * on every refresh, so they're grouped into one row per client.
 */
final class PassportTokenGateway implements AccessTokenGateway
{
    public const MCP_SCOPE = 'mcp:use';

    public function listActive(int $userId): array
    {
        $tokens = Token::with('client')
            ->where('user_id', $userId)
            ->where('revoked', false)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->get();

        [$personal, $oauth] = $tokens->partition(fn (Token $t) => $t->client?->hasGrantType('personal_access'));

        $rows = $personal->map(fn (Token $t) => [
            'id' => 'pat:'.$t->id,
            'name' => $t->name ?: 'Personal token',
            'kind' => 'personal',
            'created_at' => $t->created_at->toIso8601String(),
            'expires_at' => $t->expires_at?->toIso8601String(),
        ])->values();

        $grants = $oauth->groupBy('client_id')->map(fn ($group) => [
            'id' => 'oauth:'.$group->first()->client_id,
            'name' => $group->first()->client?->name ?? 'OAuth app',
            'kind' => 'oauth',
            'created_at' => $group->min('created_at')->toIso8601String(),
            'expires_at' => $group->max('expires_at')?->toIso8601String(),
        ])->values();

        return $rows->concat($grants)->sortByDesc('created_at')->values()->all();
    }

    public function issuePersonal(int $userId, string $name): array
    {
        $result = User::findOrFail($userId)->createToken($name, [self::MCP_SCOPE]);
        $token = $result->getToken();

        return [
            'id' => 'pat:'.$token->id,
            'name' => $name,
            'token' => $result->accessToken,
            'expires_at' => $token->expires_at?->toIso8601String(),
        ];
    }

    public function revoke(int $userId, string $tokenId): bool
    {
        [$kind, $id] = array_pad(explode(':', $tokenId, 2), 2, '');

        $query = Token::where('user_id', $userId)->where('revoked', false);
        $query = match ($kind) {
            'pat' => $query->whereKey($id),
            'oauth' => $query->where('client_id', $id),
            default => null,
        };

        if ($query === null) {
            return false;
        }

        $ids = $query->pluck('id');
        if ($ids->isEmpty()) {
            return false;
        }

        Token::whereIn('id', $ids)->update(['revoked' => true]);
        RefreshToken::whereIn('access_token_id', $ids)->update(['revoked' => true]);

        return true;
    }
}
