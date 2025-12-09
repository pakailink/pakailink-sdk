<?php

use Illuminate\Support\Facades\Cache;
use PakaiLink\Exceptions\PakaiLinkAuthenticationException;
use PakaiLink\Services\PakaiLinkAuthService;

beforeEach(function () {
    // Clear token cache before each test
    Cache::forget(config('pakailink.cache.token_key'));
});

test('can generate B2B access token from sandbox', function () {
    $authService = app(PakaiLinkAuthService::class);

    $token = $authService->getAccessToken();

    expect($token)
        ->toBeString()
        ->not->toBeEmpty();

    // Verify token format (should be a valid token string)
    expect(strlen($token))->toBeGreaterThan(20);

    $this->info('✓ B2B Access Token generated: '.substr($token, 0, 50).'...');
})->group('integration', 'auth', 'sandbox');

test('token is cached and reused', function () {
    $authService = app(PakaiLinkAuthService::class);

    // First call - should hit API
    $token1 = $authService->getAccessToken();

    // Second call - should use cache
    $token2 = $authService->getAccessToken();

    expect($token1)->toBe($token2);

    // Verify token is in cache
    $cachedToken = Cache::get(config('pakailink.cache.token_key'));
    expect($cachedToken)->toBe($token1);

    $this->info('✓ Token cached successfully');
})->group('integration', 'auth', 'sandbox');

test('can get token info', function () {
    $authService = app(PakaiLinkAuthService::class);

    $token = $authService->getAccessToken();
    $info = $authService->getTokenInfo();

    expect($info)
        ->toBeArray()
        ->toHaveKeys(['token', 'cached', 'expires_in', 'generated_at']);

    expect($info['token'])->toBe($token);
    expect($info['cached'])->toBeTrue();

    $this->info('✓ Token Info:');
    $this->info('  - Cached: '.($info['cached'] ? 'Yes' : 'No'));
    $this->info('  - Expires in: '.$info['expires_in'].' seconds');
})->group('integration', 'auth', 'sandbox');

test('token has valid expiry time', function () {
    $authService = app(PakaiLinkAuthService::class);

    $token = $authService->getAccessToken();

    expect($authService->isTokenExpired())->toBeFalse();

    $this->info('✓ Token is not expired');
})->group('integration', 'auth', 'sandbox');

test('can refresh expired token', function () {
    $authService = app(PakaiLinkAuthService::class);

    // Get first token
    $token1 = $authService->getAccessToken();

    // Clear cache to simulate expiry
    Cache::forget(config('pakailink.cache.token_key'));

    // Get new token
    $token2 = $authService->getAccessToken();

    expect($token2)
        ->toBeString()
        ->not->toBeEmpty();

    $this->info('✓ Token refreshed successfully');
})->group('integration', 'auth', 'sandbox');

test('authentication fails with invalid credentials', function () {
    // Create auth service with invalid credentials
    $authService = new PakaiLinkAuthService(
        signatureService: app(\PakaiLink\Services\PakaiLinkSignatureService::class),
        baseUrl: config('pakailink.base_url'),
        clientId: 'invalid-client-id',
        clientSecret: 'invalid-secret',
    );

    $authService->getAccessToken();
})->throws(PakaiLinkAuthenticationException::class)->group('integration', 'auth', 'sandbox');

test('can authenticate multiple times without errors', function () {
    $authService = app(PakaiLinkAuthService::class);

    // Get token 5 times
    for ($i = 0; $i < 5; $i++) {
        $token = $authService->getAccessToken();
        expect($token)->toBeString()->not->toBeEmpty();
    }

    $this->info('✓ Multiple authentication calls successful');
})->group('integration', 'auth', 'sandbox');
