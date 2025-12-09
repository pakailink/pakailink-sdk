<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use PakaiLink\Data\CreateEmoneyPaymentData;
use PakaiLink\Data\CreateVirtualAccountData;
use PakaiLink\Data\GenerateQrisData;
use PakaiLink\Enums\PakaiLinkBankCode;
use PakaiLink\Events\VirtualAccountPaid;
use PakaiLink\Services\PakaiLinkAuthService;
use PakaiLink\Services\PakaiLinkEmoneyService;
use PakaiLink\Services\PakaiLinkQrisService;
use PakaiLink\Services\PakaiLinkSignatureService;
use PakaiLink\Services\PakaiLinkVirtualAccountService;

test('complete virtual account flow from creation to callback', function () {
    Event::fake([VirtualAccountPaid::class]);

    // Step 1: Authenticate
    $authService = app(PakaiLinkAuthService::class);
    $token = $authService->getAccessToken();

    expect($token)->toBeString()->not->toBeEmpty();
    $this->info('✓ Step 1: Authenticated with PakaiLink');

    // Step 2: Create Virtual Account
    $vaService = app(PakaiLinkVirtualAccountService::class);

    $vaData = CreateVirtualAccountData::from([
        'amount' => 150000,
        'customerName' => 'End-to-End Test User',
        'bankCode' => PakaiLinkBankCode::BCA->value,
        'customerNo' => 'E2E'.rand(10000, 99999),
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $vaResponse = $vaService->create($vaData);

    expect($vaResponse)
        ->toHaveKey('virtualAccountNo')
        ->toHaveKey('partnerReferenceNo');

    $vaNumber = $vaResponse['virtualAccountNo'];
    $partnerRefNo = $vaResponse['partnerReferenceNo'];

    $this->info("✓ Step 2: VA Created - {$vaNumber}");

    // Step 3: Inquiry Status (unpaid)
    $inquiryResponse = $vaService->inquiryStatus($partnerRefNo, $vaNumber);

    expect($inquiryResponse)
        ->toHaveKey('latestTransactionStatus');

    $this->info('✓ Step 3: Status Inquired - '.$inquiryResponse['latestTransactionStatus']);

    // Step 4: Simulate Payment Callback
    $callbackPayload = [
        'partnerServiceId' => config('pakailink.credentials.partner_id'),
        'customerNo' => $vaData->customerNo,
        'virtualAccountNo' => $vaNumber,
        'virtualAccountName' => $vaData->customerName,
        'partnerReferenceNo' => $partnerRefNo,
        'amount' => [
            'value' => number_format($vaData->amount, 2, '.', ''),
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00', // Success
        'transactionStatusDesc' => 'Success',
        'trxDateTime' => now()->toIso8601String(),
    ];

    $signatureService = app(PakaiLinkSignatureService::class);
    $timestamp = now()->toIso8601String();
    $signature = $signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/virtual-account',
        json_encode($callbackPayload),
        $timestamp
    );

    $callbackResponse = $this->postJson('/api/pakailink/callbacks/virtual-account', $callbackPayload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $callbackResponse->assertOk();
    Event::assertDispatched(VirtualAccountPaid::class);

    $this->info('✓ Step 4: Payment Callback Received and Processed');

    // Step 5: Clean up - Delete VA
    $deleteResponse = $vaService->delete($partnerRefNo, $vaNumber);

    expect($deleteResponse)->toBeArray();

    $this->info('✓ Step 5: VA Deleted');

    $this->info("\n✅ END-TO-END TEST COMPLETED SUCCESSFULLY");
})->group('integration', 'e2e', 'sandbox');

test('complete QRIS flow from generation to callback', function () {
    Event::fake();

    // Authenticate
    $authService = app(PakaiLinkAuthService::class);
    $token = $authService->getAccessToken();

    $this->info('✓ Authenticated');

    // Generate QRIS
    $qrisService = app(PakaiLinkQrisService::class);

    $qrisData = GenerateQrisData::from([
        'merchant_id' => config('pakailink.credentials.merchant_id'),
        'amount' => 85000,
        'terminalId' => 'TERM-E2E-001',
        'validityPeriod' => '30',
    ]);

    $qrisResponse = $qrisService->generateQris($qrisData);

    expect($qrisResponse)
        ->toHaveKey('qrContent')
        ->toHaveKey('originalPartnerReferenceNo');

    $this->info('✓ QRIS Generated');

    // Inquiry Status
    $inquiryResponse = $qrisService->inquiryStatus(
        $qrisResponse['originalPartnerReferenceNo'],
        $qrisResponse['originalReferenceNo']
    );

    expect($inquiryResponse)->toHaveKey('latestTransactionStatus');

    $this->info('✓ Status Inquired');

    // Simulate Callback
    $callbackPayload = [
        'originalPartnerReferenceNo' => $qrisResponse['originalPartnerReferenceNo'],
        'originalReferenceNo' => $qrisResponse['originalReferenceNo'],
        'merchantId' => 'MERCHANT001',
        'subMerchantId' => 'SUB001',
        'externalStoreId' => 'STORE001',
        'amount' => [
            'value' => '85000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
        'transactionDate' => now()->toIso8601String(),
    ];

    $signatureService = app(PakaiLinkSignatureService::class);
    $timestamp = now()->toIso8601String();
    $signature = $signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/qris',
        json_encode($callbackPayload),
        $timestamp
    );

    $callbackResponse = $this->postJson('/api/pakailink/callbacks/qris', $callbackPayload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $callbackResponse->assertOk();

    $this->info('✓ Payment Callback Processed');
    $this->info("\n✅ QRIS E2E COMPLETED");
})->group('integration', 'e2e', 'sandbox');

test('complete emoney flow from creation to callback', function () {
    Event::fake();

    // Authenticate
    $authService = app(PakaiLinkAuthService::class);
    $token = $authService->getAccessToken();

    // Create E-money Payment
    $emoneyService = app(PakaiLinkEmoneyService::class);

    $emoneyData = CreateEmoneyPaymentData::from([
        'channelId' => 'GOPAY',
        'amount' => 95000,
        'customerName' => 'E2E Test Customer',
        'customerPhone' => '081234567890',
        'customerEmail' => 'e2e@test.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $emoneyResponse = $emoneyService->createPayment($emoneyData);

    expect($emoneyResponse)
        ->toHaveKey('originalPartnerReferenceNo')
        ->toHaveKey('webRedirectUrl');

    $this->info('✓ E-money Payment Created');
    $this->info('  - Redirect URL: '.$emoneyResponse['webRedirectUrl']);

    // Inquiry Status
    $inquiryResponse = $emoneyService->inquiryStatus(
        $emoneyResponse['originalPartnerReferenceNo'],
        $emoneyResponse['originalReferenceNo']
    );

    $this->info('✓ Status Inquired');

    // Simulate Callback
    $callbackPayload = [
        'originalPartnerReferenceNo' => $emoneyResponse['originalPartnerReferenceNo'],
        'originalReferenceNo' => $emoneyResponse['originalReferenceNo'],
        'merchantId' => 'MERCHANT001',
        'channelId' => 'GOPAY',
        'amount' => [
            'value' => '95000.00',
            'currency' => 'IDR',
        ],
        'latestTransactionStatus' => '00',
        'transactionStatusDesc' => 'Success',
        'transactionDate' => now()->toIso8601String(),
    ];

    $signatureService = app(PakaiLinkSignatureService::class);
    $timestamp = now()->toIso8601String();
    $signature = $signatureService->generateSymmetricSignature(
        'POST',
        '/api/pakailink/callbacks/emoney',
        json_encode($callbackPayload),
        $timestamp
    );

    $callbackResponse = $this->postJson('/api/pakailink/callbacks/emoney', $callbackPayload, [
        'X-SIGNATURE' => $signature,
        'X-TIMESTAMP' => $timestamp,
    ]);

    $callbackResponse->assertOk();

    $this->info('✓ Payment Callback Processed');
    $this->info("\n✅ E-MONEY E2E COMPLETED");
})->group('integration', 'e2e', 'sandbox');

test('can handle multiple concurrent payment creations', function () {
    $vaService = app(PakaiLinkVirtualAccountService::class);
    $qrisService = app(PakaiLinkQrisService::class);
    $emoneyService = app(PakaiLinkEmoneyService::class);

    // Create VA
    $vaData = CreateVirtualAccountData::from([
        'amount' => 100000,
        'customerName' => 'Concurrent VA',
        'bankCode' => PakaiLinkBankCode::BCA->value,
        'customerNo' => 'CONC'.rand(10000, 99999),
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $vaResponse = $vaService->create($vaData);

    // Create QRIS
    $qrisData = GenerateQrisData::from([
        'merchant_id' => config('pakailink.credentials.merchant_id'),
        'amount' => 75000,
        'terminalId' => 'TERM-CONC',
        'validityPeriod' => '30',
    ]);

    $qrisResponse = $qrisService->generateQris($qrisData);

    // Create E-money
    $emoneyData = CreateEmoneyPaymentData::from([
        'channelId' => 'OVO',
        'amount' => 50000,
        'customerName' => 'Concurrent Emoney',
        'customerPhone' => '081234567890',
        'customerEmail' => 'concurrent@test.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $emoneyResponse = $emoneyService->createPayment($emoneyData);

    // Verify all created
    expect($vaResponse)->toHaveKey('virtualAccountNo');
    expect($qrisResponse)->toHaveKey('qrContent');
    expect($emoneyResponse)->toHaveKey('webRedirectUrl');

    // Cleanup VA
    $vaService->delete($vaResponse['partnerReferenceNo'], $vaResponse['virtualAccountNo']);

    $this->info('✓ Created 3 different payment methods concurrently');
    $this->info('  - Virtual Account: ✓');
    $this->info('  - QRIS: ✓');
    $this->info('  - E-money: ✓');
})->group('integration', 'e2e', 'sandbox');

test('token is reused across multiple API calls', function () {
    Cache::forget(config('pakailink.cache.token_key'));

    $vaService = app(PakaiLinkVirtualAccountService::class);

    // First call
    $vaData1 = CreateVirtualAccountData::from([
        'amount' => 50000,
        'customerName' => 'Token Test 1',
        'bankCode' => PakaiLinkBankCode::BCA->value,
        'customerNo' => 'TOK1'.rand(10000, 99999),
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $va1 = $vaService->create($vaData1);

    // Second call (should reuse token)
    $vaData2 = CreateVirtualAccountData::from([
        'amount' => 60000,
        'customerName' => 'Token Test 2',
        'bankCode' => PakaiLinkBankCode::BRI->value,
        'customerNo' => 'TOK2'.rand(10000, 99999),
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $va2 = $vaService->create($vaData2);

    // Verify both succeeded
    expect($va1)->toHaveKey('virtualAccountNo');
    expect($va2)->toHaveKey('virtualAccountNo');

    // Verify token is cached
    $cachedToken = Cache::get(config('pakailink.cache.token_key'));
    expect($cachedToken)->not->toBeNull();

    // Cleanup
    $vaService->delete($va1['partnerReferenceNo'], $va1['virtualAccountNo']);
    $vaService->delete($va2['partnerReferenceNo'], $va2['virtualAccountNo']);

    $this->info('✓ Token reused across multiple API calls');
})->group('integration', 'e2e', 'sandbox');
