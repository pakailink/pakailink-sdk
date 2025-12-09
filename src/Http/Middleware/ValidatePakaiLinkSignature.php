<?php

namespace PakaiLink\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PakaiLink\Services\PakaiLinkSignatureService;
use Symfony\Component\HttpFoundation\Response;

class ValidatePakaiLinkSignature
{
    public function __construct(
        private PakaiLinkSignatureService $signatureService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-SIGNATURE');
        $timestamp = $request->header('X-TIMESTAMP');

        if (! $signature || ! $timestamp) {
            Log::channel('pakailink')->warning('Missing callback signature headers', [
                'url' => $request->fullUrl(),
                'has_signature' => ! empty($signature),
                'has_timestamp' => ! empty($timestamp),
            ]);

            return response()->json([
                'responseCode' => '4010000',
                'responseMessage' => 'Invalid signature or timestamp',
            ], 401);
        }

        $requestBody = $request->getContent();

        if (! $this->signatureService->validateCallbackSignature($signature, $requestBody, $timestamp)) {
            Log::channel('pakailink')->error('Invalid callback signature', [
                'url' => $request->fullUrl(),
                'signature' => $signature,
                'timestamp' => $timestamp,
            ]);

            return response()->json([
                'responseCode' => '4010001',
                'responseMessage' => 'Signature verification failed',
            ], 401);
        }

        $request->merge([
            '_pakailink_signature_valid' => true,
            '_pakailink_timestamp' => $timestamp,
        ]);

        return $next($request);
    }
}
