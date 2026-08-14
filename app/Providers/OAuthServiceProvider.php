<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Passport;

/**
 * Passport, as far as the MCP server needs it: an authorization code grant and nothing else.
 */
class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Passport registers its routes when it boots, so this has to be decided before then.
        // The device code grant is for televisions and CLIs, where a code is typed into a
        // second screen; Claude does the ordinary browser redirect, so the three routes and
        // the table that grant needs would never be reached.
        Passport::$deviceCodeGrantEnabled = false;
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Passport ships no consent screen and binds no default, so `/oauth/authorize` is a
        // container error until one is named. This one is ours, in the app's own clothes.
        Passport::authorizationView('oauth.authorize');

        // Laravel MCP registers this scope for us, described as "Use MCP server" -- true, and
        // no help at all to the person being asked to grant it. Said again in their terms.
        Passport::tokensCan([
            Registrar::OAUTH_SCOPE => 'Post projects to your shelf, exactly as you would from the form.',
        ]);
    }
}
