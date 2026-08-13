<?php

/*
| Only the keys that differ from Sanctum's own defaults are here; the package
| merges the rest in. The ones left out (`stateful`, `middleware`) exist for
| first-party SPAs authenticating by cookie, and there is no SPA here.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | Empty, so the API is bearer tokens and nothing else. Sanctum would
    | otherwise fall back to the `web` guard and let a logged-in browser
    | session through -- and the API routes carry no CSRF token, so any
    | other site could then post a project on a signed-in user's behalf.
    |
    */

    'guard' => [],

];
