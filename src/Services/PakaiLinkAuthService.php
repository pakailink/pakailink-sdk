<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PakaiLink\Exceptions\PakaiLinkAuthenticationException;

class PakaiLinkAuthService
{
    protected string $tokenCacheKey;

    public function __construct(
        protected PakaiLinkSignatureService $signatureService,
        protected string $baseUrl,
        protected string $clientId,
        protected string $clientSecret,
    ) {
        $this->tokenCacheKey = config('pakailink.cache.token_key');
    }

    /**
     * Get B2B access token (with caching).
     */
    public function getB2BAccessToken(): string
    {
        // Check cache first
        $cachedToken = Cache::get($this->tokenCacheKey);

        if ($cachedToken) {
            Log::channel('pakailink')->debug('Using cached B2B access token');

            return $cachedToken;
        }

        // Generate new token
        Log::channel('pakailink')->debug('Generating new B2B access token');

        return $this->generateB2BAccessToken();
    }

    /**
     * Generate new B2B access token.
     */
    protected function generateB2BAccessToken(): string
    {
        $timestamp = now()->toIso8601String();

        // Generate asymmetric signature
        $signature = $this->signatureService->generateAsymmetricSignature(
            $this->clientId,
            $timestamp
        );

        $endpoint = config('pakailink.endpoints.auth.b2b_token', '/snap/v1.0/access-token/b2b');
        $url = $this->baseUrl.$endpoint;

        // Prepare request
        $requestBody = [
            'grantType' => 'client_credentials',
        ];

        Log::channel('pakailink')->info('Requesting B2B access token', [
            'url' => $url,
            'client_id' => $this->clientId,
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'X-CLIENT-KEY' => $this->clientId,
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
        ];

        // Make request
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post($url, $requestBody);

        if (! $response->successful()) {
            Log::channel('pakailink')->error('Failed to obtain B2B access token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PakaiLinkAuthenticationException(
                "Failed to obtain B2B access token: {$response->body()}",
                $response->status()
            );
        }

        $data = $response->json();

        if (! isset($data['accessToken'])) {
            Log::channel('pakailink')->error('Invalid token response', [
                'response' => $data,
            ]);

            throw new PakaiLinkAuthenticationException('Invalid token response: missing accessToken');
        }

        $accessToken = $data['accessToken'];
        $expiresIn = $data['expiresIn'] ?? 900; // Default 15 minutes

        Log::channel('pakailink')->info('B2B access token obtained successfully', [
            'expires_in' => $expiresIn,
        ]);

        // Cache token (cache for 14 minutes to ensure fresh tokens)
        $cacheTtl = config('pakailink.cache.token_ttl', 840);
        Cache::put($this->tokenCacheKey, $accessToken, $cacheTtl);

        return $accessToken;
    }

    /**
     * Refresh token (clear cache and get new token).
     */
    public function refreshToken(): string
    {
        Log::channel('pakailink')->info('Refreshing B2B access token');

        Cache::forget($this->tokenCacheKey);

        return $this->generateB2BAccessToken();
    }

    /**
     * Check if cached token is expired or about to expire.
     */
    public function isTokenExpired(): bool
    {
        return ! Cache::has($this->tokenCacheKey);
    }

    /**
     * Clear cached token.
     */
    public function clearToken(): void
    {
        Log::channel('pakailink')->debug('Clearing cached token');
        Cache::forget($this->tokenCacheKey);
    }

    /**
     * Get current token info (for debugging).
     */
    public function getTokenInfo(): array
    {
        $hasToken = Cache::has($this->tokenCacheKey);
        $token = $hasToken ? Cache::get($this->tokenCacheKey) : null;

        return [
            'has_token' => $hasToken,
            'token_preview' => $token ? substr($token, 0, 20).'...' : null,
            'cache_key' => $this->tokenCacheKey,
        ];
    }
}
