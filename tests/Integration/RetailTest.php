<?php

use PakaiLink\Data\CreateRetailPaymentData;
use PakaiLink\Services\PakaiLinkRetailService;

beforeEach(function () {
    $this->retailService = app(PakaiLinkRetailService::class);
    $this->testPartnerRefNo = null;
});

test('can create retail payment with Alfamart', function () {
    $data = CreateRetailPaymentData::from([
        'amount' => 50000,
        'customer_id' => '31857119',
        'customer_name' => 'John Doe Test',
        'product_code' => 'ALFAMART',
        'customer_phone' => '085745512488',
        'customer_email' => 'test@example.com',
        'remark' => 'Pembayaran Test',
    ]);

    $response = $this->retailService->createPayment($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('responseCode')
        ->and($response['responseCode'])->toBe('2003100')
        ->and($response)->toHaveKey('paymentData')
        ->and($response['paymentData'])->toHaveKeys(['paymentCode', 'referenceNo', 'partnerReferenceNo']);

    $this->testPartnerRefNo = $response['paymentData']['partnerReferenceNo'];

    $this->info('✓ Retail Payment Created (Alfamart):');
    $this->info('  - Payment Code: '.$response['paymentData']['paymentCode']);
    $this->info('  - Reference No: '.$response['paymentData']['referenceNo']);
    $this->info('  - Partner Ref: '.$this->testPartnerRefNo);
})->group('integration', 'retail', 'sandbox');

test('can create retail payment with Indomaret', function () {
    $data = CreateRetailPaymentData::from([
        'amount' => 75000,
        'customer_id' => '31857120',
        'customer_name' => 'Jane Doe Test',
        'product_code' => 'INDOMARET',
        'customer_phone' => '085745512489',
        'customer_email' => 'test2@example.com',
    ]);

    $response = $this->retailService->createPayment($data);

    expect($response)
        ->toBeArray()
        ->toHaveKey('responseCode')
        ->and($response['responseCode'])->toBe('2003100');

    $this->info('✓ Retail Payment Created (Indomaret): '.$response['paymentData']['paymentCode']);
})->group('integration', 'retail', 'sandbox');

test('can inquiry retail payment status', function () {
    // First create a retail payment
    $data = CreateRetailPaymentData::from([
        'amount' => 60000,
        'customer_id' => '31857121',
        'customer_name' => 'Test Inquiry',
        'product_code' => 'ALFAMART',
        'customer_phone' => '085745512490',
    ]);

    $createResponse = $this->retailService->createPayment($data);
    $partnerRefNo = $createResponse['paymentData']['partnerReferenceNo'];

    // Then inquiry the status
    $inquiryResponse = $this->retailService->inquiryStatus($partnerRefNo);

    expect($inquiryResponse)
        ->toBeArray()
        ->toHaveKey('responseCode')
        ->and($inquiryResponse['responseCode'])->toBe('2003200')
        ->and($inquiryResponse)->toHaveKeys(['originalPartnerReferenceNo', 'latestTransactionStatus']);

    $this->info('✓ Retail Payment Inquiry:');
    $this->info('  - Status: '.$inquiryResponse['latestTransactionStatus']);
    $this->info('  - Status Desc: '.($inquiryResponse['transactionStatusDesc'] ?? 'N/A'));
})->group('integration', 'retail', 'sandbox');

test('validates minimum amount for retail payment', function () {
    $data = CreateRetailPaymentData::from([
        'amount' => 10000, // Below minimum (15,000)
        'customer_id' => '31857122',
        'customer_name' => 'Test Min Amount',
        'product_code' => 'ALFAMART',
    ]);

    $this->retailService->createPayment($data);
})->group('integration', 'retail', 'sandbox')->throws(\Exception::class);

test('validates maximum amount for retail payment', function () {
    $data = CreateRetailPaymentData::from([
        'amount' => 3000000, // Above maximum (2,500,000)
        'customer_id' => '31857123',
        'customer_name' => 'Test Max Amount',
        'product_code' => 'ALFAMART',
    ]);

    $this->retailService->createPayment($data);
})->group('integration', 'retail', 'sandbox')->throws(\Exception::class);

test('retail payment includes all required fields', function () {
    $data = CreateRetailPaymentData::from([
        'amount' => 50000,
        'customer_id' => '31857124',
        'customer_name' => 'Complete Test',
        'product_code' => 'ALFAMART',
        'customer_phone' => '085745512491',
        'customer_email' => 'complete@example.com',
        'remark' => 'Complete payment test',
    ]);

    $payload = $data->toApiPayload();

    expect($payload)
        ->toHaveKey('partnerReferenceNo')
        ->toHaveKey('customerId')
        ->toHaveKey('customerName')
        ->toHaveKey('totalAmount')
        ->toHaveKey('additionalInfo')
        ->and($payload['additionalInfo'])->toHaveKey('productCode')
        ->and($payload['additionalInfo'])->toHaveKey('callbackUrl');
})->group('integration', 'retail');
