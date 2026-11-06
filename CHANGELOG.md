# Changelog

All notable changes to this project will be documented in this file.

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
