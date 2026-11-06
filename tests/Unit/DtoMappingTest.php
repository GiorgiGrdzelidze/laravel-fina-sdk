<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Fina\Sdk\Laravel\Auth\AuthTokenResponse;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Reporting\Dto\CycleReportResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\CycleReportRowDto;
use Fina\Sdk\Laravel\Reporting\Dto\EntriesJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\EntriesJournalRowDto;
use Fina\Sdk\Laravel\Reporting\Dto\MoneyJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\MoneyJournalRowDto;
use Fina\Sdk\Laravel\Reporting\Dto\ProductsInReturnReportResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\ProductsInReturnReportRowDto;
use PHPUnit\Framework\TestCase;

final class DtoMappingTest extends TestCase
{
    // -----------------------------------------------------------------
    // CycleReportResponseDto
    // -----------------------------------------------------------------

    public function test_cycle_report_maps_fields(): void
    {
        $raw = [
            'reports' => [
                ['id' => 1, 'start_val' => 100.0, 'in_val' => 50.0, 'out_val' => 30.0, 'end_val' => 120.0],
                ['id' => 2, 'start_val' => 200.0, 'in_val' => 0.0, 'out_val' => 10.0, 'end_val' => 190.0],
            ],
            'ex' => null,
        ];

        $dto = CycleReportResponseDto::fromArray($raw);

        $this->assertCount(2, $dto->reports);
        $this->assertNull($dto->ex);

        $this->assertInstanceOf(CycleReportRowDto::class, $dto->reports[0]);
        $this->assertSame(1, $dto->reports[0]->id);
        $this->assertSame(100.0, $dto->reports[0]->startValue);
        $this->assertSame(50.0, $dto->reports[0]->inValue);
        $this->assertSame(30.0, $dto->reports[0]->outValue);
        $this->assertSame(120.0, $dto->reports[0]->endValue);

        $this->assertSame(2, $dto->reports[1]->id);
        $this->assertSame(190.0, $dto->reports[1]->endValue);
    }

    public function test_cycle_report_preserves_ex(): void
    {
        $dto = CycleReportResponseDto::fromArray([
            'reports' => [],
            'ex' => 'some error',
        ]);

        $this->assertSame('some error', $dto->ex);
        $this->assertCount(0, $dto->reports);
    }

    // -----------------------------------------------------------------
    // MoneyJournalResponseDto
    // -----------------------------------------------------------------

    public function test_money_journal_maps_fields(): void
    {
        $raw = [
            'journals' => [
                [
                    'id' => 10,
                    'version' => 'v1',
                    'date' => '2025-01-15T10:00:00',
                    'doc_num' => 'MJ-001',
                    'doc_type' => 3,
                    'purpose' => 'Payment',
                    'amount' => 500.0,
                    'staff_id' => 5,
                    'currency' => 'GEL',
                    'customer_id' => 42,
                    'vendor_id' => null,
                    'pay_type' => 1,
                    'pay_type_id' => 2,
                ],
            ],
            'ex' => null,
        ];

        $dto = MoneyJournalResponseDto::fromArray($raw);

        $this->assertCount(1, $dto->journals);
        $this->assertNull($dto->ex);

        $row = $dto->journals[0];
        $this->assertInstanceOf(MoneyJournalRowDto::class, $row);
        $this->assertSame(10, $row->id);
        $this->assertSame('v1', $row->version);
        $this->assertSame('MJ-001', $row->docNum);
        $this->assertSame(3, $row->docType);
        $this->assertSame(500.0, $row->amount);
        $this->assertSame(42, $row->customerId);
        $this->assertNull($row->vendorId);
        $this->assertSame('GEL', $row->currency);
    }

    // -----------------------------------------------------------------
    // EntriesJournalResponseDto
    // -----------------------------------------------------------------

