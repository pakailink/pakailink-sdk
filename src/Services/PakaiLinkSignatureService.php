<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;
use PakaiLink\Exceptions\PakaiLinkSignatureException;

class PakaiLinkSignatureService
{
    public function __construct(
        protected string $privateKeyPath,
        protected string $clientSecret,
    ) {}

    /**
     * Generate asymmetric signature for B2B token request.
     *
     * Used for: /snap/v1.0/access-token/b2b
     */
    public function generateAsymmetricSignature(string $clientId, string $timestamp): string
    {
        Log::channel('pakailink')->debug('Generating asymmetric signature', [
            'client_id' => $clientId,
            'timestamp' => $timestamp,
        ]);

        // String to sign: ClientID|Timestamp
        $stringToSign = "{$clientId}|{$timestamp}";

        Log::channel('pakailink')->debug('String to sign', [
            'string' => $stringToSign,
        ]);

        // Load private key
        if (! file_exists($this->privateKeyPath)) {
            throw new PakaiLinkSignatureException("Private key file not found: {$this->privateKeyPath}");
        }

        $privateKeyContent = file_get_contents($this->privateKeyPath);
        $privateKey = openssl_pkey_get_private($privateKeyContent);

        if (! $privateKey) {
            $error = openssl_error_string();
            Log::channel('pakailink')->error('Failed to load private key', [
                'error' => $error,
            ]);
            throw new PakaiLinkSignatureException("Failed to load private key: {$error}");
        }

        // Sign the string
        $signSuccess = openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $signSuccess) {
            $error = openssl_error_string();
            Log::channel('pakailink')->error('Failed to generate signature', [
                'error' => $error,
            ]);
            throw new PakaiLinkSignatureException("Failed to generate signature: {$error}");
        }

        // Encode to base64
        $encodedSignature = base64_encode($signature);

        Log::channel('pakailink')->debug('Asymmetric signature generated successfully');

        return $encodedSignature;
    }

    /**
     * Generate symmetric signature for API requests.
     *
     * Used for: Most other API endpoints after obtaining token
     */
    public function generateSymmetricSignature(
        string $httpMethod,
        string $endpointUrl,
        string $accessToken,
        string $requestBody,
        string $timestamp
    ): string {
        Log::channel('pakailink')->debug('Generating symmetric signature', [
            'http_method' => $httpMethod,
            'endpoint_url' => $endpointUrl,
            'timestamp' => $timestamp,
        ]);

        // Minify JSON request body (remove whitespace)
        if (! empty($requestBody)) {
            $decoded = json_decode($requestBody);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new PakaiLinkSignatureException('Invalid JSON in request body');
            }
            $minifiedBody = json_encode($decoded);
        } else {
            $minifiedBody = '';
        }

        // Calculate SHA-256 hash of request body
        $bodyHash = strtolower(hash('sha256', $minifiedBody));

        // String to sign: HTTPMethod:EndpointUrl:AccessToken:BodyHash:Timestamp
        $stringToSign = "{$httpMethod}:{$endpointUrl}:{$accessToken}:{$bodyHash}:{$timestamp}";

        Log::channel('pakailink')->debug('String to sign', [
            'string' => $stringToSign,
            'body_hash' => $bodyHash,
        ]);

        // Generate HMAC-SHA512 signature
        $signature = hash_hmac('sha512', $stringToSign, $this->clientSecret, true);

        // Encode to base64
        $encodedSignature = base64_encode($signature);

        Log::channel('pakailink')->debug('Symmetric signature generated successfully');

        return $encodedSignature;
    }

    /**
     * Validate callback signature from PakaiLink.
     *
     * Used for: Validating incoming callbacks
     */
    /**
     * Generate callback signature (for testing purposes).
     *
     * Used by: PakaiLink when sending callbacks to our system
     */
    public function generateCallbackSignature(
        string $requestBody,
        string $timestamp
    ): string {
        // String to sign: RequestBody + Timestamp
        $stringToSign = $requestBody.$timestamp;

        // Generate signature using HMAC-SHA512
        return base64_encode(
            hash_hmac('sha512', $stringToSign, $this->clientSecret, true)
        );
    }

    /**
     * Validate callback signature.
     *
     * Used by: Our system when receiving callbacks from PakaiLink
     *
     * @param  string  $receivedSignature  The signature from X-SIGNATURE header
     * @param  string  $requestBody  The raw request body (JSON string)
     * @param  string  $timestamp  The timestamp from X-TIMESTAMP header
     */
    public function validateCallbackSignature(
        string $receivedSignature,
        string $requestBody,
        string $timestamp
    ): bool {
        Log::channel('pakailink')->debug('Validating callback signature', [
            'timestamp' => $timestamp,
        ]);

        try {
            $expectedSignature = $this->generateCallbackSignature($requestBody, $timestamp);

            // Compare signatures using timing-safe comparison
            $isValid = hash_equals($expectedSignature, $receivedSignature);

            Log::channel('pakailink')->debug('Callback signature validation result', [
                'is_valid' => $isValid,
            ]);

            return $isValid;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to validate callback signature', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate client signature for B2B token request header.
     *
     * This is the same as asymmetric signature but formatted differently.
     */
    public function generateClientSignature(string $clientId, string $timestamp): string
    {
        return $this->generateAsymmetricSignature($clientId, $timestamp);
    }
}
