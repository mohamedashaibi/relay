<?php

use App\Mcp\Servers\RelayServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth discovery + dynamic client registration, so Claude.ai and ChatGPT can connect with a consent screen.
Mcp::oauthRoutes();

// Accepts OAuth access tokens and personal tokens (both issued by Passport).
Mcp::web('/mcp', RelayServer::class)->middleware(['auth:api', 'throttle:mcp']);
