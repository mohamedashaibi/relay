<?php

namespace App\Domain\Shared;

/** Where a change came from — lets the activity feed say "via Claude (MCP)". */
enum Channel: string
{
    case Web = 'web';
    case Mcp = 'mcp';
}
