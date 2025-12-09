<?php

use PakaiLink\Data\GenerateQrisData;
use PakaiLink\Services\PakaiLinkQrisService;

beforeEach(function () {
    $this->qrisService = app(PakaiLinkQrisService::class);
    $this->testPartnerRefNo = null;
    $this->testReferenceNo = null;
});

test('can generate dynamic QRIS code', function () {
    $data = GenerateQrisData::from([
        'merchant_id' => config('pakailink.credentials.merchant_id'),
        'amount' => 50000,
        'terminalId' => 'TERM001',
        'validityPeriod' => '30', // 30 minutes
    ]);

    $response = $this->qrisService->generateQris($data);

    expect($response)
        ->toBeArray()
        ->toHaveKeys(['qrContent', 'originalPartnerReferenceNo', 'originalReferenceNo']);

    $this->testPartnerRefNo = $response['originalPartnerReferenceNo'];
    $this->testReferenceNo = $response['originalReferenceNo'];

    expect($response['qrContent'])
        ->toBeString()
        ->not->toBeEmpty();

    $this->info('✓ QRIS Generated:');
    $this->info('  - Partner Ref: '.$response['originalPartnerReferenceNo']);
    $this->info('  - QR Content Length: '.strlen($response['qrContent']));
})->group('integration', 'qris', 'sandbox');

test('can generate QRIS with different amounts', function () {
    $amounts = [10000, 25000, 100000, 500000];

    foreach ($amounts as $amount) {
        $data = GenerateQrisData::from([
            'merchant_id' => config('pakailink.credentials.merchant_id'),
            'amount' => $amount,
            'terminalId' => 'TERM'.rand(100, 999),
            'validityPeriod' => '30',
        ]);

        $response = $this->qrisService->generateQris($data);

        expect($response)
            ->toHaveKey('qrContent')
            ->and($response['qrContent'])->not->toBeEmpty();
    }

    $this->info('✓ Generated QRIS for amounts: '.implode(', ', $amounts));
})->group('integration', 'qris', 'sandbox');

test('can inquiry QRIS payment status', function () {
    // First generate QRIS
    $data = GenerateQrisData::from([
        'merchant_id' => config('pakailink.credentials.merchant_id'),
        'amount' => 75000,
        'terminalId' => 'TERM002',
        'validityPeriod' => '30',
    ]);

    $generateResponse = $this->qrisService->generateQris($data);
    $this->testPartnerRefNo = $generateResponse['originalPartnerReferenceNo'];

    // Then inquiry status
    $inquiryResponse = $this->qrisService->inquiryStatus(
        $generateResponse['originalPartnerReferenceNo'],
        $generateResponse['originalReferenceNo']
    );

    expect($inquiryResponse)
        ->toBeArray()
        ->toHaveKey('latestTransactionStatus');

    $this->info('✓ QRIS Inquiry:');
    $this->info('  - Status: '.$inquiryResponse['latestTransactionStatus']);
    $this->info('  - Status Desc: '.($inquiryResponse['transactionStatusDesc'] ?? 'Pending'));
})->group('integration', 'qris', 'sandbox');

test('generates unique QRIS reference numbers', function () {
    $refNos = [];

    for ($i = 0; $i < 5; $i++) {
        $refNo = $this->qrisService->generateReferenceNo();
        expect($refNo)->toMatch('/^QRIS-\d{14}-[A-Z0-9]{8}$/');
        $refNos[] = $refNo;
    }

    expect(count($refNos))->toBe(count(array_unique($refNos)));

    $this->info('✓ Generated 5 unique QRIS reference numbers');
})->group('integration', 'qris', 'sandbox');

test('can generate QRIS with custom validity periods', function () {
    $validityPeriods = ['15', '30', '60', '120']; // minutes

    foreach ($validityPeriods as $period) {
        $data = GenerateQrisData::from([
            'amount' => 50000,
            'terminalId' => 'TERM003',
            'validityPeriod' => $period,
        ]);

        $response = $this->qrisService->generateQris($data);

        expect($response)->toHaveKey('qrContent');
    }

    $this->info('✓ Generated QRIS with different validity periods');
})->group('integration', 'qris', 'sandbox');

test('QRIS QR content is valid format', function () {
    $data = GenerateQrisData::from([
        'merchant_id' => config('pakailink.credentials.merchant_id'),
        'amount' => 100000,
        'terminalId' => 'TERM004',
        'validityPeriod' => '30',
    ]);

    $response = $this->qrisService->generateQris($data);

    $qrContent = $response['qrContent'];

    // QRIS typically starts with specific identifiers
    expect($qrContent)
        ->toBeString()
        ->and(strlen($qrContent))->toBeGreaterThan(50);

    $this->info('✓ QR Content is valid format');
})->group('integration', 'qris', 'sandbox');

test('can generate multiple QRIS codes simultaneously', function () {
    $qrisCodes = [];

    for ($i = 0; $i < 3; $i++) {
        $data = GenerateQrisData::from([
            'amount' => 50000 + ($i * 10000),
            'terminalId' => 'TERM'.str_pad($i, 3, '0', STR_PAD_LEFT),
            'validityPeriod' => '30',
        ]);

        $response = $this->qrisService->generateQris($data);
        $qrisCodes[] = $response;

        expect($response)->toHaveKey('qrContent');
    }

    // All should have unique references
    $refs = array_map(fn ($qr) => $qr['originalPartnerReferenceNo'], $qrisCodes);
    expect(count($refs))->toBe(count(array_unique($refs)));

    $this->info('✓ Generated 3 simultaneous QRIS codes with unique references');
})->group('integration', 'qris', 'sandbox');

test('QRIS response includes required SNAP fields', function () {
    $data = GenerateQrisData::from([
        'merchant_id' => config('pakailink.credentials.merchant_id'),
        'amount' => 150000,
        'terminalId' => 'TERM005',
        'validityPeriod' => '30',
    ]);

    $response = $this->qrisService->generateQris($data);

    // Check SNAP required fields
    expect($response)
        ->toHaveKey('responseCode')
        ->toHaveKey('responseMessage')
        ->toHaveKey('qrContent')
        ->toHaveKey('originalPartnerReferenceNo');

    $this->info('✓ QRIS response includes all SNAP required fields');
})->group('integration', 'qris', 'sandbox');
