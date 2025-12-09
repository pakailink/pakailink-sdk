<?php

use PakaiLink\Data\TransferToBankData;
use PakaiLink\Enums\PakaiLinkBankCode;
use PakaiLink\Services\PakaiLinkTransferService;

beforeEach(function () {
    $this->transferService = app(PakaiLinkTransferService::class);
    $this->testPartnerRefNo = null;
});

test('can inquiry bank account before transfer', function () {
    $data = TransferToBankData::from([
        'beneficiaryBankCode' => PakaiLinkBankCode::BCA->value,
        'beneficiaryAccountNo' => '1234567890',
        'amount' => 100000,
    ]);

    $response = $this->transferService->inquiryTransfer($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('responseCode');

    // Note: In sandbox, this might return specific test responses
    $this->info('✓ Bank Account Inquiry:');
    $this->info('  - Response Code: '.$response['responseCode']);
    $this->info('  - Response Message: '.($response['responseMessage'] ?? 'N/A'));
})->group('integration', 'transfer', 'sandbox');

test('can inquiry multiple bank codes', function () {
    $banks = [
        PakaiLinkBankCode::BCA->value,
        PakaiLinkBankCode::BRI->value,
        PakaiLinkBankCode::MANDIRI->value,
        PakaiLinkBankCode::BNI->value,
    ];

    foreach ($banks as $bankCode) {
        $data = TransferToBankData::from([
            'beneficiaryBankCode' => $bankCode,
            'beneficiaryAccountNo' => '1234567890',
            'amount' => 50000,
        ]);

        $response = $this->transferService->inquiryTransfer($data);

        expect($response)->toHaveKey('responseCode');
    }

    $this->info('✓ Inquired accounts for '.count($banks).' different banks');
})->group('integration', 'transfer', 'sandbox');

test('generates unique transfer reference numbers', function () {
    $refNos = [];

    for ($i = 0; $i < 5; $i++) {
        $refNo = $this->transferService->generateReferenceNo();
        expect($refNo)->toMatch('/^TRF-\d{14}-[A-Z0-9]{8}$/');
        $refNos[] = $refNo;
    }

    expect(count($refNos))->toBe(count(array_unique($refNos)));

    $this->info('✓ Generated 5 unique transfer reference numbers');
})->group('integration', 'transfer', 'sandbox');

test('inquiry accepts different amount ranges', function () {
    $amounts = [10000, 100000, 500000, 1000000, 5000000];

    foreach ($amounts as $amount) {
        $data = TransferToBankData::from([
            'beneficiaryBankCode' => PakaiLinkBankCode::BCA->value,
            'beneficiaryAccountNo' => '9876543210',
            'amount' => $amount,
        ]);

        $response = $this->transferService->inquiryTransfer($data);

        expect($response)->toBeArray();
    }

    $this->info('✓ Inquiry works with amounts: '.implode(', ', $amounts));
})->group('integration', 'transfer', 'sandbox');

test('inquiry payload includes SNAP required fields', function () {
    $data = TransferToBankData::from([
        'beneficiaryBankCode' => PakaiLinkBankCode::BRI->value,
        'beneficiaryAccountNo' => '1122334455',
        'amount' => 250000,
        'customerReferenceNumber' => 'CUST-REF-'.time(),
    ]);

    // Get the inquiry payload to verify structure
    $payload = $data->toInquiryPayload();

    expect($payload)
        ->toHaveKey('beneficiaryBankCode')
        ->toHaveKey('beneficiaryAccountNo')
        ->toHaveKey('amount')
        ->and($payload['amount'])->toBeArray()
        ->toHaveKey('value')
        ->toHaveKey('currency');

    $this->info('✓ Inquiry payload includes all SNAP required fields');
})->group('integration', 'transfer', 'sandbox');

test('transfer payload includes additional info', function () {
    $data = TransferToBankData::from([
        'beneficiaryBankCode' => PakaiLinkBankCode::MANDIRI->value,
        'beneficiaryAccountNo' => '5544332211',
        'beneficiaryAccountName' => 'John Doe',
        'amount' => 150000,
        'customerReferenceNumber' => 'CUST-'.rand(10000, 99999),
        'remark' => 'Payment for invoice #12345',
    ]);

    $payload = $data->toApiPayload();

    expect($payload)
        ->toHaveKey('beneficiaryAccountName')
        ->toHaveKey('customerReferenceNumber')
        ->toHaveKey('remark');

    $this->info('✓ Transfer payload includes additional information fields');
})->group('integration', 'transfer', 'sandbox');

test('can create transfer payload for different banks', function () {
    $banks = [
        PakaiLinkBankCode::BCA->value,
        PakaiLinkBankCode::BRI->value,
        PakaiLinkBankCode::BNI->value,
        PakaiLinkBankCode::MANDIRI->value,
        PakaiLinkBankCode::CIMB->value,
    ];

    foreach ($banks as $bankCode) {
        $data = TransferToBankData::from([
            'beneficiaryBankCode' => $bankCode,
            'beneficiaryAccountNo' => '1234567890',
            'beneficiaryAccountName' => 'Test Account',
            'amount' => 100000,
        ]);

        $payload = $data->toApiPayload();

        expect($payload)
            ->toHaveKey('beneficiaryBankCode')
            ->and($payload['beneficiaryBankCode'])->toBe($bankCode);
    }

    $this->info('✓ Created transfer payloads for '.count($banks).' banks');
})->group('integration', 'transfer', 'sandbox');

test('amount is formatted correctly in payload', function () {
    $data = TransferToBankData::from([
        'beneficiaryBankCode' => PakaiLinkBankCode::BCA->value,
        'beneficiaryAccountNo' => '9999888877',
        'amount' => 123456,
    ]);

    $payload = $data->toApiPayload();

    expect($payload['amount'])
        ->toBeArray()
        ->toHaveKey('value')
        ->toHaveKey('currency')
        ->and($payload['amount']['value'])->toBe('123456.00')
        ->and($payload['amount']['currency'])->toBe('IDR');

    $this->info('✓ Amount formatted correctly: '.$payload['amount']['value'].' '.$payload['amount']['currency']);
})->group('integration', 'transfer', 'sandbox');

test('inquiry and transfer payloads are different', function () {
    $data = TransferToBankData::from([
        'beneficiaryBankCode' => PakaiLinkBankCode::BRI->value,
        'beneficiaryAccountNo' => '7777666655',
        'beneficiaryAccountName' => 'Jane Smith',
        'amount' => 200000,
    ]);

    $inquiryPayload = $data->toInquiryPayload();
    $transferPayload = $data->toApiPayload();

    // Inquiry has fewer fields
    expect($inquiryPayload)->toHaveKeys(['beneficiaryBankCode', 'beneficiaryAccountNo', 'amount']);

    // Transfer has more fields
    expect($transferPayload)->toHaveKeys([
        'partnerReferenceNo',
        'beneficiaryBankCode',
        'beneficiaryAccountNo',
        'beneficiaryAccountName',
        'amount',
    ]);

    $this->info('✓ Inquiry and transfer payloads have correct structure differences');
})->group('integration', 'transfer', 'sandbox');
