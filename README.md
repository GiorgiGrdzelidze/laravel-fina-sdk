# FINA SDK for Laravel

[![CI](https://github.com/GiorgiGrdzelidze/fina-sdk-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/GiorgiGrdzelidze/fina-sdk-laravel/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/github/v/release/GiorgiGrdzelidze/fina-sdk-laravel?label=release)](https://github.com/GiorgiGrdzelidze/fina-sdk-laravel/releases)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D12.0-FF2D20)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A production-ready Laravel SDK for the **FINA Web API v8.0**. Handles authentication, token caching, typed DTOs, request validation, chunked reporting with deduplication, and structured error handling out of the box.

---

## Table of Contents

- [What's New in 0.2.0](#whats-new-in-020)
- [What's New in v8.0](#whats-new-in-v80)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [API Reference](#api-reference)
  - [Customers](#customers)
  - [Vendors](#vendors)
  - [Products](#products)
  - [Reference Data](#reference-data)
  - [Services](#services)
  - [Inventories](#inventories)
  - [Loyalty](#loyalty)
  - [Documents](#documents)
  - [Reporting](#reporting)
- [DTOs & Validation](#dtos--validation)
- [Error Handling](#error-handling)
- [Performance](#performance)
- [Testing & Quality](#testing--quality)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## What's New in 0.2.0

Full alignment with the **FINA WEB API 8.0** documentation (8.0.20260303): every one of its 138 methods is now reachable, and a parity test keeps it that way.

| Area | Change |
|---|---|
| **Fixed** | Chunked journals deduplicate per *operation across chunks* — `getEntriesJournal` returns one row per entry line with the same `id`/`version` on every line, and the old `id+version` row key silently dropped all but the first line of every document |
| **Fixed** | `ProductsInReturnReportRowDto`, `ProductsLastInReportRowDto`, `ProductionsJournalRowDto` now map the documented fields (`vendor_id`, `store_id`, `product_id`, `vat`, `waybill_date`, `production_type` …) instead of fields the API never sends |
| **Journals** | `movesJournal()` (raw / typed / chunked) and typed `realizesJournal()`; every journal row DTO carries all documented fields (`staff_id`, `store_id`, `pay_type`, `order_status`, `f_status`, `card_code`, debit/credit accounts …) |
| **Documents** | Typed `getProductOutTyped()`, `getProductOutSingleTyped()`, `getProductMoveTyped()`, `getCustomerReturnTyped()`, `getCustomerOrderTyped()`; raw getters for inventory out/move, customer inventory return, provided service, discount card; `saveDiscountCard()`, `saveGiftCard()`, `updateRsStatus()` |
| **Products** | `restByStore()`, `restAdvance()`, `subRest()`, `selfCost()`, `pricesAdvance()`, `images()`, `places()`, `characteristicValues()`, `characteristicValuesByProductIds()`, `packed()`, `subCodeTypes()`, `subCodes()`, `onWay()`, `additionalFields()`, `save()` |
| **Reference** | `storeGroups()`, `staffAdditionalFields()`, `docAdditionalFields($type)`, `accountValue()`, `saveStaff()`; `customers()->save()`, `vendors()->save()` |
| **New clients** | `services()` (provided/received service catalogs) and `inventories()` (fixed assets + rests) |
| **Tests** | Fixtures copied verbatim from the PDF for every journal, report and document; a read-only integration suite against a real server (`--testsuite Integration`) |

Row DTO constructors changed (new properties, `customer_name`/`vendor_name`/`user_id` that the API never returned are gone). Build rows with `fromArray()`; direct `new` calls need updating.

Not every FINA installation is on the latest build: endpoints added after your server's version answer **404** (`FinaHttpException` with status 404). The integration suite prints which endpoints your server has.

---

## What's New in v8.0

This release adds **18 new endpoints** and expands several existing ones:

| Area | New Capabilities |
|---|---|
| **Customers** | Agreements, sub-account fields, sub-accounts |
| **Vendors** | Sub-accounts |
| **Products** | Current rest, rest-after-date, rest summary, rest by store, provided service prices |
| **Loyalty** | Gift card info by code, bonus card rest by code |
| **Reference** | Transportation means, account value details |
| **Documents** | Save received service, bonus card, gift payment; get/save auto-service docs |
| **Reporting** | Auto services out journal (raw + typed + chunked), cafe order detailed report |
| **DTOs** | 17 new typed DTOs and payloads with full validation |

Existing endpoints are unchanged -- the upgrade is fully backwards-compatible.

---

## Features

| Feature | Description |
|---|---|
| **Auto-authentication** | Fetches, caches (~35h TTL), and transparently refreshes tokens on 401 |
| **Configurable HTTP** | Timeout, retry count, and retry delay via `.env` |
| **8 API clients** | Customers, Vendors, Products, Documents, Loyalty, Reference, Journals, Reporting |
| **Chunked reporting** | Splits large date ranges into windows, merges, drops operations already delivered by an earlier window (`id+version`), keeps every line of multi-line journals |
| **Typed DTOs** | `readonly` classes with `fromArray()` factories for all responses |
| **Validated payloads** | Request DTOs with Laravel validation rules, auto-validated before sending |
| **Structured exceptions** | Config, HTTP, Remote, and Validation exceptions with useful properties |
| **PHPStan level 6** | Full static analysis coverage |

---

## Requirements

- **PHP** >= 8.2
- **Laravel** >= 12.0
- A running **FINA Web API v8.0** instance

---

## Installation

### Via Composer

```bash
composer require giorgigrdzelidze/laravel-fina-sdk
```

Laravel auto-discovers the service provider. No manual registration needed.

### Local (path repository)

If using as a local path repository:

```json
{
    "repositories": [
        { "type": "path", "url": "packages/fina/fina-sdk-laravel" }
    ]
}
```

```bash
composer require giorgigrdzelidze/laravel-fina-sdk:@dev
```

---

## Configuration

### 1. Add environment variables

```dotenv
FINA_BASE_URL=https://your-fina-host:5007
FINA_LOGIN=your_login
FINA_PASSWORD=your_password
```

### 2. Publish config (optional)

```bash
php artisan vendor:publish --tag=fina-config
```

### Environment variable reference

| Variable | Default | Description |
|---|---|---|
| `FINA_BASE_URL` | *(required)* | FINA instance URL (scheme + host + port) |
| `FINA_LOGIN` | *(required)* | API login |
| `FINA_PASSWORD` | *(required)* | API password |
| `FINA_TIMEOUT` | `120` | HTTP timeout (seconds) |
| `FINA_RETRY_TIMES` | `2` | Retries on transient failure |
| `FINA_RETRY_SLEEP_MS` | `300` | Delay between retries (ms) |
| `FINA_TOKEN_TTL` | `126000` | Token cache lifetime (seconds, ~35h) |
| `FINA_DOC_TYPES_TTL` | `3600` | Doc-types cache lifetime (seconds) |

---

## Quick Start

### Get the client

```php
use Fina\Sdk\Laravel\Client\FinaClient;

// Via container
$fina = app('fina');

// Via injection
public function __construct(private FinaClient $fina) {}
```

### Fetch reference data

```php
$stores   = $fina->reference()->stores();
$users    = $fina->reference()->users();            // UserDto[]
$docTypes = $fina->reference()->docTypesCached();    // cached 1h
$vehicles = $fina->reference()->transportationMeans(); // v8.0
```

### Save a document

```php
use Fina\Sdk\Laravel\Operation\Dto\ProductOutPayload;
use Fina\Sdk\Laravel\Operation\Dto\ProductLine;

$response = $fina->documents()->saveProductOut(new ProductOutPayload(
    id: 0, date: now(), numPrefix: '', num: 0,
    purpose: 'Sale via API', amount: 100.0,
    currency: 'GEL', rate: 1.0,
    store: 1, user: 1, staff: 1, project: 0, customer: 8,
    isVat: true, makeEntry: true,
    payType: 1, wType: 2, tType: 1, tPayer: 1, wCost: 0, foreign: false,
    products: [new ProductLine(id: 2, subId: 0, quantity: 3, price: 33.33)],
    priceType: 3, // optional, FINA default 3 = retail
));

$response->id; // saved document ID
$response->ex; // null on success
```

### Query a report

```php
// Raw
$raw = $fina->reporting()->entriesJournal($from, $to);

// Typed
$dto = $fina->reporting()->entriesJournalTyped($from, $to);
$dto->journals; // EntriesJournalRowDto[]

// Chunked (splits large ranges, deduplicates automatically)
$dto = $fina->reporting()->entriesJournalChunkedTyped($from, $to, chunkDays: 7);
```

---

## API Reference

All methods are accessed through the `FinaClient` instance (`$fina`).

### Customers

Access via `$fina->customers()`.

| Method | FINA Endpoint | Returns |
|---|---|---|
| `all()` | `getCustomers` | `array` |
| `getByCode($code)` | `getCustomersByCode/{code}` | `array` |
| `groups()` | `getCustomerGroups` | `array` |
| `addresses()` | `getCustomerAddresses` | `array` |
| `additionalFields()` | `getCustomerAdditionalFields` | `array` |
| `agreements()` | `getCustomerAgreements` | `CustomerAgreementDto[]` |
| `subAccountFields()` | `getContragentSubAccountFields` | `ContragentSubAccountFieldDto[]` |
| `subAccounts()` | `getCustomerSubAccounts` | `ContragentSubAccountDto[]` |
| `save($payload)` | `saveCustomer` (POST) | `SaveDocResponse` |

> `agreements()`, `subAccountFields()`, `subAccounts()` are new in v8.0.

### Vendors

Access via `$fina->vendors()`.

| Method | FINA Endpoint | Returns |
|---|---|---|
| `all()` | `getVendors` | `array` |
| `getByCode($code)` | `getVendorsByCode/{code}` | `array` |
| `groups()` | `getVendorGroups` | `array` |
| `addresses()` | `getVendorAddresses` | `array` |
| `additionalFields()` | `getVendorAdditionalFields` | `array` |
| `subAccounts()` | `getVendorSubAccounts` | `ContragentSubAccountDto[]` |
| `save($payload)` | `saveVendor` (POST) | `SaveDocResponse` |

> `subAccounts()` is new in v8.0.

### Products

Access via `$fina->products()`.

| Method | FINA Endpoint | Returns |
|---|---|---|
| `all()` | `getProducts` | `array` |
| `groups()` | `getProductGroups` | `array` |
| `webGroups()` | `getWebProductGroups` | `array` |
| `byIds($ids)` | `getProductsArray` (POST) | `array` |
| `after($date)` | `getProductsAfter/{date}` | `array` |
| `prices()` | `getProductPrices` | `array` |
| `pricesAfter($date)` | `getProductPricesAfter/{date}` | `array` |
| `units()` | `getProductUnits` | `array` |
| `characteristics()` | `getCharacteristics` | `array` |
| `imagesByProductIds($ids)` | `getProductsImageArray` (POST) | `array` |
| `barcodesByProductIds($ids)` | `getProductsBarcodeArray` (POST) | `array` |
| `rest()` | `getProductsRest` | `array` |
| `restByProductIds($ids)` | `getProductsRestArray` (POST) | `array` |
| `restAfter($date)` | `getProductsRestAfter/{date}` | `array` |
| `restSummary($prods, $stores, $date)` | `getProductsRestSummary` (POST) | `array` |
| `restByStoreAfter($store, $date)` | `getProductsRestByStoreAfter/{store}/{date}` | `array` |
| `restByStore($store)` | `getProductsRestByStore/{store}` | `array` |
| `restAdvance($ids, $store = 0, $priceTypeId = 3)` | `getProductsRestAdvance` (POST) | `array` |
| `subRest()` | `getSubProductsRest` | `array` |
| `selfCost($ids, $date)` | `getProductsSelfCost` (POST) | `array` (`cost_info`) |
| `pricesAdvance($ids, $priceTypeId = 3)` | `getProductPricesAdvance` (POST) | `array` |
| `images($productId)` | `getProductImages/{product}` | `array` |
| `places()` | `getProductPlaces` | `array` |
| `characteristicValues()` | `getCharacteristicValues` | `array` |
| `characteristicValuesByProductIds($ids)` | `getCharacteristicValuesArray` (POST) | `array` |
| `packed()` | `getPackedProducts` | `array` |
| `subCodeTypes()` | `getSubCodeTypes` | `array` |
| `subCodes()` | `getProductSubCodes` | `array` |
| `onWay()` | `getProductsOnWay` | `array` |
| `additionalFields()` | `getProductAdditionalFields` | `AdditionalFieldDescriptorDto[]` |
| `providedServicePrices()` | `getProvidedServicePrices` | `array` |
| `save($payload)` | `saveProduct` (POST) | `SaveDocResponse` |

> `getProductsRest.rest` is already net of `reserve`. `restSummary()` is the stock at a given date; `selfCost()` the average cost at a given date.

### Reference Data

Access via `$fina->reference()`.

**Raw endpoints:**

| Method | FINA Endpoint |
|---|---|
| `stores()` | `getStores` |
| `projects()` | `getProjects` |
| `terminals()` | `getTerminals` |
| `cashes()` | `getCashes` |
| `creditBanks()` | `getCreditBanks` |
| `priceTypes()` | `getPriceTypes` |
| `customers()` | `getCustomers` (returns `contragents[]`) |
| `vendors()` | `getVendors` (returns `contragents[]`) |
| `customerGroups()` | `getCustomerGroups` |
| `vendorGroups()` | `getVendorGroups` |
| `productGroups()` | `getProductGroups` |
| `webProductGroups()` | `getWebProductGroups` |
| `providedServiceGroups()` | `getProvidedServiceGroups` |
| `receivedServiceGroups()` | `getReceivedServiceGroups` |
| `inventoryGroups()` | `getInventoryGroups` |
| `storeGroups()` | `getStoreGroups` |

**Typed endpoints:**

| Method | Returns |
|---|---|
| `users()` | `UserDto[]` |
| `userPermissions($id)` | `UserPermissionsDto` |
| `bankAccounts()` | `BankAccountDto[]` |
| `staffGroups()` | `StaffGroupDto[]` |
| `staffs()` | `StaffDto[]` |
| `giftCards()` | `GiftCardDto[]` |
| `discountTypes()` | `DiscountTypeDto[]` |
| `units()` | `UnitDto[]` |
| `docTypes()` | `DocTypeDto[]` |
| `docTypesCached()` | `DocTypeDto[]` (cached) |
| `supportedDocTypes()` | `DocTypeDto[]` (filtered) |
| `findDocType($id)` | `?DocTypeDto` |
| `transportationMeans()` | `TransportationMeanDto[]` |
| `accountValueDetails($account, $date, $currency?)` | `AccountValueDetailDto[]` (POST) |
| `accountValue($primaryAcc, $date, $obj = 0, $secondaryAcc = '', $isGel = true)` | `AccountValueDto` (POST) |
| `staffAdditionalFields()` | `AdditionalFieldDescriptorDto[]` |
| `docAdditionalFields($docType)` | `AdditionalFieldDescriptorDto[]` |
| `saveStaff($payload)` | `SaveDocResponse` (POST `saveStaff`) |

> `transportationMeans()` and `accountValueDetails()` are new in v8.0.

### Services

Access via `$fina->services()` — the service *catalogs*; service documents live under `documents()`.

| Method | FINA Endpoint | Returns |
|---|---|---|
| `provided()` | `getProvidedServices` | `array` |
| `received()` | `getReceivedServices` | `array` |
| `providedAdditionalFields()` | `getProvidedServiceAdditionalFields` | `AdditionalFieldDescriptorDto[]` |
| `providedPrices()` | `getProvidedServicePrices` | `array` |
| `saveProvided($payload)` | `saveProvidedService` (POST) | `SaveDocResponse` |

### Inventories

Access via `$fina->inventories()` — fixed assets (ძირითადი საშუალებები). Groups are `reference()->inventoryGroups()`.

| Method | FINA Endpoint | Returns |
|---|---|---|
| `all()` | `getInventories` | `array` |
| `additionalFields()` | `getInventoryAdditionalFields` | `AdditionalFieldDescriptorDto[]` |
| `rest()` | `getInventoriesRest` | `array` |
| `restByIds($ids)` | `getInventoriesRestArray` (POST) | `array` |
| `restByStore($store)` | `getInventoriesRestByStore/{store}` | `array` |
| `restAdvance($ids, $store = 0, $priceTypeId = 3)` | `getInventoriesRestAdvance` (POST) | `array` |

### Loyalty

Access via `$fina->loyalty()`.

| Method | FINA Endpoint | Returns |
|---|---|---|
| `bonusCoeff()` | `getBonusCoeff` | `BonusCoeffResponse` |
| `cardsByHolder($code)` | `getLoyaltyCardsByHolder/{code}` | `array` |
| `saveBonusOperation($payload)` | `saveDocBonusOperation` | `BonusOperationResponse` |
| `giftCardInfoByCode($code)` | `getGiftCardInfoByCode/{code}` | `GiftCardInfoDto` |
| `bonusCardRestByCode($code)` | `getBonusCardRestByCode/{code}` | `array` |

> Last 2 methods are new in v8.0.

### Documents

Access via `$fina->documents()`.

**Save methods** -- all return `SaveDocResponse` with `->id` and `->ex`:

| Method | FINA Endpoint | Payload DTO |
|---|---|---|
| `saveCustomerOrder($p)` | `saveDocCustomerOrder` | `CustomerOrderPayload` |
| `saveProductOut($p)` | `saveDocProductOut` | `ProductOutPayload` |
| `saveProductIn($p)` | `saveDocProductIn` | `ProductInPayload` |
| `saveProductMove($p)` | `saveDocProductMove` | `ProductMovePayload` |
| `saveProductCancel($p)` | `saveDocProductCancel` | `ProductCancelPayload` |
| `saveProvidedService($p)` | `saveDocProvidedService` | `ProvidedServicePayload` |
| `saveCustomerReturn($p)` | `saveDocCustomerReturn` | `CustomerReturnPayload` |
| `saveCafeOrder($p)` | `saveDocCafeOrder` | `CafeOrderPayload` |
| `saveCustomerMoneyIn($p)` | `saveDocCustomerMoneyIn` | `CustomerMoneyInPayload` |
| `saveCustomerAdvanceIn($p)` | `saveDocCustomerAdvanceIn` | *(raw array)* |
| `saveCustomerMoneyReturn($p)` | `saveDocCustomerMoneyReturn` | `CustomerMoneyReturnPayload` |
| `saveBonusPayment($p)` | `saveDocBonusPayment` | `BonusPaymentPayload` |
| `saveCustomerMoneyOut($p)` | `saveDocCustomerMoneyOut` | `CustomerMoneyOutPayload` |
| `saveVendorMoneyIn($p)` | `saveDocVendorMoneyIn` | `VendorMoneyInPayload` |
| `saveVendorMoneyOut($p)` | `saveDocVendorMoneyOut` | `VendorMoneyOutPayload` |
| `saveVendorMoneyReturn($p)` | `saveDocVendorMoneyReturn` | `VendorMoneyReturnPayload` |
| `saveProduction($p)` | `saveDocProduction` | `SaveProductionPayload` / `ProductionPayload` |
| `saveReceivedService($p)` | `saveDocReceivedService` | `ReceivedServicePayload` |
| `saveBonusCard($p)` | `saveDocBonusCard` | `BonusCardPayload` |
| `saveGiftPayment($p)` | `saveDocGiftPayment` | `GiftPaymentPayload` |
| `saveDiscountCard($p)` | `saveDocDiscountCard` | *(raw array)* |
| `saveGiftCard($p)` | `saveDocGiftCard` | *(raw array)* |
| `updateRsStatus($operationId, $waybillId, $waybillStatus, $waybillNum)` | `updateRsStatus` | returns `RsStatusResponse` |

> `saveReceivedService`, `saveBonusCard`, `saveGiftPayment` are new in v8.0. `saveCustomerMoneyOut`, `saveVendorMoneyIn`, `saveVendorMoneyOut`, `saveVendorMoneyReturn` are **not documented** in the 8.0 PDF; they are kept for compatibility but unverified.

**Get methods** -- return raw arrays or typed DTOs:

| Method | FINA Endpoint | Returns |
|---|---|---|
| `getCustomerOrder($id)` | `getDocCustomerOrder/{id}` | `array` |
| `getProductOut($id)` | `getDocProductOut/{id}` | `array` |
| `getProductOutSingle($id)` | `getDocProductOutSingle/{id}` | `array` |
| `getProductMove($id)` | `getDocProductMove/{id}` | `array` |
| `getReceivedService($id)` | `getDocReceivedService/{id}` | `array` |
| `getCustomerReturn($id)` | `getDocCustomerReturn/{id}` | `array` |
| `getProduction($id)` | `getDocProduction/{id}` | `array` |
| `getProductionTyped($id)` | `getDocProduction/{id}` | `ProductionDocDto` |
| `getAutoService($id)` | `getDocAutoService/{id}` | `array` |
| `getAutoServiceTyped($id)` | `getDocAutoService/{id}` | `AutoServiceDocDto` |
| `getCustomerOrderTyped($id)` | `getDocCustomerOrder/{id}` | `CustomerOrderDocDto` |
| `getProductOutTyped($id)` | `getDocProductOut/{id}` | `ProductOutDocDto` |
| `getProductOutSingleTyped($id)` | `getDocProductOutSingle/{id}` | `ProductOutSingleDocDto` |
| `getProductMoveTyped($id)` | `getDocProductMove/{id}` | `ProductMoveDocDto` |
| `getCustomerReturnTyped($id)` | `getDocCustomerReturn/{id}` | `CustomerReturnDocDto` |
| `getInventoryOut($id)` | `getDocInventoryOut/{id}` | `array` |
| `getInventoryMove($id)` | `getDocInventoryMove/{id}` | `array` |
| `getCustomerInventoryReturn($id)` | `getDocCustomerInventoryReturn/{id}` | `array` |
| `getProvidedService($id)` | `getDocProvidedService/{id}` | `array` |
| `getDiscountCard($id)` | `getDocDiscountCard/{id}` | `array` |

> `getDocProductOut` / `getDocProductOutSingle` answer **400** until the document is verified in FINA. Typed document DTOs expose `products` as `DocProductLineDto[]` (`price`, `selfCost`, `outId` are nullable because each document type sends a different subset) and keep the full response in `raw`.

**Generic methods** for any endpoint:

```php
// Any save endpoint
$fina->documents()->save('saveDocCustomerOrder', $payload);

// Any get endpoint
$fina->documents()->getDoc('getDocProductOut', $id);
```

### Reporting

The Reporting API provides three access patterns for every endpoint:

| Pattern | Suffix | Description |
|---|---|---|
| **Raw** | *(none)* | Returns the decoded JSON array as-is |
| **Typed** | `Typed` | Maps response into typed DTO objects |
| **Chunked + Typed** | `ChunkedTyped` | Splits large ranges, merges, deduplicates per operation across chunks, returns typed DTOs |

Access via `$fina->reporting()`.

**Journals:**

| Journal | Raw | Typed | Chunked Typed | Response DTO |
|---|---|---|---|---|
| Entries | `entriesJournal()` | `entriesJournalTyped()` | `entriesJournalChunkedTyped()` | `EntriesJournalResponseDto` |
| Customers Orders | `customersOrderJournal()` | `customersOrderJournalTyped()` | `customersOrderJournalChunkedTyped()` | `CustomersOrderJournalResponseDto` |
| Customers Returns | `customersReturnJournal()` | `customersReturnJournalTyped()` | `customersReturnJournalChunkedTyped()` | `CustomersReturnJournalResponseDto` |
| Customers Money | `customersMoneyJournal()` | `customersMoneyJournalTyped()` | `customersMoneyJournalChunkedTyped()` | `MoneyJournalResponseDto` |
| Vendors Money | `vendorsMoneyJournal()` | `vendorsMoneyJournalTyped()` | `vendorsMoneyJournalChunkedTyped()` | `MoneyJournalResponseDto` |
| Productions | `productionsJournal()` | `productionsJournalTyped()` | `productionsJournalChunkedTyped()` | `ProductionsJournalResponseDto` |
| Discount Cards | `discountCardsJournal()` | `discountCardsJournalTyped()` | `discountCardsJournalChunkedTyped()` | `DiscountCardsJournalResponseDto` |
| Provided Services | `providedServicesJournal()` | `providedServicesJournalTyped()` | `providedServicesJournalChunkedTyped()` | `ProvidedServicesJournalResponseDto` |
| Received Services | `receivedServicesJournal()` | `receivedServicesJournalTyped()` | `receivedServicesJournalChunkedTyped()` | `ReceivedServicesJournalResponseDto` |
| Realizes | `realizesJournal()` | `realizesJournalTyped()` | `realizesJournalChunkedTyped()` | `RealizesJournalResponseDto` |
| Moves | `movesJournal()` | `movesJournalTyped()` | `movesJournalChunkedTyped()` | `MovesJournalResponseDto` |
| Auto Services Out | `autoServicesOutJournal()` | `autoServicesOutJournalTyped()` | `autoServicesOutJournalChunkedTyped()` | `AutoServicesOutJournalResponseDto` |

> Auto Services Out is new in v8.0.

**Reports:**

| Report | Raw | Typed | Response DTO |
|---|---|---|---|
| Customers Cycle | `customersCycleReport()` | `customersCycleReportTyped()` | `CycleReportResponseDto` |
| Vendors Cycle | `vendorsCycleReport()` | `vendorsCycleReportTyped()` | `CycleReportResponseDto` |
| Products Last-In | `productsLastInReport()` | `productsLastInReportTyped()` | `ProductsLastInReportResponseDto` |
| Products In/Return | `productsInReturnReport()` | `productsInReturnReportTyped()` | `ProductsInReturnReportResponseDto` |
| Cafe Order Detailed | `cafeOrderDetailedReport()` | `cafeOrderDetailedReportTyped()` | `CafeOrderDetailedReportResponseDto` |

> Cafe Order Detailed is new in v8.0.

**Low-level Journals API** -- access via `$fina->journals()` for simple date-range queries:

```php
$fina->journals()->entries($from, $to);
$fina->journals()->realizes($from, $to);
$fina->journals()->moves($from, $to);
$fina->journals()->customersOrders($from, $to);
$fina->journals()->autoServicesOut($from, $to); // v8.0
```

**Generic range call:**

```php
$fina->reporting()->getRange('getEntriesJournal', $from, $to);

$fina->reporting()->getRangeChunked(
    method: 'getEntriesJournal',
    collectionKey: 'journals',
    from: $from,
    to: $to,
    chunkDays: 7,
    dedupeKeyFn: fn (array $item) => $item['id'] . ':' . $item['version'],
);
```

Chunk windows touch at their boundary second, so an operation dated exactly on a boundary can be delivered twice. The merge keeps every row of the chunk it first appeared in and skips the whole operation when a later chunk re-sends it — rows are never deduplicated inside one window, which is what keeps every line of `getEntriesJournal`.

---

## DTOs & Validation

### Contracts

Request DTOs implement two interfaces:

```
ArrayPayload          -- toArray() for serialisation
    |
ValidatesPayload      -- adds rules(), messages(), attributes()
```

When you pass a `ValidatesPayload` to any `save*` method, it is validated automatically before the HTTP request. On failure, a `FinaValidationException` is thrown -- no request is made.

### Example: save with validation

```php
use Fina\Sdk\Laravel\Operation\Dto\BonusCardPayload;
use Carbon\CarbonImmutable;

$payload = new BonusCardPayload(
    id: 0,
    date: CarbonImmutable::now(),
    numPrefix: '',
    num: 0,
    purpose: 'New bonus card',
    customer: 31,
    store: 1,
    user: 1,
    cardCode: '231',
    personCode: '0100010101',
    personName: 'John Doe',
    personAddress: '123 Main St',
    personTel: '+995597222222',
    status: true,
);

$response = $fina->documents()->saveBonusCard($payload);
```

### Example: manual validation

```php
use Fina\Sdk\Laravel\Support\PayloadValidator;
use Fina\Sdk\Laravel\Exceptions\FinaValidationException;

try {
    PayloadValidator::validate($payload);
} catch (FinaValidationException $e) {
    // $e->errors -- ['field_name' => ['Error message', ...]]
}
```

### Response DTOs

All typed response DTOs use `readonly` classes with `fromArray()` factories. Reporting row DTOs also preserve the full raw array for forward-compatibility:

```php
$dto = $fina->reporting()->autoServicesOutJournalTyped($from, $to);

foreach ($dto->journals as $row) {
    $row->id;         // typed property
    $row->amount;     // typed property
    $row->raw;        // full original array (access future fields)
    $row->dedupeKey(); // stable key for chunk merging
}
```

### Complete DTO list

**Response DTOs (Operation):**

| DTO | Purpose |
|---|---|
| `SaveDocResponse` | Generic save response (`id`, `ex`) |
| `BonusOperationResponse` | Bonus operation result (`res`, `ex`) |
| `BonusCoeffResponse` | Bonus coefficient (`coeff`, `ex`) |
| `UserDto` | User reference data |
| `UserPermissionsDto` | User permissions and defaults |
| `DocTypeDto` | Document type reference |
| `BankAccountDto` | Bank account reference |
| `StaffDto` | Staff member with additional fields |
| `StaffGroupDto` | Staff group hierarchy |
| `StaffAdditionalFieldDto` | Staff field key-value |
| `DiscountTypeDto` | Discount type with percent |
| `UnitDto` | Measurement unit |
| `GiftCardDto` | Gift card (includes `restAmount` in v8.0) |
| `GiftCardInfoDto` | Detailed gift card info by code (v8.0) |
| `CustomerAgreementDto` | Customer price agreement (v8.0) |
| `ContragentSubAccountFieldDto` | Sub-account field definition (v8.0) |
| `ContragentSubAccountDto` | Contragent sub-account data (v8.0) |
| `TransportationMeanDto` | Vehicle/transportation mean (v8.0) |
| `AccountValueDetailDto` | Account debit/credit detail (v8.0) |
| `AutoServiceDocDto` | Full auto-service document (v8.0) |
| `ProductionDocDto` | Production document with materials |
| `ProductOutDocDto` | Sale document (type 21) with `checkStatus`, typed lines |
| `ProductOutSingleDocDto` | Retail sale document (type 23) |
| `ProductMoveDocDto` | Internal move (type 20), cost-only lines |
| `CustomerReturnDocDto` | Customer return (type 9), lines carry `outId` |
| `CustomerOrderDocDto` | Customer order (type 8) |
| `DocProductLineDto` / `DocServiceLineDto` | Lines of the typed documents |
| `AdditionalFieldDescriptorDto` | `{name, header}` of every `*AdditionalFields` endpoint |
| `AccountValueDto` | Debit/credit totals of `getAccountValue` |
| `RsStatusResponse` | Result of `updateRsStatus` |
| `RealizesJournalRowDto` / `MovesJournalRowDto` | Rows of the sales and moves journals |

**Payload DTOs (Operation):**

| DTO | Validates | Purpose |
|---|---|---|
| `CustomerOrderPayload` | -- | Customer order |
| `ProductOutPayload` | Yes | Product out (sale/shipment) |
| `ProductInPayload` | Yes | Product in (purchase) |
| `ProductMovePayload` | Yes | Inter-store transfer |
| `ProductCancelPayload` | Yes | Product cancellation |
| `ProvidedServicePayload` | Yes | Provided service |
| `CustomerReturnPayload` | Yes | Customer return |
| `CafeOrderPayload` | Yes | Cafe order |
| `CustomerMoneyInPayload` | -- | Customer money in |
| `CustomerMoneyReturnPayload` | Yes | Customer money return |
| `BonusPaymentPayload` | Yes | Bonus payment |
| `BonusOperationPayload` | Yes | Bonus accumulate/spend |
| `CustomerMoneyOutPayload` | Yes | Customer money out |
| `VendorMoneyInPayload` | Yes | Vendor money in |
| `VendorMoneyOutPayload` | Yes | Vendor money out |
| `VendorMoneyReturnPayload` | Yes | Vendor money return |
| `ProductionPayload` | Yes | Production (legacy format) |
| `ReceivedServicePayload` | Yes | Received service (v8.0) |
| `BonusCardPayload` | Yes | Bonus card issuance (v8.0) |
| `GiftPaymentPayload` | Yes | Gift card payment (v8.0) |
| `SaveProductionPayload` | Yes | Production with product tree (v8.0) |

**Line item DTOs:**

| DTO | Used in |
|---|---|
| `ProductLine` | Product out, in, move, cancel, return |
| `ServiceLine` | Provided/received service |
| `CafeOrderProductLine` | Cafe order |
| `CustomerOrderProduct` | Customer order |
| `AddField` | Additional custom fields |
| `ProductionChildLine` | Production child product (v8.0) |
| `ProductionProductLine` | Production product with children (v8.0) |
| `ProductionMaterialLineDto` | Production material |
| `ProductionConsumedLineDto` | Consumed material |
| `ProductionExpenseLineDto` | Production expense |

---

## Error Handling

All exceptions extend `FinaException` (a `RuntimeException`):

```
FinaException
  ├── FinaConfigException      -- missing base_url, login, or password
  ├── FinaHttpException        -- HTTP 4xx/5xx ($status, $body)
  ├── FinaRemoteException      -- FINA returned non-null 'ex' ($ex)
  └── FinaValidationException  -- payload failed validation ($errors)
```

### Usage

```php
use Fina\Sdk\Laravel\Exceptions\FinaException;
use Fina\Sdk\Laravel\Exceptions\FinaHttpException;
use Fina\Sdk\Laravel\Exceptions\FinaRemoteException;
use Fina\Sdk\Laravel\Exceptions\FinaValidationException;

try {
    $fina->documents()->saveProductOut($payload);
} catch (FinaValidationException $e) {
    // No HTTP request was made
    return response()->json(['errors' => $e->errors], 422);
} catch (FinaHttpException $e) {
    Log::error("FINA HTTP {$e->status}", ['body' => $e->body]);
} catch (FinaRemoteException $e) {
    Log::error('FINA remote error', ['ex' => $e->ex]);
} catch (FinaException $e) {
    Log::error("FINA: {$e->getMessage()}");
}
```

### 401 auto-refresh

When a request gets a `401`, the SDK automatically:

1. Clears the cached token
2. Fetches a fresh token
3. Retries the request **once**

If the retry also fails, a `FinaHttpException` is thrown.

### Retries and writes

`retry_times` / `retry_sleep_ms` apply to reads and query POSTs only. A write (`save*` / `update*`) is sent once: if it times out or gets a 5xx, FINA may already have saved it, and repeating it would create a second document. Look the document up in the matching journal before sending it again. `FinaClient::isWrite($method, $url)` tells the two apart.

---

## Performance

- **Use chunked reporting for ranges > 30 days.** FINA may time out on large ranges. Chunked methods handle this transparently.
- **Recommended chunk sizes:** 7 days for entries (high volume), 14 days for money/order journals.
- **Deduplication** uses `id+version` when available, falls back to `id`, then SHA-1 hash.
- **Cache doc types** with `docTypesCached()` to avoid repeated calls for rarely-changing data.
- **Token caching** is ~35h by default. Adjust `FINA_TOKEN_TTL` for your instance.

---

## Testing & Quality

All tests use `Http::fake()` -- no real HTTP, no database, no external services.

```bash
# Run tests
composer test

# Static analysis (PHPStan level 6)
composer phpstan -- --memory-limit=512M

# Code style (Laravel Pint)
composer pint -- --test

# All three
composer ci
```

### Test coverage

| Suite | Tests | Assertions | Coverage |
|---|---|---|---|
| `ServiceProviderTest` | 4 | | Singleton, alias, config, accessors |
| `FinaClientHttpTest` | 5 | | Token cache, reuse, 401 refresh, HTTP errors |
| `ReportingUrlTest` | 3 | | Date format, URL structure |
| `ReportingChunkingTest` | 4 | | Chunk merge, deduplication, typed output |
| `DtoValidationTest` | 5 | | Valid/invalid payloads, nested validation |
| `DtoMappingTest` | 14 | | DTO mapping, empty data, roundtrip |
| `NewEndpointsTest` | 16 | | All v8.0 endpoints (HTTP mocked) |
| `NewDtoMappingTest` | 12 | | All v8.0 DTO mapping + edge cases |
| `NewDtoValidationTest` | 8 | | v8.0 payload validation (valid + invalid) |
| **Total** | **75** | **207** | |

### Live integration suite (read-only)

`tests/Integration/LiveFinaReadOnlyTest.php` runs against a real server when credentials are in the environment and is skipped otherwise:

```bash
FINA_LIVE_BASE_URL=http://host:5007 FINA_LIVE_LOGIN=... FINA_LIVE_PASSWORD=... \
  vendor/bin/phpunit --testsuite Integration --display-incomplete
```

It only reads (GET and query-style POST endpoints); a request middleware aborts the run if any URL contains `/save` or `/update`. For every endpoint it checks that all keys documented in the 8.0 PDF are present in the live response and prints which endpoints your FINA build answers with 404.

### CI

GitHub Actions on every push/PR:

- **Matrix:** PHP 8.2, 8.3, 8.4
- **Steps:** tests -> PHPStan -> Pint
- **No external services required**

---

## Troubleshooting

### 401 loops

```php
// Verify credentials, then clear token cache:
app('fina')->auth()->forgetToken();
```

### Base URL format

```dotenv
# Correct
FINA_BASE_URL=https://your-fina-host:5007

# Wrong (no scheme)
FINA_BASE_URL=your-fina-host:5007

# Wrong (includes path)
FINA_BASE_URL=https://your-fina-host:5007/api
```

### Reporting timeouts

```php
// Switch to chunked:
$fina->reporting()->entriesJournalChunkedTyped($from, $to, chunkDays: 7);

// Or increase timeout:
// FINA_TIMEOUT=300
```

### Config exception on boot

Ensure `.env` contains `FINA_BASE_URL`, `FINA_LOGIN`, and `FINA_PASSWORD`. The SDK validates these when the client is first resolved.

---

## License

[MIT License](LICENSE)

---

## Contributing

Contributions welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

For security vulnerabilities, see [SECURITY.md](SECURITY.md).
