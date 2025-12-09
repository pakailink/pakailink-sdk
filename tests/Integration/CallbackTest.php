<?php

use Illuminate\Support\Facades\Event;
use PakaiLink\Events\CallbackReceived;
use PakaiLink\Events\EmoneyPaymentReceived;
use PakaiLink\Events\QrisPaymentReceived;
use PakaiLink\Events\TransferCompleted;
use PakaiLink\Events\VirtualAccountPaid;
use PakaiLink\Services\PakaiLinkSignatureService;

beforeEach(function () {
    $this->signatureService = app(PakaiLinkSignatureService::class);
});

test('can receive and process virtual account callback', function () {
    Event::fake([VirtualAccountPaid::class, CallbackReceived::class]);

    $payload = [
        'partnerServiceId' => 'DEV000015',
        'customerNo' => '12345678',
        'virtualAccountNo' => '888801234567890',
        'virtualAccountName' => 'John Doe Test',
        'partnerReferenceNo' => 'VA-'.time().'-TEST1234',
        'amount' => [
            'value' => '100000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
        'trxDateTime' => now()->toIso8601String(),
    ];

    $timestamp = now()->toIso8601String();
    $signature = $this->signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/virtual-account',
        json_encode($payload),
        $timestamp
    );

    $response = $this->postJson('/api/pakailink/callbacks/virtual-account', $payload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['responseCode', 'responseMessage', 'partnerReferenceNo']);

    Event::assertDispatched(VirtualAccountPaid::class);
    Event::assertDispatched(CallbackReceived::class);

    $this->info('✓ Virtual Account callback processed successfully');
})->group('integration', 'callback', 'sandbox');

test('rejects callback with invalid signature', function () {
    $payload = [
        'partnerServiceId' => 'DEV000015',
        'customerNo' => '12345678',
        'virtualAccountNo' => '888801234567890',
        'virtualAccountName' => 'Test Invalid',
        'partnerReferenceNo' => 'VA-INVALID-'.time(),
        'amount' => [
            'value' => '50000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
    ];

    $response = $this->postJson('/api/pakailink/callbacks/virtual-account', $payload, [
        'X-SIGNATURE' => 'invalid-signature',
        'X-TIMESTAMP' => now()->toIso8601String(),
    ]);

    $response->assertStatus(401);
    $response->assertJson(['responseCode' => '4010001']);

    $this->info('✓ Invalid signature correctly rejected');
})->group('integration', 'callback', 'sandbox');

test('rejects callback without signature header', function () {
    $payload = [
        'partnerReferenceNo' => 'VA-'.time(),
        'latestTransactionStatus' => '00',
    ];

    $response = $this->postJson('/api/pakailink/callbacks/virtual-account', $payload);

    $response->assertStatus(401);
    $response->assertJson(['responseCode' => '4010000']);

    $this->info('✓ Missing signature header correctly rejected');
})->group('integration', 'callback', 'sandbox');

test('can receive QRIS payment callback', function () {
    Event::fake([QrisPaymentReceived::class]);

    $payload = [
        'originalPartnerReferenceNo' => 'QRIS-'.time().'-TEST1234',
        'originalReferenceNo' => 'REF-'.time(),
        'merchantId' => 'MERCHANT001',
        'subMerchantId' => 'SUB001',
        'externalStoreId' => 'STORE001',
        'amount' => [
            'value' => '75000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
        'transactionDate' => now()->toIso8601String(),
    ];

    $timestamp = now()->toIso8601String();
    $signature = $this->signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/qris',
        json_encode($payload),
        $timestamp
    );

    $response = $this->postJson('/api/pakailink/callbacks/qris', $payload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $response->assertOk();
    Event::assertDispatched(QrisPaymentReceived::class);

    $this->info('✓ QRIS callback processed successfully');
})->group('integration', 'callback', 'sandbox');

test('can receive emoney payment callback', function () {
    Event::fake([EmoneyPaymentReceived::class]);

    $payload = [
        'originalPartnerReferenceNo' => 'EMY-'.time().'-TEST1234',
        'originalReferenceNo' => 'REF-'.time(),
        'merchantId' => 'MERCHANT001',
        'channelId' => 'GOPAY',
        'amount' => [
            'value' => '50000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
        'transactionDate' => now()->toIso8601String(),
    ];

    $timestamp = now()->toIso8601String();
    $signature = $this->signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/emoney',
        json_encode($payload),
        $timestamp
    );

    $response = $this->postJson('/api/pakailink/callbacks/emoney', $payload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $response->assertOk();
    Event::assertDispatched(EmoneyPaymentReceived::class);

    $this->info('✓ E-money callback processed successfully');
})->group('integration', 'callback', 'sandbox');

test('can receive transfer completed callback', function () {
    Event::fake([TransferCompleted::class]);

    $payload = [
        'partnerReferenceNo' => 'TRF-'.time().'-TEST1234',
        'referenceNo' => 'REF-'.time(),
        'beneficiaryBankCode' => '014',
        'beneficiaryAccountNo' => '1234567890',
        'beneficiaryAccountName' => 'John Doe',
        'amount' => [
            'value' => '200000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
        'transactionDate' => now()->toIso8601String(),
    ];

    $timestamp = now()->toIso8601String();
    $signature = $this->signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/transfer',
        json_encode($payload),
        $timestamp
    );

    $response = $this->postJson('/api/pakailink/callbacks/transfer', $payload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $response->assertOk();
    Event::assertDispatched(TransferCompleted::class);

    $this->info('✓ Transfer callback processed successfully');
})->group('integration', 'callback', 'sandbox');

test('callback response includes SNAP required fields', function () {
    $payload = [
        'partnerServiceId' => 'DEV000015',
        'customerNo' => '12345678',
        'virtualAccountNo' => '888801234567890',
        'virtualAccountName' => 'Test Response',
        'partnerReferenceNo' => 'VA-'.time().'-RESP',
        'amount' => [
            'value' => '100000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
    ];

    $timestamp = now()->toIso8601String();
    $signature = $this->signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/virtual-account',
        json_encode($payload),
        $timestamp
    );

    $response = $this->postJson('/api/pakailink/callbacks/virtual-account', $payload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'responseCode',
        'responseMessage',
        'partnerReferenceNo',
    ]);

    expect($response->json('responseCode'))->toBe('2000000');
    expect($response->json('responseMessage'))->toBe('Success');

    $this->info('✓ Callback response includes all SNAP required fields');
})->group('integration', 'callback', 'sandbox');

test('callbacks are logged correctly', function () {
    $payload = [
        'partnerServiceId' => 'DEV000015',
        'customerNo' => '12345678',
        'virtualAccountNo' => '888801234567890',
        'virtualAccountName' => 'Test Logging',
        'partnerReferenceNo' => 'VA-LOG-'.time(),
        'amount' => [
            'value' => '100000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
    ];

    $timestamp = now()->toIso8601String();
    $signature = $this->signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/virtual-account',
        json_encode($payload),
        $timestamp
    );

    $response = $this->postJson('/api/pakailink/callbacks/virtual-account', $payload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $response->assertOk();

    // Check log file exists
    $logPath = storage_path('logs/payments/pakailink.log');
    expect(file_exists($logPath))->toBeTrue();

    $this->info('✓ Callback logged to: '.$logPath);
})->group('integration', 'callback', 'sandbox');
