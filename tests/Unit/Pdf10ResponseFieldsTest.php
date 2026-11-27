<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Operation\Dto\AutoServiceDocDto;
use Fina\Sdk\Laravel\Operation\Dto\ContragentDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\CustomerReturnDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\ProductMoveDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\ProductOutDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\WaybillStatus;
use Fina\Sdk\Laravel\Operation\Dto\StoreDto;
use Fina\Sdk\Laravel\Reporting\Dto\CafeOrderDetailedReportRowDto;
use Fina\Sdk\Laravel\Reporting\Dto\MoneyJournalRowDto;
use Fina\Sdk\Laravel\Reporting\Dto\RealizesJournalRowDto;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Response fields FINA WEB API 10.0 adds to methods that already existed, fed with the
 * document's examples; a FINA build that does not send a field leaves it null.
 */
final class Pdf10ResponseFieldsTest extends TestCase
{
    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    /** @param array<string, mixed> $body */
    private function fakeOperation(string $path, array $body): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            "*/api/operation/{$path}" => Http::response($body),
        ]);
    }

    /** @param array<string, mixed> $body */
    private function fakeReporting(string $method, array $body): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            "*/api/reporting/{$method}/*" => Http::response($body),
        ]);
    }

    // -------------------------------------------------------
    // waybill_status (10.0.20261106)
    // -------------------------------------------------------

    public function test_waybill_status_codes_are_the_documented_table(): void
    {
        $this->assertSame(
            [-1, 0, 1, 2, 8, -2],
            [WaybillStatus::NONE, WaybillStatus::SAVED, WaybillStatus::ACTIVATED, WaybillStatus::COMPLETED, WaybillStatus::WITH_CARRIER, WaybillStatus::CANCELLED]
        );
    }

    public function test_product_out_typed_reads_waybill_status(): void
    {
        $this->fakeOperation('getDocProductOut/6563', ['product_out' => [
            'id' => 6563, 'date' => '2019-05-16T15:28:19', 'num_pfx' => '', 'num' => 8624, 'waybill_num' => '0418925004', 'waybill_status' => 1,
            'purpose' => 'საქონლის გაყიდვა №', 'amount' => 780.0, 'currency' => 'GEL', 'rate' => 1.0, 'store' => 2, 'customer' => 1,
        ], 'ex' => null]);

        $doc = $this->client()->documents()->getProductOutTyped(6563);

        $this->assertSame(WaybillStatus::ACTIVATED, $doc->waybillStatus);
        $this->assertSame('0418925004', $doc->waybillNum);
        $this->assertSame(1, $doc->raw['waybill_status']);
    }

    public function test_product_move_and_customer_return_typed_read_waybill_status(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getDocProductMove/14830' => Http::response(['product_move' => ['id' => 14830, 'waybill_num' => null, 'waybill_status' => -1, 'store_from' => 1, 'store_to' => 2], 'ex' => null]),
            '*/api/operation/getDocCustomerReturn/6552' => Http::response(['customer_return' => ['id' => 6552, 'waybill_num' => '0526302888', 'waybill_status' => 1, 'customer' => 8], 'ex' => null]),
        ]);

        $move = $this->client()->documents()->getProductMoveTyped(14830);
        $return = $this->client()->documents()->getCustomerReturnTyped(6552);

        $this->assertSame(WaybillStatus::NONE, $move->waybillStatus);
        $this->assertNull($move->waybillNum);
        $this->assertSame(WaybillStatus::ACTIVATED, $return->waybillStatus);
    }

    public function test_auto_service_typed_reads_waybill_status(): void
    {
        $this->fakeOperation('getDocAutoService/6563', ['autoservice' => ['id' => 6563, 'waybill_num' => '0418925004', 'waybill_status' => 8, 'mileage' => 946176], 'ex' => null]);

        $doc = $this->client()->documents()->getAutoServiceTyped(6563);

        $this->assertSame(WaybillStatus::WITH_CARRIER, $doc->waybillStatus);
        $this->assertSame(946176, $doc->mileage);
    }

    public function test_waybill_status_is_null_when_the_build_does_not_send_it(): void
    {
        $this->assertNull(ProductOutDocDto::fromArray(['id' => 1, 'waybill_num' => '0418925004'])->waybillStatus);
        $this->assertNull(ProductMoveDocDto::fromArray(['id' => 1])->waybillStatus);
        $this->assertNull(CustomerReturnDocDto::fromArray(['id' => 1])->waybillStatus);
        $this->assertNull(AutoServiceDocDto::fromArray(['id' => 1])->waybillStatus);
        $this->assertSame(WaybillStatus::SAVED, ProductOutDocDto::fromArray(['waybill_status' => 0])->waybillStatus, '0 (saved) is not taken for missing');
        $this->assertSame(WaybillStatus::CANCELLED, ProductMoveDocDto::fromArray(['waybill_status' => '-2'])->waybillStatus);
    }

    public function test_raw_inventory_documents_pass_waybill_status_through(): void
    {
        $this->fakeOperation('getDocInventoryOut/6536', ['inventory_out' => ['id' => 6536, 'waybill_status' => -1], 'ex' => null]);

        $this->assertSame(-1, $this->client()->documents()->getInventoryOut(6536)['inventory_out']['waybill_status']);
    }

    // -------------------------------------------------------
    // ref_id (10.0.20261027)
    // -------------------------------------------------------

    public function test_realizes_journal_reads_ref_id(): void
    {
        $this->fakeReporting('getRealizesJournal', [
            'journals' => [
                ['id' => 5280, 'version' => 'AAAAAAAB3TM=', 'date' => '2018-12-27 18:10:48', 'doc_num' => '36', 'waybill_num' => null, 'doc_type' => 21, 'purpose' => 'საქონლის რეალიზაცია ნაღდზე', 'amount' => 4.28, 'staff_id' => 3, 'currency' => 'GEL', 'customer_id' => 8, 'store_id' => 1, 'pay_type' => 0, 'ref_id' => 5275],
                ['id' => 5282, 'version' => 'AAAAAAAB3Tg=', 'date' => '2018-12-27 18:36:25', 'doc_num' => '49', 'waybill_num' => '0431440360', 'doc_type' => 23, 'purpose' => 'საცალო გაყიდვა № 49', 'amount' => 4.50, 'staff_id' => 0, 'currency' => 'GEL', 'customer_id' => 0, 'store_id' => 1, 'pay_type' => 0, 'ref_id' => 0],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->realizesJournalTyped(CarbonImmutable::create(2018, 1, 1), CarbonImmutable::create(2018, 12, 31, 23, 59, 59));

        $this->assertSame(5275, $dto->journals[0]->refId, 'the customer order the sale was made from');
        $this->assertSame(0, $dto->journals[1]->refId, '0 = not made from an order');
        $this->assertSame(5275, $dto->toArray()['journals'][0]['ref_id']);
        $this->assertSame(
            ['id', 'version', 'date', 'doc_num', 'waybill_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'customer_id', 'store_id', 'pay_type', 'ref_id', 'raw'],
            array_keys($dto->journals[0]->toArray())
        );
        $this->assertNull(RealizesJournalRowDto::fromArray(['id' => 1, 'version' => 'v'])->refId);
    }

    public function test_customers_money_journal_reads_ref_id(): void
    {
        $this->fakeReporting('getCustomersMoneyJournal', [
            'journals' => [
                ['id' => 3122, 'version' => 'AAAAAAACOnw=', 'date' => '2018-10-19 16:05:04', 'doc_num' => '5', 'doc_type' => 49, 'purpose' => 'ავანსის მიღება მყიდველისგან - რონალდ რეიგანი', 'amount' => 12.00, 'staff_id' => 4, 'currency' => 'USD', 'customer_id' => 8, 'pay_type' => 4, 'pay_type_id' => 11, 'ref_id' => 0],
                ['id' => 3123, 'version' => 'AAAAAAABf5I=', 'date' => '2018-10-19 16:08:48', 'doc_num' => '5', 'doc_type' => 38, 'purpose' => 'თანხის მიღება მყიდველისგან - რონალდ რეიგანი', 'amount' => 10.00, 'staff_id' => 0, 'currency' => 'GEL', 'customer_id' => 8, 'pay_type' => 1, 'pay_type_id' => 1, 'ref_id' => 5280],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->customersMoneyJournalTyped(CarbonImmutable::create(2018, 1, 1), CarbonImmutable::create(2018, 12, 31, 23, 59, 59));

        $this->assertSame(0, $dto->journals[0]->refId);
        $this->assertSame(5280, $dto->journals[1]->refId, 'the operation the money was received for');
        $this->assertSame(5280, $dto->toArray()['journals'][1]['ref_id']);
        $this->assertNull(MoneyJournalRowDto::fromArray(['id' => 4122, 'vendor_id' => 18])->refId, 'the vendors journal sends no ref_id');
    }

    // -------------------------------------------------------
    // getCafeOrderDetailedReport: user_id, guest_count
    // -------------------------------------------------------

    public function test_cafe_order_report_reads_user_and_guest_count(): void
    {
        $this->fakeReporting('getCafeOrderDetailedReport', [
            'reports' => [
                ['date' => '2020-08-21T13:01:28.913', 'doc_num' => '20190228100000', 'store_id' => 1, 'status_id' => 1, 'user_id' => 21, 'guest_count' => 1, 'product_id' => 1, 'group_id' => 1, 'unit_id' => 1, 'quantity' => 1, 'amount' => 2.5],
                ['date' => '2020-09-22T08:21:27', 'doc_num' => '0548560688', 'store_id' => 1, 'status_id' => 1, 'user_id' => 2, 'guest_count' => 1, 'product_id' => 58, 'group_id' => 1, 'unit_id' => 1, 'quantity' => 1, 'amount' => 3],
            ],
            'ex' => null,
        ]);

        $dto = $this->client()->reporting()->cafeOrderDetailedReportTyped(CarbonImmutable::create(2020, 1, 1), CarbonImmutable::create(2020, 12, 31, 23, 59, 59));

        $this->assertSame(21, $dto->reports[0]->userId);
        $this->assertSame(1, $dto->reports[0]->guestCount);
        $this->assertSame(2, $dto->reports[1]->userId);
        $this->assertSame(58, $dto->reports[1]->productId);
        $this->assertSame(
            ['date', 'doc_num', 'store_id', 'status_id', 'user_id', 'guest_count', 'product_id', 'group_id', 'unit_id', 'quantity', 'amount', 'raw'],
            array_keys($dto->reports[0]->toArray())
        );

        $old = CafeOrderDetailedReportRowDto::fromArray(['doc_num' => '1', 'status_id' => 2]);
        $this->assertNull($old->userId);
        $this->assertNull($old->guestCount);
    }

    // -------------------------------------------------------
    // getStores: add_fields (10.0.20261027)
    // -------------------------------------------------------

    public function test_stores_typed_carry_add_fields(): void
    {
        $this->fakeOperation('getStores', [
            'stores' => [
                ['id' => 1, 'group_id' => 2, 'name' => 'მთავარი საწყობი', 'address' => '', 'project_id' => 1, 'add_fields' => [['field' => 'usr_column_520', 'value' => '120']]],
                ['id' => 2, 'group_id' => 2, 'name' => 'საწყობი 2', 'address' => '', 'project_id' => 2, 'add_fields' => [['field' => 'usr_column_520', 'value' => '85']]],
            ],
            'ex' => null,
        ]);

        $stores = $this->client()->reference()->storesTyped();

        Http::assertSent(fn (Request $r) => $r->method() === 'GET' && $r->url() === 'http://fina-test.local:5007/api/operation/getStores');
        $this->assertCount(2, $stores);
        $this->assertInstanceOf(StoreDto::class, $stores[0]);
        $this->assertSame(1, $stores[0]->id);
        $this->assertSame(2, $stores[0]->groupId);
        $this->assertSame('მთავარი საწყობი', $stores[0]->name);
        $this->assertSame('', $stores[0]->address);
        $this->assertSame(1, $stores[0]->projectId);
        $this->assertSame([['field' => 'usr_column_520', 'value' => '120']], $stores[0]->addFields);
        $this->assertSame('85', $stores[1]->addFields[0]['value']);

        $old = StoreDto::fromArray(['id' => 6, 'name' => 'x']);
        $this->assertNull($old->addFields, 'a build without add_fields: unknown, not "no fields"');
        $this->assertNull($old->groupId);
        $this->assertNull($old->address);
        $this->assertNull($old->projectId);
        $this->assertSame([], StoreDto::fromArray(['id' => 6, 'add_fields' => []])->addFields, 'an empty list FINA sends is kept');
        $this->assertSame(
            [['field' => 'x', 'value' => null], ['field' => null, 'value' => '']],
            StoreDto::fromArray(['id' => 6, 'add_fields' => [['field' => 'x'], ['value' => '']]])->addFields,
            'a value not sent is not an empty value'
        );
        $this->assertSame('120', $this->client()->reference()->stores()['stores'][0]['add_fields'][0]['value'], 'the raw method is unchanged');
    }

    // -------------------------------------------------------
    // contragents: birth_date, marketing_promotions
    // -------------------------------------------------------

    public function test_customers_by_code_typed(): void
    {
        $this->fakeOperation('getCustomersByCode/12345678910', [
            'contragents' => [[
                'id' => 1, 'group_id' => 5, 'code' => '12345678910', 'name' => 'სატესტო სატესტო', 'address' => '', 'phone' => '', 'email' => '',
                'is_company' => true, 'is_resident' => true, 'vat_type' => 1, 'cons_period' => 30, 'birth_date' => '2018-08-21T00:00:00',
                'marketing_promotions' => true, 'add_fields' => [['field' => 'usr_column_515', 'value' => '']],
            ]],
            'ex' => null,
        ]);

        $found = $this->client()->customers()->getByCodeTyped('12345678910');

        Http::assertSent(fn (Request $r) => $r->url() === 'http://fina-test.local:5007/api/operation/getCustomersByCode/12345678910');
        $this->assertCount(1, $found);
        $c = $found[0];
        $this->assertInstanceOf(ContragentDto::class, $c);
        $this->assertSame(1, $c->id);
        $this->assertSame(5, $c->groupId);
        $this->assertSame('12345678910', $c->code);
        $this->assertSame('სატესტო სატესტო', $c->name);
        $this->assertTrue($c->isCompany);
        $this->assertTrue($c->isResident);
        $this->assertSame(1, $c->vatType);
        $this->assertSame(30, $c->consPeriod);
        $this->assertSame('2018-08-21T00:00:00', $c->birthDate);
        $this->assertTrue($c->marketingPromotions);
        $this->assertSame([['field' => 'usr_column_515', 'value' => '']], $c->addFields);
    }

    public function test_customers_and_vendors_typed_lists(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getCustomers' => Http::response(['contragents' => [['id' => 1, 'group_id' => 5, 'code' => '12345678910', 'birth_date' => '2011-08-21T00:00:00', 'marketing_promotions' => true]], 'ex' => null]),
            '*/api/operation/getVendors' => Http::response(['contragents' => [['id' => 2, 'group_id' => 3, 'code' => '00112233665', 'name' => 'X ვენდორ', 'birth_date' => '2018-08-21T00:00:00', 'marketing_promotions' => false]], 'ex' => null]),
            '*/api/operation/getVendorsByCode/*' => Http::response(['contragents' => [['id' => 2, 'code' => '00112233665', 'marketing_promotions' => true]], 'ex' => null]),
        ]);

        $customers = $this->client()->customers()->allTyped();
        $vendors = $this->client()->vendors()->allTyped();
        $byCode = $this->client()->vendors()->getByCodeTyped('00112233665');

        $this->assertSame('2011-08-21T00:00:00', $customers[0]->birthDate);
        $this->assertTrue($customers[0]->marketingPromotions);
        $this->assertSame(3, $vendors[0]->groupId);
        $this->assertSame('X ვენდორ', $vendors[0]->name);
        $this->assertFalse($vendors[0]->marketingPromotions);
        $this->assertTrue($byCode[0]->marketingPromotions);
        Http::assertSent(fn (Request $r) => $r->url() === 'http://fina-test.local:5007/api/operation/getVendorsByCode/00112233665');
    }

    public function test_contragent_fields_a_build_does_not_send_stay_null(): void
    {
        $old = ContragentDto::fromArray(['id' => 9, 'code' => '01', 'name' => 'n']);

        $this->assertNull($old->birthDate);
        $this->assertNull($old->marketingPromotions, 'unknown is not "not allowed"');
        $this->assertNull($old->addFields, 'unknown is not "no fields"');
        $this->assertNull($old->vatType, 'unknown is not 0 (not a VAT payer)');
        $this->assertNull($old->isCompany);
        $this->assertNull($old->isResident);
        $this->assertNull($old->consPeriod);
        $this->assertNull($old->groupId);
        $this->assertNull($old->address);
        $this->assertNull($old->phone);
        $this->assertNull($old->email);
        $this->assertFalse(ContragentDto::fromArray(['marketing_promotions' => false])->marketingPromotions);

        $sent = ContragentDto::fromArray(['id' => 9, 'vat_type' => 0, 'is_company' => false, 'cons_period' => 0, 'address' => '', 'add_fields' => []]);
        $this->assertSame(0, $sent->vatType, 'a 0 FINA sends is kept');
        $this->assertFalse($sent->isCompany);
        $this->assertSame(0, $sent->consPeriod);
        $this->assertSame('', $sent->address);
        $this->assertSame([], $sent->addFields);
        $this->assertSame(
            [['field' => 'x', 'value' => null]],
            ContragentDto::fromArray(['id' => 9, 'add_fields' => [['field' => 'x']]])->addFields
        );
    }
}
