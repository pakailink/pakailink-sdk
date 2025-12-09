# PakaiLink SDK - Integration Testing Guide

This guide explains how to run the comprehensive integration tests that connect to PakaiLink sandbox environment.

## Overview

The SDK includes 60+ integration tests that simulate real production scenarios:

| Test Suite          | Tests    | Description                                                |
|---------------------|----------|------------------------------------------------------------|
| **Authentication**  | 7 tests  | B2B token generation, caching, refresh                     |
| **Virtual Account** | 10 tests | Create, update, delete, inquiry VA for multiple banks      |
| **QRIS**            | 8 tests  | Generate QR codes, inquiry status, validation              |
| **E-money**         | 10 tests | Create payments for GoPay, OVO, DANA, ShopeePay, LinkAja   |
| **Transfer**        | 9 tests  | Bank account inquiry, transfer payload validation          |
| **Callbacks**       | 8 tests  | Webhook reception, signature validation, event dispatching |
| **End-to-End**      | 6 tests  | Complete payment flows from creation to callback           |

**Total: 58 integration tests**

## Prerequisites

### 1. Environment Configuration

Ensure your `.env` has PakaiLink sandbox credentials:

```bash
PAKAILINK_ENV=sandbox
PAKAILINK_BASE_URL=https://rising-dev.pakailink.id
PAKAILINK_CLIENT_ID=client-key-15
PAKAILINK_CLIENT_SECRET=signature-dev-15
PAKAILINK_PARTNER_ID=DEV000015
PAKAILINK_PRIVATE_KEY_PATH=storage/keys/sandbox_private_key.pem
PAKAILINK_PUBLIC_KEY_PATH=storage/keys/sandbox_public_key.pem
```

### 2. RSA Keys

Ensure your RSA keys exist:

```bash
ls -la storage/keys/sandbox_private_key.pem
ls -la storage/keys/sandbox_public_key.pem
```

### 3. Network Access

Verify you can reach PakaiLink sandbox:

```bash
curl -I https://rising-dev.pakailink.id
```

## Running Tests

### Run All Integration Tests

```bash
php artisan test --group=integration
```

### Run By Payment Method

#### Authentication Tests

```bash
php artisan test --group=auth --group=sandbox
```

#### Virtual Account Tests

```bash
php artisan test --group=virtual-account --group=sandbox
```

#### QRIS Tests

```bash
php artisan test --group=qris --group=sandbox
```

#### E-money Tests

```bash
php artisan test --group=emoney --group=sandbox
```

#### Transfer Tests

```bash
php artisan test --group=transfer --group=sandbox
```

#### Callback Tests

```bash
php artisan test --group=callback --group=sandbox
```

#### End-to-End Tests

```bash
php artisan test --group=e2e --group=sandbox
```

### Run Specific Test

```bash
# Run single test by name
php artisan test --filter="can generate B2B access token"

# Run all tests in a file
php artisan test packages/pgpay/pakailink-sdk/tests/Integration/AuthenticationTest.php
```

## Test Coverage

### Authentication Tests (`AuthenticationTest.php`)

✅ **can generate B2B access token from sandbox**

- Authenticates with PakaiLink
- Verifies token format and length
- Tests real API connection

✅ **token is cached and reused**

- Verifies token caching mechanism
- Tests cache retrieval
- Ensures no unnecessary API calls

✅ **can get token info**

- Tests token metadata retrieval
- Verifies expiry information
- Checks cache status

✅ **token has valid expiry time**

- Ensures token is not expired
- Tests expiry checking logic

✅ **can refresh expired token**

- Tests token refresh mechanism
- Verifies new token generation

✅ **authentication fails with invalid credentials**

- Tests error handling
- Verifies exception throwing

✅ **can authenticate multiple times without errors**

- Stress tests authentication
- Verifies stability

### Virtual Account Tests (`VirtualAccountTest.php`)

✅ **can create virtual account with BCA/BRI/Mandiri**

- Creates VAs for different banks
- Verifies VA number generation
- Tests bank-specific requirements

✅ **can inquiry virtual account status**

- Tests status checking
- Verifies response format

✅ **can update virtual account amount**

- Tests VA modification
- Verifies amount updates

✅ **can delete virtual account**

- Tests VA deletion
- Verifies cleanup

