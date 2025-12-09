<?php

namespace PakaiLink\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PakaiLink\Exceptions\PakaiLinkAuthenticationException;
use PakaiLink\Exceptions\PakaiLinkException;

class PakaiLinkHttpClient
{
    public function __construct(
        protected PakaiLinkAuthService $authService,
        protected PakaiLinkSignatureService $signatureService,
        protected string $baseUrl,
        protected string $clientId,
        protected ?string $partnerId,
        protected ?string $channelId,
        protected int $timeout,
        protected int $retryTimes,
        protected int $retryDelay,
    ) {}

    /**
     * Make authenticated API request.
     */
    public function request(
        string $method,
        string $endpoint,
        array $data = [],
        array $additionalHeaders = []
    ): array {
        $url = $this->baseUrl.$endpoint;
        // Format timestamp as per SNAP API: YYYY-MM-DDTHH:mm:ss+07:00 (Jakarta time GMT+7)
        $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $accessToken = $this->authService->getB2BAccessToken();

        // Generate X-EXTERNAL-ID (unique request identifier for idempotency)
        $externalId = $this->generateExternalId();

        // Prepare request body
        $requestBody = empty($data) ? '' : json_encode($data);

        // Generate symmetric signature
        $signature = $this->signatureService->generateSymmetricSignature(
            strtoupper($method),
            $endpoint,
            $accessToken,
            $requestBody,
            $timestamp
        );

        // Prepare headers according to SNAP API documentation
        $headers = array_merge([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json',
            'X-TIMESTAMP' => $timestamp,
            'X-PARTNER-ID' => $this->partnerId,
            'X-EXTERNAL-ID' => $externalId,
            'CHANNEL-ID' => $this->channelId,
            'X-SIGNATURE' => $signature,
        ], $additionalHeaders);

        Log::channel('pakailink')->debug('Making API request', [
            'method' => $method,
            'url' => $url,
            'headers' => array_diff_key($headers, array_flip(['Authorization', 'X-SIGNATURE'])),
        ]);

        // Make request with retry logic
        try {
            $response = $this->makeHttpRequest($method, $url, $headers, $data);

            Log::channel('pakailink')->debug('API request successful', [
                'status' => $response->status(),
            ]);

            return $this->handleResponse($response);
        } catch (PakaiLinkAuthenticationException $e) {
            // Token expired, try refreshing once
            Log::channel('pakailink')->warning('Token expired, refreshing and retrying');

            $this->authService->refreshToken();

            // Retry once with new token
            $accessToken = $this->authService->getB2BAccessToken();
            $signature = $this->signatureService->generateSymmetricSignature(
                strtoupper($method),
                $endpoint,
                $accessToken,
                $requestBody,
                $timestamp
            );

            $headers['Authorization'] = "Bearer {$accessToken}";
            $headers['X-SIGNATURE'] = $signature;

            $response = $this->makeHttpRequest($method, $url, $headers, $data);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('API request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * GET request.
     */
    public function get(string $endpoint, array $query = []): array
    {
        $queryString = ! empty($query) ? '?'.http_build_query($query) : '';

        return $this->request('GET', $endpoint.$queryString);
    }

    /**
     * POST request.
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * PUT request.
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * DELETE request.
     */
    public function delete(string $endpoint, array $data = []): array
    {
        return $this->request('DELETE', $endpoint, $data);
    }

    /**
     * Make HTTP request with retry logic.
     */
    protected function makeHttpRequest(
        string $method,
        string $url,
        array $headers,
        array $data
    ): Response {
        return Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->retry(
                times: $this->retryTimes,
                sleepMilliseconds: $this->retryDelay,
                when: function ($exception) {
                    // Retry on network errors, timeouts, and 5xx errors
                    if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        $status = $exception->response->status();

                        return $status >= 500 || $status === 429;
                    }

                    return false;
                }
            )
            ->send($method, $url, [
                'json' => $data,
            ]);
    }

    /**
     * Handle API response.
     */
    protected function handleResponse(Response $response): array
    {
        $statusCode = $response->status();
        $body = $response->json();

        // Log response
        Log::channel('pakailink')->debug('API response received', [
            'status' => $statusCode,
            'response' => $body,
        ]);

        // Handle successful responses (2xx)
        if ($response->successful()) {
            return $body;
        }

        // Handle error responses
        $errorMessage = $body['responseMessage'] ?? $body['message'] ?? 'Unknown error';
        $errorCode = $body['responseCode'] ?? $body['code'] ?? 'UNKNOWN';

        Log::channel('pakailink')->error('API error response', [
            'status' => $statusCode,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);

        // Handle specific error codes
        if ($statusCode === 401) {
            throw new PakaiLinkAuthenticationException(
                "Authentication failed: {$errorMessage}",
                $statusCode
            );
        }

        throw new PakaiLinkException(
            "PakaiLink API error [{$errorCode}]: {$errorMessage}",
            $statusCode
        );
    }

    /**
     * Generate unique external ID for request tracking and idempotency.
     * Must be numeric string and unique per day as per SNAP API documentation.
     */
    protected function generateExternalId(): string
    {
        return (string) (int) (now()->timestamp * 1000 + rand(0, 999));
    }
}
