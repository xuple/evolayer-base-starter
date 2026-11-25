<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Reverse proxies whose X-Forwarded-* headers this application may trust,
    | so that URLs, the request scheme, and the client IP are correct behind
    | Nginx or a cloud load balancer. Illuminate\Http\Middleware\TrustProxies
    | reads this key on every request, which keeps the setting working under
    | `config:cache` (where env() is unavailable in bootstrap/app.php).
    |
    | null (TRUSTED_PROXIES blank) trusts no proxy — with one framework-level
    | exception: on Laravel Cloud, Forge and Vapor hosts the middleware still
    | auto-trusts the platform proxy, exactly as it does for any Laravel app.
    | Any explicit value overrides that. "*" trusts whichever proxy connects to
    | the app — only safe behind a proxy you control. Otherwise give a
    | comma-separated list of IP addresses and/or CIDR ranges.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
