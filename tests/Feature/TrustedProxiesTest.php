<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    Route::get('/_trusted-proxies-probe', fn (Request $request) => response()->json([
        'secure' => $request->isSecure(),
        'ip' => $request->ip(),
    ]));
});

/**
 * Hit the probe route from REMOTE_ADDR 127.0.0.1 with forwarded headers that
 * claim an https client at 203.0.113.9, under the given trusted-proxy value.
 */
function trustedProxiesProbe(?string $proxies, string $host = 'localhost'): TestResponse
{
    config(['trustedproxy.proxies' => $proxies]);

    // Absolute http:// URL: a relative URI is resolved with url(), which reuses
    // the previous request's scheme — https once a trusted X-Forwarded-Proto
    // has been seen — and would make the next probe secure for the wrong reason.
    return test()
        ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '203.0.113.9',
        ])
        ->getJson("http://{$host}/_trusted-proxies-probe");
}

it('ignores forwarded headers when no proxy is trusted', function (): void {
    trustedProxiesProbe(null)
        ->assertOk()
        ->assertJson(['secure' => false, 'ip' => '127.0.0.1']);
});

it('trusts forwarded headers from any upstream when set to a wildcard', function (): void {
    trustedProxiesProbe('*')
        ->assertOk()
        ->assertJson(['secure' => true, 'ip' => '203.0.113.9']);
});

it('trusts forwarded headers only from the configured proxies', function (): void {
    trustedProxiesProbe('127.0.0.1')
        ->assertOk()
        ->assertJson(['secure' => true, 'ip' => '203.0.113.9']);

    trustedProxiesProbe('10.0.0.0/8')
        ->assertOk()
        ->assertJson(['secure' => false, 'ip' => '127.0.0.1']);
});

it('accepts a comma-separated list with surrounding whitespace', function (): void {
    trustedProxiesProbe(' 10.0.0.1 , 127.0.0.1 ')
        ->assertOk()
        ->assertJson(['secure' => true, 'ip' => '203.0.113.9']);
});

it('keeps the framework auto-trust on managed hosts when nothing is configured', function (): void {
    // A null value is the framework default, which still trusts the platform
    // proxy on Laravel Cloud, Forge and Vapor hosts. The docs say so; this
    // pins it so "blank" is never mistaken for "off everywhere".
    trustedProxiesProbe(null, 'app.on-forge.com')
        ->assertOk()
        ->assertJson(['secure' => true, 'ip' => '203.0.113.9']);
});

it('lets an explicit value override the managed-host auto-trust', function (): void {
    trustedProxiesProbe('10.0.0.0/8', 'app.on-forge.com')
        ->assertOk()
        ->assertJson(['secure' => false, 'ip' => '127.0.0.1']);
});

it('ships off by default and is read through config, not env() in bootstrap', function (): void {
    // A blank TRUSTED_PROXIES must normalise to null so the framework default
    // (trust nothing, except on hosts it recognises) is preserved exactly.
    expect(config('trustedproxy.proxies'))->toBeNull();

    expect(file_get_contents(base_path('.env.example')))->toContain("\nTRUSTED_PROXIES=\n");
    expect(file_get_contents(base_path('config/trustedproxy.php')))->toContain("env('TRUSTED_PROXIES')");

    // Passing env() to $middleware->trustProxies() in bootstrap/app.php runs
    // before .env is loaded and silently trusts nothing; keep it out of there.
    expect(file_get_contents(base_path('bootstrap/app.php')))
        ->not->toContain('TRUSTED_PROXIES')
        ->not->toContain('trustProxies(');
});
