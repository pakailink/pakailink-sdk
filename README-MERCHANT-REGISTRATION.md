# PakaiLink SDK - Merchant Registration Guide

## QRIS Merchant Registration

### Basic Registration

```php
use Pgpay\PakaiLink\Services\PakaiLinkMerchantService;

$merchantService = app(PakaiLinkMerchantService::class);

$merchantData = [
    'merchantName' => 'Warung Kopi Jaya',
    'merchantType' => 'STATIS',
    'merchantEmail' => 'contact@kopiJaya.com',
    'storeData' => [
        'storeAplicationName' => 'Kopi Jaya App',
        'storeWebsite' => 'www.kopijaya.com',
        'storeType' => 'Kafe dan Restoran',
        'storeName' => 'Warung Kopi Jaya',
        'omzet' => '100000',
        'storeAddress' => [
            'address' => 'Jl. Sudirman No. 10',
            'city' => 'Bandung',
            'postalCode' => '40201',
            'province' => 'Jawa Barat',
            'country' => 'ID',
        ],
    ],
];

$ownerData = [
    'firstName' => 'Budi',
    'lastName' => 'Santoso',
    'email' => 'budi@example.com',
    'phoneNumber' => '081234567890',
    'idNumber' => '3201234567890123',
    'taxId' => '001234567890000', // Optional
    'dateOfBirth' => '1990-05-15',
    'placeOfBirth' => 'Bandung',
];

$response = $merchantService->registerQrisMerchant($merchantData, $ownerData);

// Get merchant info
$registeredName = $response['detailData']['merchantName'];
```

## DANA Merchant Registration

### Full Registration with Documents

```php
$merchantData = [
    'merchantName' => 'Toko Roti Enak Jaya',
    'merchantCriteria' => 'UMKM',
    'merchantCategoryCode' => ['5462'], // Bakery
    'merchantType' => 'UMI',
    'merchantEmail' => 'admin@tokoenakjaya.com',
    'merchantDescription' => 'Menjual aneka roti dan kue basah',
    'merchantGoodsType' => 'DIGITAL',
    'merchantUsecase' => 'Online Payment Digital Goods',
    'merchantBussinesType' => 'B2B',
    'merchantBussinesEntities' => 'individu',
    'pgDivisionFlag' => 'true',

    // Director PIC (required)
    'merchantDirectorPic' => [
        [
            'picName' => 'Ahmad Yani',
            'picPosition' => 'Director',
        ],
    ],

    // Non-Director PIC (required)
    'merchantNonDirectorPic' => [
        [
            'picName' => 'Siti Rahma',
            'picPosition' => 'Manager',
        ],
    ],

    // Documents (required)
    'merchantDocs' => [
        [
            'docId' => '12323412341234',
            'docType' => 'KTP',
            'docFile' => base64_encode(file_get_contents('path/to/ktp.pdf')),
        ],
    ],

    // Logo (required)
    'merchantLogo' => [
        'logo' => base64_encode(file_get_contents('path/to/logo.png')),
        'pcLogo' => base64_encode(file_get_contents('path/to/logo-pc.png')),
        'mobileLogo' => base64_encode(file_get_contents('path/to/logo-mobile.png')),
    ],

    'storeData' => [
        'storeAplicationName' => 'Toko Enak Jaya App',
        'storeWebsite' => 'https://www.tokoenakjaya.com',
        'storeType' => 'OFFLINE',
        'storeName' => 'Toko Roti Enak Jaya - Cabang Pusat',
        'omzet' => '<2BIO',
        'avgTransaction' => '50000',
        'storeAddress' => [
            'address' => 'Jl. Pahlawan No. 123',
            'area' => 'BANDAR BARU',
            'city' => 'PIDIE JAYA',
            'postalCode' => '24184',
            'province' => 'ACEH',
            'country' => 'Indonesia',
            'subDistrict' => 'ABAH LUENG',
        ],
    ],
];

$ownerData = [
    'firstName' => 'Budi',
    'lastName' => 'Santoso',
    'email' => 'budi.santoso@example.com',
    'phoneNumber' => '081234567890',
    'idNumber' => '3578012345670001',
    'idType' => 'KTP',
    'mobileId' => '081234567890',
    'address' => [
        'address' => 'Jl. Pahlawan No. 123',
        'area' => 'BANDAR BARU',
        'city' => 'PIDIE JAYA',
        'postalCode' => '24184',
        'province' => 'ACEH',
        'country' => 'Indonesia',
        'subDistrict' => 'ABAH LUENG',
    ],
];

$response = $merchantService->registerDanaMerchant($merchantData, $ownerData);
```

## Required Fields

### QRIS Registration (Simplified)
- Merchant: name, type, email
- Store: name, type, website, address
- Owner: name, email, phone, ID number, DOB

### DANA Registration (Complex)
- All QRIS fields PLUS:
- Merchant criteria, category code
- Business type, entities, goods type
- Director & non-director PIC
- Documents (KTP, etc.) in base64
- Logo (3 versions) in base64

## Error Codes

| Code | Message | Solution |
|------|---------|----------|
| `2004900` | Successful | Success |
| `4004902` | Invalid Mandatory Field | Add missing fields |
| `4014900` | Unauthorized | Check auth |
| `4034901` | Transaction Not Found | Check reference |

---

**Note:** Merchant registration is typically done once per merchant. Use carefully and verify all data before submission.
