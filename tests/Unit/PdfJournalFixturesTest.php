<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Every reporting endpoint fed with the example response printed in
 * "FINA WEB API 8.0" (2026-03-04), field for field. If FINA renames a field,
 * the fixture here is the only thing that should need changing.
 */
final class PdfJournalFixturesTest extends TestCase
{
    private CarbonImmutable $from;

    private CarbonImmutable $to;

    protected function setUp(): void
    {
        parent::setUp();
        $this->from = CarbonImmutable::create(2018, 1, 1, 0, 0, 0);
        $this->to = CarbonImmutable::create(2021, 12, 31, 23, 59, 59);
    }

    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    /** @param array<string, mixed> $body */
    private function fakeReporting(string $method, array $body): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            "*/api/reporting/{$method}/*" => Http::response($body),
        ]);
    }

    private function assertRequested(string $method): void
    {
        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && $r->url() === "http://fina-test.local:5007/api/reporting/{$method}/2018-01-01T00%3A00%3A00/2021-12-31T23%3A59%3A59"
            && $r->hasHeader('Authorization', 'Bearer tok'));
    }

    public function test_realizes_journal(): void
    {
        $this->fakeReporting('getRealizesJournal', [
            'journals' => [
                ['id' => 5280, 'version' => 'AAAAAAAB3TM=', 'date' => '2018-12-27 18:10:48', 'doc_num' => '36', 'waybill_num' => null, 'doc_type' => 21, 'purpose' => 'საქონლის რეალიზაცია ნაღდზე', 'amount' => 4.28, 'staff_id' => 3, 'currency' => 'GEL', 'customer_id' => 8, 'store_id' => 1, 'pay_type' => 0],
                ['id' => 5282, 'version' => 'AAAAAAAB3Tg=', 'date' => '2018-12-27 18:36:25', 'doc_num' => '49', 'waybill_num' => '0431440360', 'doc_type' => 23, 'purpose' => 'საცალო გაყიდვა № 49', 'amount' => 4.50, 'staff_id' => 0, 'currency' => 'GEL', 'customer_id' => 0, 'store_id' => 1, 'pay_type' => 0],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->realizesJournalTyped($this->from, $this->to);
        $this->assertRequested('getRealizesJournal');

        $this->assertCount(2, $dto->journals);
        $this->assertNull($dto->ex);

        $sale = $dto->journals[0];
        $this->assertSame(5280, $sale->id);
        $this->assertSame('AAAAAAAB3TM=', $sale->version);
        $this->assertSame('2018-12-27 18:10:48', $sale->date);
        $this->assertSame('36', $sale->docNum);
        $this->assertNull($sale->waybillNum);
        $this->assertSame(21, $sale->docType);
        $this->assertSame('საქონლის რეალიზაცია ნაღდზე', $sale->purpose);
        $this->assertSame(4.28, $sale->amount);
        $this->assertSame(3, $sale->staffId);
        $this->assertSame('GEL', $sale->currency);
        $this->assertSame(8, $sale->customerId);
        $this->assertSame(1, $sale->storeId);
        $this->assertSame(0, $sale->payType);
        $this->assertSame('idv:5280:AAAAAAAB3TM=', $sale->dedupeKey());

        $retail = $dto->journals[1];
        $this->assertSame(23, $retail->docType);
        $this->assertSame('0431440360', $retail->waybillNum);
        $this->assertSame(0, $retail->customerId);
        $this->assertSame(4.5, $retail->amount);

        $this->assertSame('36', $dto->toArray()['journals'][0]['doc_num']);
        $this->assertSame(
            $this->client()->reporting()->realizesJournal($this->from, $this->to)['journals'][0]['id'],
            $sale->id
        );
    }

    public function test_moves_journal(): void
    {
        $this->fakeReporting('getMovesJournal', [
            'journals' => [
                ['id' => 14830, 'version' => 'AAAAAAAG/RM=', 'date' => '2020-03-18 10:24:13', 'doc_num' => '11', 'waybill_num' => '0514054983', 'doc_type' => 20, 'purpose' => 'საქონლის გადატანა № 11', 'amount' => 18.70, 'staff_id' => 2, 'store_from_id' => 1, 'store_to_id' => 2],
                ['id' => 14831, 'version' => 'AAAAAAAG/RQ=', 'date' => '2020-03-18 10:25:25', 'doc_num' => '12', 'waybill_num' => null, 'doc_type' => 12, 'purpose' => 'ძირითადი საშუალების გადაადგილება № 12', 'amount' => 5.70, 'staff_id' => 2, 'store_from_id' => 1, 'store_to_id' => 6],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->movesJournalTyped($this->from, $this->to);
        $this->assertRequested('getMovesJournal');

        $this->assertCount(2, $dto->journals);
        $move = $dto->journals[0];
        $this->assertSame(14830, $move->id);
        $this->assertSame('AAAAAAAG/RM=', $move->version);
        $this->assertSame('2020-03-18 10:24:13', $move->date);
        $this->assertSame('11', $move->docNum);
        $this->assertSame('0514054983', $move->waybillNum);
        $this->assertSame(20, $move->docType);
        $this->assertSame('საქონლის გადატანა № 11', $move->purpose);
        $this->assertSame(18.7, $move->amount);
        $this->assertSame(2, $move->staffId);
        $this->assertSame(1, $move->storeFromId);
        $this->assertSame(2, $move->storeToId);
        $this->assertSame('idv:14830:AAAAAAAG/RM=', $move->dedupeKey());

        $assetMove = $dto->journals[1];
        $this->assertSame(12, $assetMove->docType);
        $this->assertNull($assetMove->waybillNum);
        $this->assertSame(6, $assetMove->storeToId);

        $this->assertSame(2, $dto->toArray()['journals'][1]['store_from_id'] + 1);

        $raw = $this->client()->reporting()->movesJournal($this->from, $this->to);
        $this->assertSame(14830, $raw['journals'][0]['id']);

        $viaJournals = $this->client()->journals()->moves($this->from, $this->to);
        $this->assertSame($raw, $viaJournals);
    }

    public function test_provided_services_journal(): void
    {
        $this->fakeReporting('getDocProvidedServicesJournal', [
            'journals' => [
                ['id' => 6400, 'version' => 'AAAAAAACWbw=', 'date' => '2019-02-15 13:00:00', 'doc_num' => '7', 'doc_type' => 29, 'purpose' => 'მომსახურების გაწევა', 'amount' => 2.00, 'staff_id' => 2, 'currency' => 'GEL', 'customer_id' => 8, 'f_status' => false, 'pay_type' => 0],
                ['id' => 6478, 'version' => 'AAAAAAAC5rk=', 'date' => '2019-03-15 12:32:18', 'doc_num' => '8', 'doc_type' => 29, 'purpose' => 'მომსახურების გაწევა № 8', 'amount' => 118.00, 'staff_id' => 0, 'currency' => 'GEL', 'customer_id' => 1, 'f_status' => false, 'pay_type' => 1],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->providedServicesJournalTyped($this->from, $this->to);
        $this->assertRequested('getDocProvidedServicesJournal');

        $row = $dto->journals[1];
        $this->assertSame(6478, $row->id);
        $this->assertSame('AAAAAAAC5rk=', $row->version);
        $this->assertSame('8', $row->docNum);
        $this->assertSame(29, $row->docType);
        $this->assertSame(118.0, $row->amount);
        $this->assertSame(0, $row->staffId);
        $this->assertSame('GEL', $row->currency);
        $this->assertSame(1, $row->customerId);
        $this->assertFalse($row->fStatus);
        $this->assertSame(1, $row->payType);
        $this->assertSame(['id', 'version', 'date', 'doc_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'customer_id', 'f_status', 'pay_type', 'raw'], array_keys($row->toArray()));
    }

    public function test_received_services_journal(): void
    {
        $this->fakeReporting('getDocReceivedServicesJournal', [
            'journals' => [
                ['id' => 6562, 'version' => 'AAAAAAAEPh0=', 'date' => '2019-05-16 11:36:11', 'doc_num' => '4', 'doc_type' => 28, 'purpose' => 'მომსახურების მიღება № 4', 'amount' => 1000.00, 'staff_id' => 0, 'currency' => 'GEL', 'vendor_id' => 3, 'pay_type' => 1],
                ['id' => 10756, 'version' => 'AAAAAAAGAy0=', 'date' => '2019-12-09 16:22:04', 'doc_num' => '5', 'doc_type' => 28, 'purpose' => 'მომსახურების მიღება № 5', 'amount' => 1000.00, 'staff_id' => 0, 'currency' => 'GEL', 'vendor_id' => 24, 'pay_type' => 2],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->receivedServicesJournalTyped($this->from, $this->to);
        $this->assertRequested('getDocReceivedServicesJournal');

        $row = $dto->journals[1];
        $this->assertSame(10756, $row->id);
        $this->assertSame('5', $row->docNum);
        $this->assertSame(28, $row->docType);
        $this->assertSame(1000.0, $row->amount);
        $this->assertSame(0, $row->staffId);
        $this->assertSame(24, $row->vendorId);
        $this->assertSame(2, $row->payType);
        $this->assertSame(24, $row->toArray()['vendor_id']);
    }

    public function test_customers_order_journal(): void
    {
        $this->fakeReporting('getCustomersOrderJournal', [
            'journals' => [
                ['id' => 3121, 'version' => 'AAAAAAABf5A=', 'date' => '2018-10-19 16:03:21', 'doc_num' => '5', 'doc_type' => 8, 'purpose' => 'შეკვეთა მყიდველისგან № 5', 'amount' => 49.00, 'staff_id' => 4, 'currency' => 'GEL', 'customer_id' => 8, 'store_id' => 1, 'order_status' => 1, 'pay_type' => 0],
                ['id' => 4131, 'version' => 'AAAAAAACOlY=', 'date' => '2018-11-07 11:30:28', 'doc_num' => '6', 'doc_type' => 8, 'purpose' => 'შეკვეთა მყიდველისგან № 6', 'amount' => 33.75, 'staff_id' => 0, 'currency' => 'GEL', 'customer_id' => 8, 'store_id' => 1, 'order_status' => 3, 'pay_type' => 5],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->customersOrderJournalTyped($this->from, $this->to);
        $this->assertRequested('getCustomersOrderJournal');

        $cancelled = $dto->journals[1];
        $this->assertSame(4131, $cancelled->id);
        $this->assertSame(8, $cancelled->docType);
        $this->assertSame(33.75, $cancelled->amount);
        $this->assertSame(0, $cancelled->staffId);
        $this->assertSame(8, $cancelled->customerId);
        $this->assertSame(1, $cancelled->storeId);
        $this->assertSame(3, $cancelled->orderStatus);
        $this->assertSame(5, $cancelled->payType);
        $this->assertSame(4, $dto->journals[0]->staffId);
        $this->assertSame(1, $dto->journals[0]->orderStatus);
    }

    public function test_customers_return_journal(): void
    {
        $this->fakeReporting('getCustomersReturnJournal', [
            'journals' => [
                ['id' => 6352, 'version' => 'AAAAAAACSlg=', 'date' => '2019-02-11 12:56:45', 'doc_num' => '1', 'waybill_num' => null, 'doc_type' => 9, 'purpose' => 'დაბრუნება მყიდველისგან № 0', 'amount' => 7.25, 'staff_id' => 0, 'currency' => 'GEL', 'customer_id' => 8, 'store_id' => 1, 'pay_type' => 1],
                ['id' => 6401, 'version' => 'AAAAAAACaUU=', 'date' => '2019-02-15 13:00:00', 'doc_num' => '5', 'waybill_num' => null, 'doc_type' => 9, 'purpose' => 'dabruneba', 'amount' => 65.7, 'staff_id' => 0, 'currency' => 'GEL', 'customer_id' => 8, 'store_id' => 1, 'pay_type' => 2],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->customersReturnJournalTyped($this->from, $this->to);
        $this->assertRequested('getCustomersReturnJournal');

        $row = $dto->journals[1];
        $this->assertSame(6401, $row->id);
        $this->assertNull($row->waybillNum);
        $this->assertSame(9, $row->docType);
        $this->assertSame('dabruneba', $row->purpose);
        $this->assertSame(65.7, $row->amount);
        $this->assertSame(8, $row->customerId);
        $this->assertSame(1, $row->storeId);
        $this->assertSame(2, $row->payType);
    }

    public function test_customers_money_journal(): void
    {
        $this->fakeReporting('getCustomersMoneyJournal', [
            'journals' => [
                ['id' => 3122, 'version' => 'AAAAAAACOnw=', 'date' => '2018-10-19 16:05:04', 'doc_num' => '5', 'doc_type' => 49, 'purpose' => 'ავანსის მიღება მყიდველისგან - რონალდ რეიგანი', 'amount' => 12.00, 'staff_id' => 4, 'currency' => 'USD', 'customer_id' => 8, 'pay_type' => 4, 'pay_type_id' => 11],
                ['id' => 3123, 'version' => 'AAAAAAABf5I=', 'date' => '2018-10-19 16:08:48', 'doc_num' => '5', 'doc_type' => 38, 'purpose' => 'თანხის მიღება მყიდველისგან - რონალდ რეიგანი', 'amount' => 10.00, 'staff_id' => 0, 'currency' => 'GEL', 'customer_id' => 8, 'pay_type' => 1, 'pay_type_id' => 1],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->customersMoneyJournalTyped($this->from, $this->to);
        $this->assertRequested('getCustomersMoneyJournal');

        $advance = $dto->journals[0];
        $this->assertSame(49, $advance->docType);
        $this->assertSame('USD', $advance->currency);
        $this->assertSame(4, $advance->payType);
        $this->assertSame(11, $advance->payTypeId);
        $this->assertSame(8, $advance->customerId);
        $this->assertNull($advance->vendorId);
    }

    public function test_vendors_money_journal(): void
    {
        $this->fakeReporting('getVendorsMoneyJournal', [
            'journals' => [
                ['id' => 4122, 'version' => 'AAAAAAACOnw=', 'date' => '2018-10-19 16:05:04', 'doc_num' => '5', 'doc_type' => 50, 'purpose' => 'ავანსის გაცემა მომწოდებელზე - (მომწოდებელი)', 'amount' => 12.00, 'staff_id' => 4, 'currency' => 'USD', 'vendor_id' => 18, 'pay_type' => 4, 'pay_type_id' => 11],
                ['id' => 3165, 'version' => 'AAAAAAABf5I=', 'date' => '2018-10-19 16:08:48', 'doc_num' => '5', 'doc_type' => 39, 'purpose' => 'თანხის გაცემა მომწოდებელზე - xyz', 'amount' => 10.00, 'staff_id' => 0, 'currency' => 'GEL', 'vendor_id' => 68, 'pay_type' => 1, 'pay_type_id' => 1],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->vendorsMoneyJournalTyped($this->from, $this->to);
        $this->assertRequested('getVendorsMoneyJournal');

        $payment = $dto->journals[1];
        $this->assertSame(39, $payment->docType);
        $this->assertSame(68, $payment->vendorId);
        $this->assertNull($payment->customerId);
        $this->assertSame(1, $payment->payType);
        $this->assertSame(1, $payment->payTypeId);
    }

    public function test_productions_journal(): void
    {
        $this->fakeReporting('getProductionsJournal', [
            'journals' => [
                ['id' => 17059, 'version' => 'AAAAAAAQY1Q=', 'date' => '2021-01-14 16:12:00', 'doc_num' => '2', 'doc_type' => 18, 'purpose' => 'წარმოება № 2', 'amount' => 0.00, 'store_id' => 1, 'production_type' => 1],
                ['id' => 17060, 'version' => 'AAAAAAAQgpI=', 'date' => '2021-01-14 18:44:57', 'doc_num' => '3', 'doc_type' => 18, 'purpose' => 'წარმოება № 3', 'amount' => 5.92, 'store_id' => 1, 'production_type' => 0],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->productionsJournalTyped($this->from, $this->to);
        $this->assertRequested('getProductionsJournal');

        $this->assertSame(1, $dto->journals[0]->productionType);
        $this->assertSame(0, $dto->journals[1]->productionType);
        $this->assertSame(5.92, $dto->journals[1]->amount);
        $this->assertSame(1, $dto->journals[1]->storeId);
        $this->assertArrayNotHasKey('user_id', $dto->journals[1]->toArray());
        $this->assertSame(1, $dto->journals[0]->toArray()['production_type']);
    }

    public function test_discount_cards_journal(): void
    {
        $this->fakeReporting('getDiscountCardsJournal', [
            'journals' => [
                ['id' => 8728, 'version' => 'AAAAAAAFKHk=', 'date' => '2019-11-08 18:00:00', 'doc_num' => '14', 'doc_type' => 118, 'purpose' => 'ფასდაკლების ბარათის გაცემა', 'amount' => 823.44, 'staff_id' => 0, 'customer' => 31, 'store_id' => 1, 'card_code' => '231', 'discount_id' => 1, 'status' => true],
                ['id' => 8733, 'version' => 'AAAAAAAFKHg=', 'date' => '2019-11-08 18:00:00', 'doc_num' => '15', 'doc_type' => 118, 'purpose' => 'ფასდაკლების ბარათის გაცემა', 'amount' => 823.44, 'staff_id' => 0, 'customer' => 31, 'store_id' => 1, 'card_code' => '2311', 'discount_id' => 2, 'status' => true],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->discountCardsJournalTyped($this->from, $this->to);
        $this->assertRequested('getDiscountCardsJournal');

        $card = $dto->journals[1];
        $this->assertSame(8733, $card->id);
        $this->assertSame(118, $card->docType);
        $this->assertSame(823.44, $card->amount);
        $this->assertSame(0, $card->staffId);
        $this->assertSame(31, $card->customerId, 'journal sends the holder as `customer`');
        $this->assertSame(1, $card->storeId);
        $this->assertSame('2311', $card->cardCode);
        $this->assertSame(2, $card->discountId);
        $this->assertTrue($card->status);
    }

    public function test_auto_services_out_journal(): void
    {
        $this->fakeReporting('getAutoServicesOutJournal', [
            'journals' => [
                ['id' => 5280, 'version' => 'AAAAAAAB3TM=', 'date' => '2022-12-27 18:10:48', 'in_date' => '2022-12-29 12:00:00', 'doc_num' => '36', 'waybill_num' => null, 'doc_type' => 107, 'purpose' => 'განაცხადი № 2', 'amount' => 150, 'staff_id' => 3, 'customer_id' => 8, 'pay_type' => 0],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->autoServicesOutJournalTyped($this->from, $this->to);
        $this->assertRequested('getAutoServicesOutJournal');

        $row = $dto->journals[0];
        $this->assertSame('2022-12-29 12:00:00', $row->inDate);
        $this->assertSame(107, $row->docType);
        $this->assertSame(150.0, $row->amount);
        $this->assertSame(8, $row->customerId);
    }

    public function test_cycle_reports(): void
    {
        $this->fakeReporting('getVendorsCycleReport', [
            'reports' => [
                ['id' => 2, 'start_val' => 2887.27, 'in_val' => 2275.70, 'out_val' => 3136.00, 'end_val' => 2026.97],
                ['id' => 3, 'start_val' => 4584.28, 'in_val' => 620.00, 'out_val' => 323.27, 'end_val' => 4881.01],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->vendorsCycleReportTyped($this->from, $this->to);
        $this->assertRequested('getVendorsCycleReport');

        $this->assertSame(2887.27, $dto->reports[0]->startValue);
        $this->assertSame(2275.7, $dto->reports[0]->inValue);
        $this->assertSame(3136.0, $dto->reports[0]->outValue);
        $this->assertSame(2026.97, $dto->reports[0]->endValue);
        $this->assertSame(3, $dto->reports[1]->id);
    }

    public function test_products_last_in_report(): void
    {
        $this->fakeReporting('getProductsLastInReport', [
            'reports' => [
                ['id' => 1, 'quantity' => 2.00, 'price' => 118.00, 'vendor_id' => 25, 'currency' => 'GEL', 'rate' => 1.00, 'waybill_date' => '2019-04-01T16:12:00', 'waybill_num' => '', 'purpose' => 'საქონლის მიღება № 10'],
                ['id' => 2, 'quantity' => 1.00, 'price' => 35.70, 'vendor_id' => 2, 'currency' => 'GEL', 'rate' => 1.00, 'waybill_date' => '2019-02-18T13:00:00', 'waybill_num' => '', 'purpose' => 'shesyidva'],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->productsLastInReportTyped($this->from, $this->to);
        $this->assertRequested('getProductsLastInReport');

        $row = $dto->reports[0];
        $this->assertSame(1, $row->id);
        $this->assertSame(2.0, $row->quantity);
        $this->assertSame(118.0, $row->price);
        $this->assertSame(25, $row->vendorId);
        $this->assertSame('GEL', $row->currency);
        $this->assertSame(1.0, $row->rate);
        $this->assertSame('2019-04-01T16:12:00', $row->waybillDate);
        $this->assertSame('', $row->waybillNum);
        $this->assertSame('საქონლის მიღება № 10', $row->purpose);
        $this->assertSame(35.7, $dto->reports[1]->price);
        $this->assertSame('2019-02-18T13:00:00', $dto->reports[1]->toArray()['waybill_date']);
    }

    public function test_products_in_return_report(): void
    {
        $this->fakeReporting('getProductsInReturnReport', [
            'reports' => [
                ['date' => '2020-08-21T13:01:28.913', 'doc_num' => '10', 'doc_type' => 16, 'vendor_id' => 24, 'store_id' => 1, 'product_id' => 1, 'unit_id' => 1, 'quantity' => 20, 'amount' => 2000, 'vat' => 0],
                ['date' => '2020-09-22T08:21:27', 'doc_num' => '0548560688', 'doc_type' => 32, 'vendor_id' => 5, 'store_id' => 1, 'product_id' => 58, 'unit_id' => 1, 'quantity' => 1, 'amount' => 3, 'vat' => 0.457627],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->productsInReturnReportTyped($this->from, $this->to);
        $this->assertRequested('getProductsInReturnReport');

        $this->assertSame(16, $dto->reports[0]->docType);
        $this->assertSame(24, $dto->reports[0]->vendorId);
        $this->assertSame(2000.0, $dto->reports[0]->amount);
        $this->assertSame(32, $dto->reports[1]->docType);
        $this->assertSame('0548560688', $dto->reports[1]->docNum);
        $this->assertSame(58, $dto->reports[1]->productId);
        $this->assertSame(0.457627, $dto->reports[1]->vat);
    }

    public function test_cafe_order_detailed_report(): void
    {
        $this->fakeReporting('getCafeOrderDetailedReport', [
            'reports' => [
                ['date' => '2020-08-21T13:01:28.913', 'doc_num' => '20190228100000', 'store_id' => 1, 'status_id' => 1, 'product_id' => 1, 'group_id' => 1, 'unit_id' => 1, 'quantity' => 1, 'amount' => 2.5],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->cafeOrderDetailedReportTyped($this->from, $this->to);
        $this->assertRequested('getCafeOrderDetailedReport');

        $row = $dto->reports[0];
        $this->assertSame('20190228100000', $row->docNum);
        $this->assertSame(1, $row->statusId);
        $this->assertSame(1, $row->groupId);
        $this->assertSame(2.5, $row->amount);
    }

    public function test_entries_journal_typed_over_http(): void
    {
        $this->fakeReporting('getEntriesJournal', [
            'journals' => [
                ['id' => 3121, 'version' => 'AAAAAAABf5A=', 'date' => '2018-10-19 16:03:21', 'doc_num' => '5', 'waybill_num' => null, 'doc_type' => 49, 'purpose' => 'ავანსის მიღება მყიდველისგან - რონალდ რეიგანი', 'amount' => 12.00, 'amount_currency' => 24, 'currency' => 'USD', 'staff_id' => 1, 'debit_acc' => '1110.1', 'debit_comment' => 'მთავარი სალარო', 'debit_quantity' => 0, 'credit_acc' => '3120.8', 'credit_comment' => 'რონალდ რეიგანი', 'credit_quantity' => 0, 'project_id' => 2],
                ['id' => 3121, 'version' => 'AAAAAAABf5A=', 'date' => '2018-10-19 16:03:21', 'doc_num' => '5', 'waybill_num' => null, 'doc_type' => 38, 'purpose' => 'ავანსის მიღება მყიდველისგან - რონალდ რეიგანი', 'amount' => 1.83, 'amount_currency' => 1.83, 'currency' => 'GEL', 'staff_id' => 1, 'debit_acc' => '3339', 'debit_comment' => 'გადასახდელი დღგ-ს ტრანზიტული ანგარიში', 'debit_quantity' => 0, 'credit_acc' => '3330', 'credit_comment' => 'გადასახდელი დღგ', 'credit_quantity' => 0, 'project_id' => 1],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->entriesJournalTyped($this->from, $this->to);
        $this->assertRequested('getEntriesJournal');

        $this->assertCount(2, $dto->journals, 'both lines of operation 3121 are kept');
        $this->assertSame('1110.1', $dto->journals[0]->debitAcc);
        $this->assertSame('3330', $dto->journals[1]->creditAcc);
        $this->assertSame(24.0, $dto->journals[0]->amountCurrency);
    }
}
