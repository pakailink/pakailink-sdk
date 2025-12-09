<?php

use Carbon\Carbon;
use PakaiLink\Data\CreateVirtualAccountData;
use PakaiLink\Enums\PakaiLinkBankCode;
use PakaiLink\Services\PakaiLinkVirtualAccountService;

beforeEach(function () {
    $this->vaService = app(PakaiLinkVirtualAccountService::class);
    $this->testVANumber = null;
    $this->testPartnerRefNo = null;
});

afterEach(function () {
    // Clean up: Delete created VA if exists
    if ($this->testVANumber && $this->testPartnerRefNo) {
        try {
            $this->vaService->delete($this->testPartnerRefNo, $this->testVANumber);
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }
});

test('can create virtual account with BCA', function () {
    $data = CreateVirtualAccountData::from([
        'amount' => 100000,
        'customerName' => 'John Doe Test',
        'bankCode' => PakaiLinkBankCode::BCA->value,
        'customerNo' => '12345678',
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $response = $this->vaService->create($data);

    expect($response)
        ->toBeArray()
        ->toHaveKeys(['virtualAccountNo', 'virtualAccountName', 'partnerReferenceNo']);

    $this->testVANumber = $response['virtualAccountNo'];
    $this->testPartnerRefNo = $response['partnerReferenceNo'];

    $this->info('✓ Virtual Account Created:');
    $this->info('  - VA Number: '.$response['virtualAccountNo']);
    $this->info('  - Name: '.$response['virtualAccountName']);
    $this->info('  - Reference: '.$response['partnerReferenceNo']);
})->group('integration', 'virtual-account', 'sandbox');

test('can create virtual account with BRI', function () {
    $data = CreateVirtualAccountData::from([
        'amount' => 150000,
        'customerName' => 'Jane Doe Test',
        'bankCode' => PakaiLinkBankCode::BRI->value,
        'customerNo' => '87654321',
        'expiredDate' => Carbon::now()->addHours(2)->toIso8601String(),
    ]);

    $response = $this->vaService->create($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('virtualAccountNo');

    $this->testVANumber = $response['virtualAccountNo'];
    $this->testPartnerRefNo = $response['partnerReferenceNo'];

    $this->info('✓ BRI Virtual Account Created: '.$response['virtualAccountNo']);
})->group('integration', 'virtual-account', 'sandbox');

test('can create virtual account with Mandiri', function () {
    $data = CreateVirtualAccountData::from([
        'amount' => 200000,
        'customerName' => 'Bob Smith Test',
        'bankCode' => PakaiLinkBankCode::MANDIRI->value,
        'customerNo' => '11223344',
        'expiredDate' => Carbon::now()->addDays(7)->toIso8601String(),
    ]);

    $response = $this->vaService->create($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('virtualAccountNo');

    $this->testVANumber = $response['virtualAccountNo'];
    $this->testPartnerRefNo = $response['partnerReferenceNo'];

    $this->info('✓ Mandiri Virtual Account Created: '.$response['virtualAccountNo']);
})->group('integration', 'virtual-account', 'sandbox');

test('can inquiry virtual account status', function () {
    // First create a VA
    $data = CreateVirtualAccountData::from([
        'amount' => 100000,
        'customerName' => 'Test Inquiry',
        'bankCode' => PakaiLinkBankCode::BNI->value,
        'customerNo' => '99887766',
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $createResponse = $this->vaService->create($data);
    $this->testVANumber = $createResponse['virtualAccountData']['virtualAccountNo'] ?? $createResponse['virtualAccountNo'];
    $this->testPartnerRefNo = $createResponse['virtualAccountData']['partnerReferenceNo'] ?? $createResponse['partnerReferenceNo'];

    // Then inquiry the status using partner reference number
    $inquiryResponse = $this->vaService->inquiryStatus($this->testPartnerRefNo);

    expect($inquiryResponse)
        ->toBeArray()
        ->toHaveKeys(['originalPartnerReferenceNo', 'latestTransactionStatus']);

    $this->info('✓ VA Inquiry Response:');
    $this->info('  - Status: '.$inquiryResponse['latestTransactionStatus']);
    $this->info('  - Status Desc: '.($inquiryResponse['transactionStatusDesc'] ?? 'N/A'));
})->group('integration', 'virtual-account', 'sandbox');

test('can update virtual account amount', function () {
    // Create VA
    $data = CreateVirtualAccountData::from([
        'amount' => 100000,
        'customerName' => 'Test Update',
        'bankCode' => PakaiLinkBankCode::BCA->value,
        'customerNo' => '55443322',
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $createResponse = $this->vaService->create($data);
    $this->testVANumber = $createResponse['virtualAccountNo'];
    $this->testPartnerRefNo = $createResponse['partnerReferenceNo'];

    // Update amount
    $updateData = CreateVirtualAccountData::from([
        'amount' => 250000, // New amount
        'customerName' => 'Test Update',
        'bankCode' => PakaiLinkBankCode::BCA->value,
        'customerNo' => '55443322',
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $updateResponse = $this->vaService->update(
        $createResponse['partnerReferenceNo'],
        $createResponse['virtualAccountNo'],
        $updateData
    );

    expect($updateResponse)
        ->toBeArray()
        ->toHaveKey('virtualAccountNo');

    $this->info('✓ VA Amount Updated to 250,000');
})->group('integration', 'virtual-account', 'sandbox');

test('can delete virtual account', function () {
    // Create VA
    $data = CreateVirtualAccountData::from([
        'amount' => 100000,
        'customerName' => 'Test Delete',
        'bankCode' => PakaiLinkBankCode::BCA->value,
        'customerNo' => '11111111',
        'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
    ]);

    $createResponse = $this->vaService->create($data);

    // Delete VA
    $deleteResponse = $this->vaService->delete(
        $createResponse['partnerReferenceNo'],
        $createResponse['virtualAccountNo']
    );

    expect($deleteResponse)
        ->toBeArray()
        ->toHaveKey('virtualAccountNo');

    $this->info('✓ VA Deleted Successfully');

    // Clear test data since we deleted it
    $this->testVANumber = null;
    $this->testPartnerRefNo = null;
})->group('integration', 'virtual-account', 'sandbox');

test('generates unique reference numbers', function () {
    $refNos = [];

    // Generate 5 reference numbers
    for ($i = 0; $i < 5; $i++) {
        $refNo = $this->vaService->generateReferenceNo();
        expect($refNo)->toMatch('/^VA-\d{14}-[A-Z0-9]{8}$/');
        $refNos[] = $refNo;
    }

    // All should be unique
    expect(count($refNos))->toBe(count(array_unique($refNos)));

    $this->info('✓ Generated 5 unique reference numbers');
})->group('integration', 'virtual-account', 'sandbox');

test('can create virtual account with different amounts', function () {
    $amounts = [50000, 100000, 500000, 1000000];
    $createdVAs = [];

    foreach ($amounts as $amount) {
        $data = CreateVirtualAccountData::from([
            'amount' => $amount,
            'customerName' => "Test Amount {$amount}",
            'bankCode' => PakaiLinkBankCode::BCA->value,
            'customerNo' => (string) rand(10000000, 99999999),
            'expiredDate' => Carbon::now()->addDays(1)->toIso8601String(),
        ]);

        $response = $this->vaService->create($data);
        $createdVAs[] = $response;

        expect($response)->toHaveKey('virtualAccountNo');
    }

    // Cleanup
    foreach ($createdVAs as $va) {
        try {
            $this->vaService->delete($va['partnerReferenceNo'], $va['virtualAccountNo']);
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }

    $this->info('✓ Created VAs with different amounts: '.implode(', ', $amounts));
})->group('integration', 'virtual-account', 'sandbox');

test('virtual account expires correctly', function () {
    // Create VA with short expiry
    $data = CreateVirtualAccountData::from([
        'amount' => 100000,
        'customerName' => 'Test Expiry',
        'bankCode' => PakaiLinkBankCode::BCA->value,
        'customerNo' => '22222222',
        'expiredDate' => Carbon::now()->addMinutes(5)->toIso8601String(),
    ]);

    $response = $this->vaService->create($data);
    $this->testVANumber = $response['virtualAccountNo'];
    $this->testPartnerRefNo = $response['partnerReferenceNo'];

    expect($response)->toHaveKey('expiredDate');

    $expiredDate = Carbon::parse($response['expiredDate']);
    $now = Carbon::now();

    expect($expiredDate->greaterThan($now))->toBeTrue();

    $this->info('✓ VA will expire at: '.$expiredDate->toDateTimeString());
})->group('integration', 'virtual-account', 'sandbox');
