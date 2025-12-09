<?php

use PakaiLink\Data\TopupPaymentData;
use PakaiLink\Services\PakaiLinkTopupService;

beforeEach(function () {
    $this->topupService = app(PakaiLinkTopupService::class);
    $this->testPartnerRefNo = null;
});

test('can create customer topup for OVO', function () {
    $data = TopupPaymentData::from([
        'amount' => 50000,
        'customer_number' => '08113338390',
        'product_code' => 'OVO',
        'session_id' => 'INQ'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
    ]);

    $response = $this->topupService->createTopup($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('responseCode')
        ->and($response['responseCode'])->toBe('2003800')
        ->and($response)->toHaveKeys(['referenceNo', 'partnerReferenceNo', 'customerNumber', 'customerName']);

    $this->testPartnerRefNo = $response['partnerReferenceNo'];

    $this->info('✓ Customer Topup Created (OVO):');
    $this->info('  - Reference No: '.$response['referenceNo']);
    $this->info('  - Customer: '.$response['customerName']);
    $this->info('  - Amount: '.$response['amount']['value']);
})->group('integration', 'topup', 'sandbox');

test('can create customer topup for DANA', function () {
    $data = TopupPaymentData::from([
        'amount' => 100000,
        'customer_number' => '08123456789',
        'product_code' => 'DANA',
        'session_id' => 'INQ'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
    ]);

    $response = $this->topupService->createTopup($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('responseCode')
        ->and($response['responseCode'])->toBe('2003800');

    $this->info('✓ Customer Topup Created (DANA): '.$response['referenceNo']);
})->group('integration', 'topup', 'sandbox');

test('can inquiry customer topup status', function () {
    // First create a topup
    $data = TopupPaymentData::from([
        'amount' => 75000,
        'customer_number' => '08133445566',
        'product_code' => 'OVO',
        'session_id' => 'INQ'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
    ]);

    $createResponse = $this->topupService->createTopup($data);
    $partnerRefNo = $createResponse['partnerReferenceNo'];

    // Then inquiry the status
    $inquiryResponse = $this->topupService->inquiryStatus($partnerRefNo);

    expect($inquiryResponse)
        ->toBeArray()
        ->toHaveKey('responseCode')
        ->and($inquiryResponse['responseCode'])->toBe('2004000')
        ->and($inquiryResponse)->toHaveKeys(['originalPartnerReferenceNo', 'latestTransactionStatus', 'customerNumber', 'customerName']);

    $this->info('✓ Customer Topup Inquiry:');
    $this->info('  - Status: '.$inquiryResponse['latestTransactionStatus']);
    $this->info('  - Status Desc: '.json_encode($inquiryResponse['latestTransactionStatusDesc'] ?? 'N/A'));
})->group('integration', 'topup', 'sandbox');

test('validates minimum amount for fund transfer topup', function () {
    $data = TopupPaymentData::from([
        'amount' => 15000, // Below minimum (20,000 for funds)
        'customer_number' => '08113338391',
        'product_code' => 'OVO',
        'session_id' => 'INQ0000001',
    ]);

    $this->topupService->createTopup($data);
})->group('integration', 'topup', 'sandbox')->throws(\Exception::class);

test('topup payment includes all required fields', function () {
    $data = TopupPaymentData::from([
        'amount' => 50000,
        'customer_number' => '08113338392',
        'product_code' => 'DANA',
        'session_id' => 'INQ0000002',
    ]);

    $payload = $data->toApiPayload();

    expect($payload)
        ->toHaveKey('partnerReferenceNo')
        ->toHaveKey('customerNumber')
        ->toHaveKey('productCode')
        ->toHaveKey('sessionId')
        ->toHaveKey('amount')
        ->toHaveKey('additionalInfo')
        ->and($payload['amount'])->toHaveKeys(['value', 'currency'])
        ->and($payload['additionalInfo'])->toHaveKey('callbackUrl');
})->group('integration', 'topup');

test('can create topup with different products', function () {
    $products = ['OVO', 'DANA', 'GOPAY', 'LINKAJA'];
    $createdTopups = [];

    foreach ($products as $product) {
        $data = TopupPaymentData::from([
            'amount' => 50000,
            'customer_number' => '0811'.rand(1000000, 9999999),
            'product_code' => $product,
            'session_id' => 'INQ'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
        ]);

        try {
            $response = $this->topupService->createTopup($data);
            $createdTopups[] = [
                'product' => $product,
                'referenceNo' => $response['referenceNo'] ?? 'N/A',
            ];

            expect($response)->toHaveKey('responseCode');
        } catch (\Exception $e) {
            // Some products might not be available in sandbox
            $this->info("  ⚠ {$product} not available: ".$e->getMessage());
        }
    }

    expect($createdTopups)->not->toBeEmpty();

    $this->info('✓ Created topups for: '.implode(', ', array_column($createdTopups, 'product')));
})->group('integration', 'topup', 'sandbox');