    public function test_entries_journal_maps_fields(): void
    {
        // Two lines of ONE operation (id 3121) as in the PDF example: same id and
        // version, different accounts and even different doc_type per line.
        $raw = [
            'journals' => [
                [
                    'id' => 3121,
                    'version' => 'AAAAAAABf5A=',
                    'date' => '2018-10-19 16:03:21',
                    'doc_num' => '5',
                    'waybill_num' => null,
                    'doc_type' => 49,
                    'purpose' => 'ავანსის მიღება მყიდველისგან - რონალდ რეიგანი',
                    'amount' => 12.00,
                    'amount_currency' => 24,
                    'currency' => 'USD',
                    'staff_id' => 1,
                    'debit_acc' => '1110.1',
                    'debit_comment' => 'მთავარი სალარო',
                    'debit_quantity' => 0,
                    'credit_acc' => '3120.8',
                    'credit_comment' => 'რონალდ რეიგანი',
                    'credit_quantity' => 0,
                    'project_id' => 2,
                ],
                [
                    'id' => 3121,
                    'version' => 'AAAAAAABf5A=',
                    'date' => '2018-10-19 16:03:21',
                    'doc_num' => '5',
                    'waybill_num' => null,
                    'doc_type' => 38,
                    'purpose' => 'ავანსის მიღება მყიდველისგან - რონალდ რეიგანი',
                    'amount' => 1.83,
                    'amount_currency' => 1.83,
                    'currency' => 'GEL',
                    'staff_id' => 1,
                    'debit_acc' => '3339',
                    'debit_comment' => 'გადასახდელი დღგ-ს ტრანზიტული ანგარიში',
                    'debit_quantity' => 0,
                    'credit_acc' => '3330',
                    'credit_comment' => 'გადასახდელი დღგ',
                    'credit_quantity' => 0,
                    'project_id' => 1,
                ],
            ],
            'ex' => null,
        ];

        $dto = EntriesJournalResponseDto::fromArray($raw);

        $this->assertCount(2, $dto->journals);
        $first = $dto->journals[0];
        $this->assertInstanceOf(EntriesJournalRowDto::class, $first);
        $this->assertSame(3121, $first->id);
        $this->assertSame('AAAAAAABf5A=', $first->version);
        $this->assertSame('2018-10-19 16:03:21', $first->date);
        $this->assertSame('5', $first->docNum);
        $this->assertNull($first->waybillNum);
        $this->assertSame(49, $first->docType);
        $this->assertSame(12.0, $first->amount);
        $this->assertSame(24.0, $first->amountCurrency);
        $this->assertSame('USD', $first->currency);
        $this->assertSame(1, $first->staffId);
        $this->assertSame('1110.1', $first->debitAcc);
        $this->assertSame('მთავარი სალარო', $first->debitComment);
        $this->assertSame(0.0, $first->debitQuantity);
        $this->assertSame('3120.8', $first->creditAcc);
        $this->assertSame('რონალდ რეიგანი', $first->creditComment);
        $this->assertSame(0.0, $first->creditQuantity);
        $this->assertSame(2, $first->projectId);

        $second = $dto->journals[1];
        $this->assertSame(38, $second->docType);
        $this->assertSame('3339', $second->debitAcc);
        $this->assertSame('3330', $second->creditAcc);
        $this->assertSame(1.83, $second->amountCurrency);
        $this->assertSame($first->dedupeKey(), $second->dedupeKey(), 'lines of one operation share the operation key');
        $this->assertSame($raw['journals'][1], $second->raw);
    }

    public function test_entries_journal_amount_currency_falls_back_to_amount(): void
    {
        $row = EntriesJournalRowDto::fromArray(['id' => 1, 'version' => 'v', 'amount' => 7.5]);

        $this->assertSame(7.5, $row->amountCurrency);
    }

    public function test_entries_journal_row_dedupe_key(): void
    {
        $row = EntriesJournalRowDto::fromArray([
            'id' => 5,
            'version' => 'xyz',
        ]);

        $this->assertSame('idv:5:xyz', $row->dedupeKey());
    }

    public function test_entries_journal_to_array_roundtrip(): void
    {
        $raw = [
            'id' => 1,
            'version' => 'v',
            'date' => '2025-01-01',
            'doc_num' => 'D1',
            'doc_type' => 2,
            'purpose' => 'test',
            'amount' => 10.0,
        ];

        $row = EntriesJournalRowDto::fromArray($raw);
        $arr = $row->toArray();

        $this->assertSame(1, $arr['id']);
        $this->assertSame('v', $arr['version']);
        $this->assertSame('D1', $arr['doc_num']);
        $this->assertSame(2, $arr['doc_type']);
        $this->assertSame(10.0, $arr['amount']);
        $this->assertArrayHasKey('raw', $arr);
        foreach (['waybill_num', 'amount_currency', 'currency', 'staff_id', 'debit_acc', 'debit_comment', 'debit_quantity', 'credit_acc', 'credit_comment', 'credit_quantity', 'project_id'] as $key) {
            $this->assertArrayHasKey($key, $arr);
        }
    }