✅ **generates unique reference numbers**

- Tests reference generation
- Ensures uniqueness

✅ **can create virtual account with different amounts**

- Tests various amount ranges
- Verifies amount handling

✅ **virtual account expires correctly**

- Tests expiry date handling
- Verifies expiry logic

### QRIS Tests (`QrisTest.php`)

✅ **can generate dynamic QRIS code**

- Generates QR code
- Verifies QR content format

✅ **can generate QRIS with different amounts**

- Tests amount variations
- Verifies flexibility

✅ **can inquiry QRIS payment status**

- Tests status checking
- Verifies inquiry response

✅ **generates unique QRIS reference numbers**

- Tests reference generation
- Ensures uniqueness

✅ **can generate QRIS with custom validity periods**

- Tests different expiry times
- Verifies period handling

✅ **QRIS QR content is valid format**

- Validates QR format
- Tests content structure

✅ **can generate multiple QRIS codes simultaneously**

- Tests concurrent generation
- Verifies uniqueness

✅ **QRIS response includes required SNAP fields**

- Validates SNAP compliance
- Tests response structure

### E-money Tests (`EmoneyTest.php`)

✅ **can create GoPay/OVO/DANA/ShopeePay/LinkAja payment**

- Tests all e-wallet providers
- Verifies payment creation

✅ **can inquiry emoney payment status**

- Tests status checking
- Verifies response format

✅ **generates unique emoney reference numbers**

- Tests reference generation
- Ensures uniqueness

✅ **can create payments with different amounts**

- Tests all channels
- Verifies flexibility

✅ **emoney response includes redirect URL**

- Verifies redirect URL presence
- Tests URL format

✅ **emoney response includes SNAP required fields**

- Validates SNAP compliance
- Tests response structure

### Transfer Tests (`TransferTest.php`)

✅ **can inquiry bank account before transfer**

- Tests account inquiry
- Verifies response handling

✅ **can inquiry multiple bank codes**

- Tests different banks
- Verifies multi-bank support

✅ **generates unique transfer reference numbers**

- Tests reference generation
- Ensures uniqueness

✅ **inquiry accepts different amount ranges**

- Tests various amounts
- Verifies range handling

✅ **inquiry/transfer payloads include SNAP required fields**

- Validates SNAP compliance
- Tests payload structure

✅ **amount is formatted correctly in payload**

- Tests formatting
- Verifies decimal precision

### Callback Tests (`CallbackTest.php`)

✅ **can receive and process virtual account callback**

- Simulates webhook reception
- Tests signature validation
- Verifies event dispatching

✅ **rejects callback with invalid signature**

- Tests security
- Verifies rejection

✅ **rejects callback without signature header**

- Tests header validation
- Verifies error handling

✅ **can receive QRIS/E-money/Transfer callbacks**

- Tests all payment methods
- Verifies callback processing

✅ **callback response includes SNAP required fields**

- Validates SNAP compliance
- Tests response structure

✅ **callbacks are logged correctly**

- Tests logging
- Verifies log file creation

### End-to-End Tests (`EndToEndFlowTest.php`)

✅ **complete virtual account flow from creation to callback**

- Creates VA
- Inquiries status
- Simulates payment
- Receives callback
- Verifies event dispatch
- Cleans up

✅ **complete QRIS flow from generation to callback**

- Generates QR
- Inquiries status
- Simulates payment
- Receives callback

✅ **complete emoney flow from creation to callback**

- Creates payment
- Inquiries status
- Simulates payment
- Receives callback

✅ **can handle multiple concurrent payment creations**

- Creates VA, QRIS, E-money simultaneously
- Verifies all succeed

✅ **token is reused across multiple API calls**

- Tests token caching
- Verifies reuse

## Expected Output

When tests run successfully, you'll see:

```
✓ B2B Access Token generated: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
✓ Token cached successfully
✓ Virtual Account Created:
  - VA Number: 888801234567890
  - Name: John Doe Test
  - Reference: VA-20251127123456-ABC12345
✓ QRIS Generated:
  - Partner Ref: QRIS-20251127123456-ABC12345
  - QR Content Length: 180
✓ GoPay Payment Created:
  - Partner Ref: EMY-20251127123456-ABC12345
  - Redirect URL: https://rising-dev.pakailink.id/payment/...
```

