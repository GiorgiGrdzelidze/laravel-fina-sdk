# Changelog

All notable changes to this project will be documented in this file.

## [0.3.0] - 2026-11-27

Alignment with the FINA WEB API 10.0 documentation (10.0.20261106). All 154 documented methods are reachable, 16 of them new; `PdfParityTest` now checks the 10.0 table of contents. A payload built without the new fields sends the same bytes as 0.2.5 (pinned by a test against output captured from 0.2.5). None of the new write methods (`saveLoyaltyCustomer`, `saveLoyaltyGroup`, `saveLoyaltyBonusPoint`, `saveDocAdvProduction`, `saveDocDiscountOperation`, `fulfillmentCustomerOrder`) was called against a real FINA; they are tested against the document's examples only.

### Added
- `tenant_key` (10.0 request headers, MultiTenant version): new config key `fina.tenant_key` / `FINA_TENANT_KEY`. When set it is sent as the `tenant_key` header on every request, `authenticate` included, and becomes part of the token and doc-types cache keys (the doc-types key takes it from the client that reads), so two tenants behind one URL and login never share a token or doc types. Empty (the default): no header, and both cache keys stay what they were. A key holding a control character (CR, LF, NUL, any byte below 0x20, or 0x7F) is refused when the client is built, with a `FinaConfigException` that does not repeat the key.
- `authenticate` → `valid_to` (in the 10.0 document, no change-log row): `AuthTokenResponse::$validTo` and `expiresAt()`. The token is cached until one hour before `valid_to` and never longer than `token_cache_ttl_seconds`. A value without a time zone (as documented) is read as UTC, which is what a real FINA sends: `valid_to` `2026-11-28T10:54:07` came in an answer whose `Date` header was `Thu, 26 Nov 2026 22:54:06 GMT`, 36 hours earlier; a value with its own offset or `Z` keeps it. A `valid_to` already past or less than an hour away keeps the token 60 seconds (at most `token_cache_ttl_seconds`), so the next call after that logs in again; a missing or unreadable one keeps the fixed lifetime. `TokenStore::put()` takes it as an optional second argument; new `TokenStore::ttlFor()`.
- Loyalty (Cloud), 10.0.20260713: `LoyaltyApi::customers()`, `customer($id)`, `customerPrograms()`, `customerGroups()`, `customerBalances()`, `customerBalance($qrCode)`, `bonusHistory($qrCode)` with `Loyalty\LoyaltyCustomerDto` (+ `LoyaltyCustomerProgramDto`, `LoyaltyCustomerGroupDto`), `LoyaltyProgramDto`, `LoyaltyGroupDto`, `LoyaltyBalanceDto`, `LoyaltyBonusHistoryDto` (+ `LoyaltyBonusProductDto`); `saveCustomer()`, `saveGroup()`, `saveBonusPoint()` with `LoyaltyCustomerPayload` (+ `LoyaltyCustomerProgramLine`, `LoyaltyCustomerGroupLine`), `LoyaltyGroupPayload`, `LoyaltyBonusPointPayload`.
- 10.0.20261009: `DocumentsApi::fulfillCustomerOrder()` (`fulfillmentCustomerOrder`, `CustomerOrderFulfillmentPayload`, answers the new sale's id); `LoyaltyApi::saveDiscountOperation()` (`saveDocDiscountOperation`, `DiscountOperationPayload`, `DiscountOperationResponse` with `res`, null when the answer has none, and the whole answer in `raw`); `ServicesApi::consumptionMaterials()` (`getServiceConsumptionMaterials`, `ServiceConsumptionDto`, `ServiceConsumptionLineDto`); `saveProduct` prices: `ProductPriceLine` with `currency`, `discountValue`, `discountStart`, `discountEnd`.
- 10.0.20260625: `DocumentsApi::getAdvProduction()`, `getAdvProductionTyped()` (`AdvProductionDocDto`, `AdvProductionLineDto`) and `saveAdvProduction()` (`AdvProductionPayload`, `AdvProductionLine`).
- 10.0.20261106: `waybillStatus` on `ProductOutDocDto`, `ProductMoveDocDto`, `CustomerReturnDocDto`, `AutoServiceDocDto`; `Documents\WaybillStatus` constants (-1 none, 0 saved, 1 activated, 2 completed, 8 with carrier, -2 cancelled). The raw inventory getters pass it through unchanged.
- 10.0.20261027: `ReferenceApi::storesTyped()` (`StoreDto` with `addFields`) and `storeAdditionalFields()`; `refId` on `RealizesJournalRowDto` and `MoneyJournalRowDto` (in `toArray()` too).
- 8.0.20260511 (first listed in the 10.0 document) and undated fields: `CustomersApi` / `VendorsApi` `allTyped()` and `getByCodeTyped()` with `ContragentDto` (`birthDate`, `marketingPromotions`, `addFields`); `userId` and `guestCount` on `CafeOrderDetailedReportRowDto`.
- Typed payloads for saves that only took arrays: `ContragentPayload` (`saveCustomer` / `saveVendor`, incl. `marketing_promotions`), `ProductPayload` (`saveProduct`; `barcodes` / `images` are sent only when given, because a non-null list replaces the product's), `DiscountCardPayload` (`saveDocDiscountCard`, incl. the 10.0 `person_*` fields; without them the body is the 8.0 one). Arrays are still accepted everywhere.
- Request fields without a change-log row: `ProductOutPayload::$giftOverlap` (`gift_overlap`, 0/1; 1 is refused unless `pay_type` is 5, the only pay type the document says it acts on, and 0 is accepted on any pay type, as the document's own `saveDocProductOut` example sends it with `pay_type` 1); `ServiceLine::$consumedMaterials` (`ConsumedMaterialLine[]`, sent as `consumed_materials`, accepted on `ProvidedServicePayload` only and refused on the sale, received-service and cafe payloads, an empty list included). Every new field is the last constructor argument with a null default and is left out of the JSON when null.

### Changed
- A field added to an existing response that a FINA build does not send (`waybill_status`, `ref_id`, `birth_date`, `marketing_promotions`, `user_id`, `guest_count`) maps to `null`, not 0 or false: an older server must not read as "saved", "not from an order" or "no consent". The DTOs new in this release do the same for every field the answer lacks, never 0, false, `''` or `[]`; only the id of a document line or of a reference row (store, contragent, program, group, enrolment) stays an `int`, as in the older DTOs; the id of a loyalty card or balance is null when not sent, in a list too, and a `field` or `value` missing inside `addFields` of `StoreDto` / `ContragentDto` is null. So `DiscountOperationResponse::$res` is null on an answer without `res` (not "not applied"), a `LoyaltyCustomerDto` from `customers()` has `loyaltyPrograms` / `loyaltyGroups` null (saved back through `LoyaltyCustomerPayload`, they are not sent; the document applies insert/update/delete only to a non-null list), `StoreDto` / `ContragentDto` `addFields` are null rather than empty, and `customer()`, `customerBalance()` and `getAdvProductionTyped()` answer a missing object with every field null, the id included.
- `ProductMovePayload` validates `products.*.price` (≥ 0), the retail price 10.0 documents for moves at sale prices; `ProductLine` already sent it when set.
- `PdfParityTest` checks the 154 methods of the 10.0 table of contents instead of the 138 of 8.0, and also scans `fulfillment*` endpoints.
- composer description and README name API 10.0.

### Fixed
- `fulfillmentCustomerOrder` is a write: `FinaClient::isWrite()` now also treats `fulfillment*` as one, so the transport never repeats it (a repeat would turn one order into two sales), and the live suite's guard refuses it like `/save` and `/update`, in any letter case.

## [0.2.5] - 2026-11-09

### Changed
- `ProductOutPayload::$customer` must be ≥ 1 again. A real `saveDocProductOut` with customer 0 came back HTTP 400 `{"errors":{"CustomerId":["The field customer is invalid."]}}` (nothing was saved), although FINA's own till books retail sales to customer 0. Refusing it before sending keeps a wrong setting from reaching FINA. Walk-in sales need a customer of their own.

## [0.2.4] - 2026-11-09

### Changed
- `ProductOutPayload::$customer` may be 0. FINA's own till books every retail sale to customer 0 ("საცალო გაყიდვების ტრანზიტული ანგარიში"; the `getRealizesJournal` example in the 8.0 PDF, p. 196, shows a type-23 sale with `customer_id` 0), and the PDF puts no lower bound on `customer`. Negative ids are still refused. The other payloads keep `customer ≥ 1` until FINA's answer to a 0 is known.

## [0.2.3] - 2026-11-08

### Added
- `FinaRemoteException::$response`: the whole decoded body of the answer that carried `ex`. A save that FINA answers with an `id` and an `ex` at once can now be told apart from a refusal (`id` 0), so a caller never sends a saved document a second time. The constructor's new third argument defaults to `[]`; existing callers are unchanged.

## [0.2.2] - 2026-11-07

### Fixed
- Writes (`save*` / `update*`) are no longer repeated by the HTTP transport. With `retry_times` above 0 every request went through Laravel's `retry()`, so a `saveDoc*` that timed out or got a 5xx after FINA had already saved it was posted again and created a second document. Reads and query POSTs (`getProductsArray` and the like) are still retried. A 401 on a write still refreshes the token and resends once: FINA did not process the request. New `FinaClient::isWrite()`.
- `staff` may be 0 on every document payload, as the PDF documents it (0 = the user's linked staff). `CustomerReturnPayload`, `CustomerMoneyReturnPayload`, the money payloads, `ProductMovePayload`, `ProductCancelPayload`, `BonusPaymentPayload` and `ProvidedServicePayload` required ≥ 1.
- `ProductOutPayload` refuses `w_type` other than 2/3, `t_type` outside 1/2/3/4/6/7 and `t_payer` other than 1/2 before sending — FINA answers these with HTTP 400 even on a sale without a waybill. `CustomerReturnPayload` checks `t_type` / `t_payer` the same way (its rule list also had them twice).

## [0.2.1] - 2026-11-07

### Added
- `ProductOutPayload::$priceType` (sent as `price_type`, the price type the sale is priced by). The 8.0 PDF documents it for `saveDocProductOut` with default 3 (retail); the payload never sent it. It is the last constructor argument with default 3, so existing named and positional callers are unchanged, and it must be ≥ 1.

## [0.2.0] - 2026-11-06

Alignment with the FINA WEB API 8.0 documentation (8.0.20260303). All 138 documented methods are reachable; `tests/Unit/PdfParityTest.php` fails if one goes missing.

### Fixed
- `ReportingApi::getRangeChunked()` dropped every entry line but the first of each document: `getEntriesJournal` returns one row per line and all lines share `id` + `version`, which was the row dedupe key. Deduplication is now per operation and only across chunk windows; rows inside one window are never collapsed. `entriesJournalChunked()` / `entriesJournalChunkedTyped()` now return every line.
- `ProductsInReturnReportRowDto` mapped fields the API never sends (`name`, `barcode`, `in_qty`, `ret_qty`, `diff_qty`); it now carries the documented `date, doc_num, doc_type, vendor_id, store_id, product_id, unit_id, quantity, amount, vat`.
- `ProductsLastInReportRowDto` read `name`, `barcode`, `date` (absent); now `id, quantity, price, vendor_id, currency, rate, waybill_date, waybill_num, purpose`.
- `ProductionsJournalRowDto` read `user_id` (absent) and dropped `production_type`.
- Journal row DTOs no longer read `customer_name` / `vendor_name` (absent) and now carry every documented field: `EntriesJournalRowDto` (waybill_num, amount_currency, currency, staff_id, debit/credit acc + comment + quantity, project_id), `DiscountCardsJournalRowDto` (staff_id, customer, store_id, card_code, discount_id, status), `ProvidedServicesJournalRowDto` (staff_id, f_status, pay_type), `ReceivedServicesJournalRowDto` (staff_id, pay_type), `CustomersReturnJournalRowDto` (waybill_num, staff_id, store_id, pay_type), `CustomersOrderJournalRowDto` (staff_id, store_id, order_status, pay_type).
- `getRangeChunked()` rejects `chunkDays < 1` instead of looping forever.

### Added
- Reporting: `movesJournal()`, `movesJournalTyped()`, `movesJournalChunked()`, `movesJournalChunkedTyped()`, `realizesJournalTyped()`, `realizesJournalChunked()`, `realizesJournalChunkedTyped()`, `JournalsApi::moves()`; `RealizesJournalRowDto`, `MovesJournalRowDto` and their response DTOs.
- Documents: `getProductOutTyped()`, `getProductOutSingleTyped()`, `getProductMoveTyped()`, `getCustomerReturnTyped()`, `getCustomerOrderTyped()` with `ProductOutDocDto` (incl. `checkStatus`), `ProductOutSingleDocDto`, `ProductMoveDocDto`, `CustomerReturnDocDto` (lines carry `outId`), `CustomerOrderDocDto`, `DocProductLineDto`, `DocServiceLineDto`; raw `getInventoryOut()`, `getInventoryMove()`, `getCustomerInventoryReturn()`, `getProvidedService()`, `getDiscountCard()`; `saveDiscountCard()`, `saveGiftCard()`, `updateRsStatus()` (`RsStatusResponse`).
- Products: `restByStore()`, `restAdvance()`, `subRest()`, `selfCost()`, `pricesAdvance()`, `images()`, `places()`, `characteristicValues()`, `characteristicValuesByProductIds()`, `packed()`, `subCodeTypes()`, `subCodes()`, `onWay()`, `additionalFields()`, `save()`.
- Reference: `storeGroups()`, `staffAdditionalFields()`, `docAdditionalFields()`, `accountValue()` (`AccountValueDto`), `saveStaff()`. `CustomersApi::save()`, `VendorsApi::save()`.
- New clients `ServicesApi` (`$fina->services()`) and `InventoriesApi` (`$fina->inventories()`); `AdditionalFieldDescriptorDto`.
- Tests: PDF-example fixtures for every journal, report and document; endpoint path/body tests for every new method; `PdfParityTest`; read-only live integration suite (`--testsuite Integration`, env-gated, write-guarded).

### Changed
- Row DTO constructor signatures changed with the new properties; build rows through `fromArray()`.
- README documents which four save endpoints the 8.0 PDF does not cover.

## [0.1.3] - 2026-10-16

### Added
- `ProductsApi::rest()` — `GET getProductsRest`, current rest of every product per store.
- `DocumentsApi::getProductOutSingle($id)` — `GET getDocProductOutSingle/{id}`, retail sale document.