    // -----------------------------------------------------------------
    // ProductsInReturnReportResponseDto — rows exactly as the FINA 8.0 PDF
    // (getProductsInReturnReport) documents them
    // -----------------------------------------------------------------

    public function test_products_in_return_report_maps_fields(): void
    {
        $raw = [
            'reports' => [
                [
                    'date' => '2020-08-21T13:01:28.913',
                    'doc_num' => '10',
                    'doc_type' => 16,
                    'vendor_id' => 24,
                    'store_id' => 1,
                    'product_id' => 1,
                    'unit_id' => 1,
                    'quantity' => 20,
                    'amount' => 2000,
                    'vat' => 0,
                ],
                [
                    'date' => '2020-09-22T08:21:27',
                    'doc_num' => '0548560688',
                    'doc_type' => 32,
                    'vendor_id' => 5,
                    'store_id' => 1,
                    'product_id' => 58,
                    'unit_id' => 1,
                    'quantity' => 1,
                    'amount' => 3,
                    'vat' => 0.457627,
                ],
            ],
            'ex' => null,
        ];

        $dto = ProductsInReturnReportResponseDto::fromArray($raw);

        $this->assertCount(2, $dto->reports);
        $this->assertNull($dto->ex);

        $row = $dto->reports[0];
        $this->assertInstanceOf(ProductsInReturnReportRowDto::class, $row);
        $this->assertSame('2020-08-21T13:01:28.913', $row->date);
        $this->assertSame('10', $row->docNum);
        $this->assertSame(16, $row->docType);
        $this->assertSame(24, $row->vendorId);
        $this->assertSame(1, $row->storeId);
        $this->assertSame(1, $row->productId);
        $this->assertSame(1, $row->unitId);
        $this->assertSame(20.0, $row->quantity);
        $this->assertSame(2000.0, $row->amount);
        $this->assertSame(0.0, $row->vat);

        $return = $dto->reports[1];
        $this->assertSame(32, $return->docType);
        $this->assertSame(58, $return->productId);
        $this->assertSame(0.457627, $return->vat);
        $this->assertSame($raw['reports'][1], $return->raw);
    }

    // -----------------------------------------------------------------
    // SaveDocResponse
    // -----------------------------------------------------------------

    public function test_save_doc_response_maps_fields(): void
    {
        $dto = SaveDocResponse::fromArray(['id' => 42, 'ex' => null]);

        $this->assertSame(42, $dto->id);
        $this->assertNull($dto->ex);
    }

    public function test_save_doc_response_with_error(): void
    {
        $dto = SaveDocResponse::fromArray(['id' => 0, 'ex' => 'Something went wrong']);

        $this->assertSame(0, $dto->id);
        $this->assertSame('Something went wrong', $dto->ex);
    }

    // -----------------------------------------------------------------
    // AuthTokenResponse
    // -----------------------------------------------------------------

    public function test_auth_token_response_maps_token(): void
    {
        $dto = AuthTokenResponse::fromArray(['token' => 'abc123', 'ex' => null]);

        $this->assertSame('abc123', $dto->token);
        $this->assertNull($dto->ex);
    }

    public function test_auth_token_response_empty_token(): void
    {
        $dto = AuthTokenResponse::fromArray(['token' => '', 'ex' => 'Invalid credentials']);

        $this->assertNull($dto->token);
        $this->assertSame('Invalid credentials', $dto->ex);
    }

    // -----------------------------------------------------------------
    // Empty / missing data graceful handling
    // -----------------------------------------------------------------

    public function test_entries_journal_handles_empty_array(): void
    {
        $dto = EntriesJournalResponseDto::fromArray([]);

        $this->assertCount(0, $dto->journals);
        $this->assertNull($dto->ex);
    }

    public function test_cycle_report_handles_empty_array(): void
    {
        $dto = CycleReportResponseDto::fromArray([]);

        $this->assertCount(0, $dto->reports);
        $this->assertNull($dto->ex);
    }
}
