<?php

use PakaiLink\Data\CreateEmoneyPaymentData;
use PakaiLink\Services\PakaiLinkEmoneyService;

beforeEach(function () {
    $this->emoneyService = app(PakaiLinkEmoneyService::class);
    $this->testPartnerRefNo = null;
});

test('can create GoPay payment', function () {
    $data = CreateEmoneyPaymentData::from([
        'channelId' => 'GOPAY',
        'amount' => 50000,
        'customerName' => 'John Doe',
        'customerPhone' => '081234567890',
        'customerEmail' => 'john@example.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $response = $this->emoneyService->createPayment($data);

    expect($response)
        ->toBeArray()
        ->toHaveKeys(['originalPartnerReferenceNo', 'originalReferenceNo', 'webRedirectUrl']);

    $this->testPartnerRefNo = $response['originalPartnerReferenceNo'];

    $this->info('✓ GoPay Payment Created:');
    $this->info('  - Partner Ref: '.$response['originalPartnerReferenceNo']);
    $this->info('  - Redirect URL: '.$response['webRedirectUrl']);
})->group('integration', 'emoney', 'sandbox');

test('can create OVO payment', function () {
    $data = CreateEmoneyPaymentData::from([
        'channelId' => 'OVO',
        'amount' => 75000,
        'customerName' => 'Jane Smith',
        'customerPhone' => '081298765432',
        'customerEmail' => 'jane@example.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $response = $this->emoneyService->createPayment($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('originalPartnerReferenceNo');

    $this->testPartnerRefNo = $response['originalPartnerReferenceNo'];

    $this->info('✓ OVO Payment Created: '.$response['originalPartnerReferenceNo']);
})->group('integration', 'emoney', 'sandbox');

test('can create DANA payment', function () {
    $data = CreateEmoneyPaymentData::from([
        'channelId' => 'DANA',
        'amount' => 100000,
        'customerName' => 'Bob Johnson',
        'customerPhone' => '081355667788',
        'customerEmail' => 'bob@example.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $response = $this->emoneyService->createPayment($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('originalPartnerReferenceNo');

    $this->info('✓ DANA Payment Created: '.$response['originalPartnerReferenceNo']);
})->group('integration', 'emoney', 'sandbox');

test('can create ShopeePay payment', function () {
    $data = CreateEmoneyPaymentData::from([
        'channelId' => 'SHOPEEPAY',
        'amount' => 125000,
        'customerName' => 'Alice Brown',
        'customerPhone' => '081411223344',
        'customerEmail' => 'alice@example.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $response = $this->emoneyService->createPayment($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('originalPartnerReferenceNo');

    $this->info('✓ ShopeePay Payment Created: '.$response['originalPartnerReferenceNo']);
})->group('integration', 'emoney', 'sandbox');

test('can create LinkAja payment', function () {
    $data = CreateEmoneyPaymentData::from([
        'channelId' => 'LINKAJA',
        'amount' => 80000,
        'customerName' => 'Charlie Davis',
        'customerPhone' => '081599887766',
        'customerEmail' => 'charlie@example.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $response = $this->emoneyService->createPayment($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('originalPartnerReferenceNo');

    $this->info('✓ LinkAja Payment Created: '.$response['originalPartnerReferenceNo']);
})->group('integration', 'emoney', 'sandbox');

test('can inquiry emoney payment status', function () {
    // First create payment
    $data = CreateEmoneyPaymentData::from([
        'channelId' => 'GOPAY',
        'amount' => 50000,
        'customerName' => 'Test Inquiry',
        'customerPhone' => '081234567890',
        'customerEmail' => 'test@example.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $createResponse = $this->emoneyService->createPayment($data);

    // Then inquiry status
    $inquiryResponse = $this->emoneyService->inquiryStatus(
        $createResponse['originalPartnerReferenceNo'],
        $createResponse['originalReferenceNo']
    );

    expect($inquiryResponse)
        ->toBeArray()
        ->toHaveKey('latestTransactionStatus');

    $this->info('✓ E-money Inquiry:');
    $this->info('  - Status: '.$inquiryResponse['latestTransactionStatus']);
    $this->info('  - Status Desc: '.($inquiryResponse['transactionStatusDesc'] ?? 'Pending'));
})->group('integration', 'emoney', 'sandbox');

test('generates unique emoney reference numbers', function () {
    $refNos = [];

    for ($i = 0; $i < 5; $i++) {
        $refNo = $this->emoneyService->generateReferenceNo();
        expect($refNo)->toMatch('/^EMY-\d{14}-[A-Z0-9]{8}$/');
        $refNos[] = $refNo;
    }

    expect(count($refNos))->toBe(count(array_unique($refNos)));

    $this->info('✓ Generated 5 unique E-money reference numbers');
})->group('integration', 'emoney', 'sandbox');

test('can create payments with different amounts', function () {
    $amounts = [10000, 50000, 100000, 250000];
    $channels = ['GOPAY', 'OVO', 'DANA', 'SHOPEEPAY'];

    foreach (array_combine($channels, $amounts) as $channel => $amount) {
        $data = CreateEmoneyPaymentData::from([
            'channelId' => $channel,
            'amount' => $amount,
            'customerName' => "Test {$channel}",
            'customerPhone' => '081'.rand(100000000, 999999999),
            'customerEmail' => strtolower($channel).'@example.com',
            'webRedirectUrl' => 'https://example.com/return',
        ]);

        $response = $this->emoneyService->createPayment($data);

        expect($response)->toHaveKey('originalPartnerReferenceNo');
    }

    $this->info('✓ Created payments for all channels with different amounts');
})->group('integration', 'emoney', 'sandbox');

test('emoney response includes redirect URL', function () {
    $data = CreateEmoneyPaymentData::from([
        'channelId' => 'GOPAY',
        'amount' => 50000,
        'customerName' => 'Test Redirect',
        'customerPhone' => '081234567890',
        'customerEmail' => 'redirect@example.com',
        'webRedirectUrl' => 'https://example.com/callback',
    ]);

    $response = $this->emoneyService->createPayment($data);

    expect($response)
        ->toHaveKey('webRedirectUrl')
        ->and($response['webRedirectUrl'])->toBeString()->not->toBeEmpty();

    $this->info('✓ Response includes redirect URL');
})->group('integration', 'emoney', 'sandbox');

test('emoney response includes SNAP required fields', function () {
    $data = CreateEmoneyPaymentData::from([
        'channelId' => 'OVO',
        'amount' => 60000,
        'customerName' => 'Test SNAP',
        'customerPhone' => '081234567890',
        'customerEmail' => 'snap@example.com',
        'webRedirectUrl' => 'https://example.com/return',
    ]);

    $response = $this->emoneyService->createPayment($data);

    expect($response)
        ->toHaveKey('responseCode')
        ->toHaveKey('responseMessage')
        ->toHaveKey('originalPartnerReferenceNo')
        ->toHaveKey('originalReferenceNo');

    $this->info('✓ E-money response includes all SNAP required fields');
})->group('integration', 'emoney', 'sandbox');
