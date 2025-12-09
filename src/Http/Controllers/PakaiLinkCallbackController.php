<?php

namespace PakaiLink\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use PakaiLink\Exceptions\PakaiLinkException;
use PakaiLink\Services\PakaiLinkCallbackService;

class PakaiLinkCallbackController extends Controller
{
    public function __construct(
        private PakaiLinkCallbackService $callbackService,
    ) {}

    public function virtualAccount(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-SIGNATURE');
            $timestamp = $request->header('X-TIMESTAMP');

            $response = $this->callbackService->handleVirtualAccountCallback(
                $payload,
                $signature,
                $timestamp
            );

            return response()->json($response);
        } catch (PakaiLinkException $e) {
            Log::channel('pakailink')->error('Virtual Account callback failed', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '4000000',
                    $e->getMessage(),
                    $request->input('partnerReferenceNo')
                ),
                400
            );
        } catch (\Throwable $e) {
            Log::channel('pakailink')->error('Virtual Account callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '5000000',
                    'Internal server error',
                    $request->input('partnerReferenceNo')
                ),
                500
            );
        }
    }

    public function qris(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-SIGNATURE');
            $timestamp = $request->header('X-TIMESTAMP');

            $response = $this->callbackService->handleQrisCallback(
                $payload,
                $signature,
                $timestamp
            );

            return response()->json($response);
        } catch (PakaiLinkException $e) {
            Log::channel('pakailink')->error('QRIS callback failed', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '4000000',
                    $e->getMessage(),
                    $request->input('originalPartnerReferenceNo')
                ),
                400
            );
        } catch (\Throwable $e) {
            Log::channel('pakailink')->error('QRIS callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '5000000',
                    'Internal server error',
                    $request->input('originalPartnerReferenceNo')
                ),
                500
            );
        }
    }

    public function emoney(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-SIGNATURE');
            $timestamp = $request->header('X-TIMESTAMP');

            $response = $this->callbackService->handleEmoneyCallback(
                $payload,
                $signature,
                $timestamp
            );

            return response()->json($response);
        } catch (PakaiLinkException $e) {
            Log::channel('pakailink')->error('E-money callback failed', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '4000000',
                    $e->getMessage(),
                    $request->input('originalPartnerReferenceNo')
                ),
                400
            );
        } catch (\Throwable $e) {
            Log::channel('pakailink')->error('E-money callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '5000000',
                    'Internal server error',
                    $request->input('originalPartnerReferenceNo')
                ),
                500
            );
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-SIGNATURE');
            $timestamp = $request->header('X-TIMESTAMP');

            $response = $this->callbackService->handleTransferCallback(
                $payload,
                $signature,
                $timestamp
            );

            return response()->json($response);
        } catch (PakaiLinkException $e) {
            Log::channel('pakailink')->error('Transfer callback failed', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '4000000',
                    $e->getMessage(),
                    $request->input('partnerReferenceNo')
                ),
                400
            );
        } catch (\Throwable $e) {
            Log::channel('pakailink')->error('Transfer callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '5000000',
                    'Internal server error',
                    $request->input('partnerReferenceNo')
                ),
                500
            );
        }
    }

    public function retail(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-SIGNATURE');
            $timestamp = $request->header('X-TIMESTAMP');

            $response = $this->callbackService->handleRetailCallback(
                $payload,
                $signature,
                $timestamp
            );

            return response()->json($response);
        } catch (PakaiLinkException $e) {
            Log::channel('pakailink')->error('Retail callback failed', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '4000000',
                    $e->getMessage(),
                    $request->input('transactionData.partnerReferenceNo')
                ),
                400
            );
        } catch (\Throwable $e) {
            Log::channel('pakailink')->error('Retail callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '5000000',
                    'Internal server error',
                    $request->input('transactionData.partnerReferenceNo')
                ),
                500
            );
        }
    }

    public function topup(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-SIGNATURE');
            $timestamp = $request->header('X-TIMESTAMP');

            $response = $this->callbackService->handleTopupCallback(
                $payload,
                $signature,
                $timestamp
            );

            return response()->json($response);
        } catch (PakaiLinkException $e) {
            Log::channel('pakailink')->error('Topup callback failed', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '4000000',
                    $e->getMessage(),
                    $request->input('transactionData.partnerReferenceNo')
                ),
                400
            );
        } catch (\Throwable $e) {
            Log::channel('pakailink')->error('Topup callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                $this->callbackService->buildErrorResponse(
                    '5000000',
                    'Internal server error',
                    $request->input('transactionData.partnerReferenceNo')
                ),
                500
            );
        }
    }
}