## Troubleshooting

### Tests Fail with "Invalid credentials"

**Solution:**

```bash
# Verify credentials
grep PAKAILINK_ .env

# Test API connectivity
curl -X POST https://rising-dev.pakailink.id/api/v1.0/access-token/b2b \
  -H "Content-Type: application/json" \
  -d '{"grantType":"client_credentials"}'
```

### Tests Fail with "Could not connect"

**Solution:**

```bash
# Check network
ping rising-dev.pakailink.id

# Check proxy/firewall
curl -v https://rising-dev.pakailink.id
```

### Tests Fail with "Signature validation failed"

**Solution:**

```bash
# Verify keys exist
ls -la storage/keys/

# Verify key format
head -1 storage/keys/sandbox_private_key.pem
# Should show: -----BEGIN PRIVATE KEY-----

# Regenerate if needed
openssl genrsa -out storage/keys/sandbox_private_key.pem 2048
openssl rsa -in storage/keys/sandbox_private_key.pem -pubout -out storage/keys/sandbox_public_key.pem
```

### Tests Timeout

**Solution:**

```bash
# Increase timeout in phpunit.xml
<phpunit ... processTimeout="300">

# Or set in test
php artisan test --timeout=300
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Integration Tests

on: [ push, pull_request ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: openssl, json

      - name: Install Dependencies
        run: composer install

      - name: Generate Keys
        run: |
          mkdir -p storage/keys
          openssl genrsa -out storage/keys/sandbox_private_key.pem 2048
          openssl rsa -in storage/keys/sandbox_private_key.pem -pubout -out storage/keys/sandbox_public_key.pem

      - name: Run Integration Tests
        env:
          PAKAILINK_CLIENT_ID: ${{ secrets.PAKAILINK_CLIENT_ID }}
          PAKAILINK_CLIENT_SECRET: ${{ secrets.PAKAILINK_CLIENT_SECRET }}
        run: php artisan test --group=integration
```

## Performance Benchmarks

Expected execution times on standard hardware:

| Test Suite      | Duration | API Calls |
|-----------------|----------|-----------|
| Authentication  | ~5s      | 7 calls   |
| Virtual Account | ~30s     | 15+ calls |
| QRIS            | ~20s     | 12+ calls |
| E-money         | ~25s     | 15+ calls |
| Transfer        | ~15s     | 10+ calls |
| Callbacks       | ~10s     | 0 (local) |
| End-to-End      | ~40s     | 20+ calls |

**Total: ~2-3 minutes** for all 58 tests

## Best Practices

### 1. Run Tests Before Deployment

```bash
# Full test suite
php artisan test --group=integration

# Quick smoke test
php artisan test --group=auth --group=e2e
```

### 2. Use Sandbox for Development

Never run integration tests against production:

```bash
# Always verify
grep PAKAILINK_ENV .env
# Should output: PAKAILINK_ENV=sandbox
```

### 3. Clean Up Test Data

Tests auto-cleanup, but verify:

```bash
# Check for leftover VAs
# (In production, implement cleanup jobs)
```

### 4. Monitor Rate Limits

PakaiLink sandbox may have rate limits:

```bash
# Space out test runs
sleep 60 && php artisan test --group=integration
```

## Test Data

### Test Virtual Account Numbers

Tests create VAs with these patterns:

- BCA: `8888012345XXXXX`
- BRI: `8888026789XXXXX`
- Mandiri: `8888011223XXXXX`

### Test Amounts

Common test amounts:

- Rp 50,000
- Rp 100,000
- Rp 150,000
- Rp 500,000

### Test Phone Numbers

E-money tests use:

- `081234567890`
- `081298765432`
- Randomized numbers

## Contributing

When adding new tests:

1. Follow existing patterns
2. Use descriptive test names
3. Include cleanup in `afterEach`
4. Add informative `$this->info()` outputs
5. Group tests appropriately
6. Update this documentation

## Support

For issues with tests:

1. Check `.env` configuration
2. Verify RSA keys exist
3. Test network connectivity
4. Review logs: `storage/logs/payments/pakailink.log`
5. Contact PakaiLink support for sandbox issues

---

**Last Updated:** 2025-11-27
**Test Suite Version:** 1.0.0
**Compatible with:** PakaiLink SNAP API v1.0
