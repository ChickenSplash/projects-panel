<?php

use App\Mcp\Servers\ProjectsServer;
use Laravel\Mcp\Facades\Mcp;

// The `/.well-known/oauth-*` discovery documents, plus `POST /oauth/register` so a client can
// register itself. Claude reads the discovery documents before it will start an OAuth flow at
// all, so a connector that never gets past "Disconnected" usually means these are not answering.
Mcp::oauthRoutes();

// This file is loaded outside the `web` and `api` groups, so the only middleware on the
// endpoint is what is named here: no session, no CSRF token, an OAuth bearer token or nothing.
Mcp::web('/mcp/projects', ProjectsServer::class)
    ->middleware(['auth:api', 'throttle:60,1']);
