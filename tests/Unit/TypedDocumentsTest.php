<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Exceptions\FinaRemoteException;
use Fina\Sdk\Laravel\Operation\Dto\Documents\DocProductLineDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\DocServiceLineDto;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Typed getDoc* responses, built from the exact examples in the FINA 8.0 PDF.
 */
final class TypedDocumentsTest extends TestCase
{
    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    /** @param array<string, mixed> $body */
    private function fakeDoc(string $method, int $id, array $body): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            "*/api/operation/{$method}/{$id}" => Http::response($body),
        ]);
    }

    private function assertFetched(string $method, int $id): void
    {
        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && $r->url() === "http://fina-test.local:5007/api/operation/{$method}/{$id}");
    }

    public function test_product_out_typed(): void
    {
        $this->fakeDoc('getDocProductOut', 6563, [
            'product_out' => [
                'id' => 6563, 'date' => '2019-05-16T15:28:19', 'num_pfx' => '', 'num' => 8624, 'waybill_num' => '0418925004',
                'purpose' => 'საქონლის გაყიდვა №', 'amount' => 780.0, 'currency' => 'GEL', 'rate' => 1.0, 'store' => 2,
                'customer' => 1, 'user' => 1, 'staff' => 1, 'project' => 2, 'is_vat' => true, 'make_entry' => true,
                'pay_type' => 2, 'price_type' => 3, 'w_type' => 2, 't_type' => 1, 't_payer' => 1, 'w_cost' => 0.0,
                'foreign' => false, 'drv_name' => 'რამინ ხოზრევანიძე', 'tr_start' => 'ქ.ქუთაისი / "+1"¿ს ბაზარი eee',
                'tr_end' => 'წერეტლიე4', 'driver_id' => '61006038420', 'car_num' => 'iu100iu', 'tr_text' => '',
                'sender' => '', 'reciever' => '', 'comment' => '', 'overlap_type' => 0, 'overlap_amount' => 0.0,
                'check_status' => true,
                'add_fields' => [['field' => 'usr_column_510', 'value' => ''], ['field' => 'usr_column_527', 'value' => ''], ['field' => 'usr_column_528', 'value' => '']],
                'products' => [['id' => 29, 'sub_id' => 1, 'price' => 4.5, 'quantity' => 120.0], ['id' => 28, 'sub_id' => 0, 'price' => 20.0, 'quantity' => 12.0]],
                'services' => [],
            ],
            'ex' => null,
        ]);

        $doc = $this->client()->documents()->getProductOutTyped(6563);
        $this->assertFetched('getDocProductOut', 6563);

        $this->assertSame(6563, $doc->id);
        $this->assertSame('2019-05-16T15:28:19', $doc->date);
        $this->assertSame('', $doc->numPfx);
        $this->assertSame(8624, $doc->num);
        $this->assertSame('0418925004', $doc->waybillNum);
        $this->assertSame('საქონლის გაყიდვა №', $doc->purpose);
        $this->assertSame(780.0, $doc->amount);
        $this->assertSame('GEL', $doc->currency);
        $this->assertSame(1.0, $doc->rate);
        $this->assertSame(2, $doc->store);
        $this->assertSame(1, $doc->customer);
        $this->assertSame(1, $doc->user);
        $this->assertSame(1, $doc->staff);
        $this->assertSame(2, $doc->project);
        $this->assertTrue($doc->isVat);
        $this->assertTrue($doc->makeEntry);
        $this->assertSame(2, $doc->payType);
        $this->assertSame(3, $doc->priceType);
        $this->assertSame(2, $doc->wType);
        $this->assertSame(1, $doc->tType);
        $this->assertSame(1, $doc->tPayer);
        $this->assertSame(0.0, $doc->wCost);
        $this->assertFalse($doc->foreign);
        $this->assertSame('რამინ ხოზრევანიძე', $doc->drvName);
        $this->assertSame('წერეტლიე4', $doc->trEnd);
        $this->assertSame('61006038420', $doc->driverId);
        $this->assertSame('iu100iu', $doc->carNum);
        $this->assertSame('', $doc->reciever);
        $this->assertSame(0, $doc->overlapType);
        $this->assertTrue($doc->checkStatus);
        $this->assertSame([['field' => 'usr_column_510', 'value' => ''], ['field' => 'usr_column_527', 'value' => ''], ['field' => 'usr_column_528', 'value' => '']], $doc->addFields);

        $this->assertCount(2, $doc->products);
        $line = $doc->products[0];
        $this->assertInstanceOf(DocProductLineDto::class, $line);
        $this->assertSame(29, $line->id);
        $this->assertSame(1, $line->subId);
        $this->assertSame(4.5, $line->price);
        $this->assertSame(120.0, $line->quantity);
        $this->assertNull($line->selfCost);
        $this->assertNull($line->outId);
        $this->assertSame(['id' => 29, 'sub_id' => 1, 'quantity' => 120.0, 'price' => 4.5], $line->toArray());
        $this->assertSame([], $doc->services);
        $this->assertSame(6563, $doc->raw['id']);
    }

    public function test_product_out_single_typed_keeps_repeated_product_lines(): void
    {
        $this->fakeDoc('getDocProductOutSingle', 16902, [
            'product_out_single' => [
                'id' => 16902, 'date' => '2020-08-26T23:59:58', 'num_pfx' => '', 'num' => 20,
                'purpose' => 'დაჯგუფებული საცალო გაყიდვა staff1', 'amount' => 11.0, 'currency' => 'GEL', 'rate' => 1.0,
                'store' => 1, 'user' => 1, 'staff' => 2, 'project' => 1, 'is_vat' => true, 'make_entry' => true, 'pay_type' => 6,
                'add_fields' => [],
                'products' => [['id' => 1, 'sub_id' => 0, 'price' => 5.0, 'quantity' => 1.0], ['id' => 1, 'sub_id' => 0, 'price' => 6.0, 'quantity' => 1.0]],
            ],
            'ex' => null,
        ]);

        $doc = $this->client()->documents()->getProductOutSingleTyped(16902);
        $this->assertFetched('getDocProductOutSingle', 16902);

        $this->assertSame(16902, $doc->id);
        $this->assertSame(20, $doc->num);
        $this->assertSame('დაჯგუფებული საცალო გაყიდვა staff1', $doc->purpose);
        $this->assertSame(11.0, $doc->amount);
        $this->assertSame(1, $doc->store);
        $this->assertSame(2, $doc->staff);
        $this->assertSame(6, $doc->payType);
        $this->assertTrue($doc->makeEntry);
        $this->assertSame([], $doc->addFields);
        $this->assertCount(2, $doc->products);
        $this->assertSame([1, 1], array_map(static fn (DocProductLineDto $l) => $l->id, $doc->products));
        $this->assertSame([5.0, 6.0], array_map(static fn (DocProductLineDto $l) => $l->price, $doc->products));
        $this->assertSame(11.0, array_sum(array_map(static fn (DocProductLineDto $l) => $l->price * $l->quantity, $doc->products)));
    }

    public function test_product_move_typed_has_cost_lines_without_price(): void
    {
        $this->fakeDoc('getDocProductMove', 14830, [
            'product_move' => [
                'id' => 14830, 'date' => '2020-03-18T10:24:13', 'num_pfx' => '', 'num' => 11, 'waybill_num' => null,
                'purpose' => 'საქონლის გადატანა № 11', 'amount' => 18.69858, 'store_from' => 1, 'store_to' => 2, 'user' => 1,
                'staff' => 2, 'make_entry' => true, 't_type' => 1, 't_payer' => 1, 'w_cost' => 0.0, 'foreign' => false,
                'drv_name' => '', 'tr_start' => '', 'tr_end' => '', 'driver_id' => '', 'car_num' => '', 'tr_text' => '',
                'add_fields' => [['field' => 'usr_column_518', 'value' => '12:30']],
                'products' => [['id' => 12, 'sub_id' => 0, 'self_cost' => 1.01090475053712, 'quantity' => 5.0], ['id' => 13, 'sub_id' => 0, 'self_cost' => 1.94915122076992, 'quantity' => 7.0]],
            ],
            'ex' => null,
        ]);

        $doc = $this->client()->documents()->getProductMoveTyped(14830);
        $this->assertFetched('getDocProductMove', 14830);

        $this->assertSame(14830, $doc->id);
        $this->assertNull($doc->waybillNum);
        $this->assertSame(18.69858, $doc->amount);
        $this->assertSame(1, $doc->storeFrom);
        $this->assertSame(2, $doc->storeTo);
        $this->assertSame(2, $doc->staff);
        $this->assertTrue($doc->makeEntry);
        $this->assertSame(1, $doc->tType);
        $this->assertSame([['field' => 'usr_column_518', 'value' => '12:30']], $doc->addFields);
        $this->assertCount(2, $doc->products);
        $this->assertNull($doc->products[0]->price);
        $this->assertSame(1.01090475053712, $doc->products[0]->selfCost);
        $this->assertSame(7.0, $doc->products[1]->quantity);
        $this->assertSame(['id' => 13, 'sub_id' => 0, 'quantity' => 7.0, 'self_cost' => 1.94915122076992], $doc->products[1]->toArray());
    }

    public function test_customer_return_typed_links_lines_to_the_original_sale(): void
    {
        $this->fakeDoc('getDocCustomerReturn', 6552, [
            'customer_return' => [
                'id' => 6552, 'date' => '2019-05-01T14:25:00', 'num_pfx' => '', 'num' => 10, 'waybill_num' => '0526302888',
                'purpose' => 'dabruneba', 'amount' => 65.7, 'currency' => 'GEL', 'rate' => 1.0, 'store' => 1, 'customer' => 8,
                'user' => 2, 'staff' => 3, 'project' => 2, 'is_vat' => true, 'make_entry' => true, 'pay_type' => 1,
                't_type' => 1, 't_payer' => 2, 'w_cost' => 0.5, 'foreign' => false, 'drv_name' => 'მძღოლის სახელი, გვარი',
                'tr_start' => 'ტრანსპორტირების დაწყების ადგილი', 'tr_end' => 'ტრანსპორტირების დასრულების ადგილი',
                'driver_id' => '12345678910', 'car_num' => 'SAK001', 'tr_text' => 'მისაბმელი/გადამზიდავი',
                'products' => [
                    ['id' => 29, 'sub_id' => 0, 'price' => 35.7, 'self_cost' => 1.69491525423729, 'quantity' => 1.0, 'out_id' => 6547],
                    ['id' => 3, 'sub_id' => 0, 'price' => 10.0, 'self_cost' => 8.47457627118644, 'quantity' => 3.0, 'out_id' => 0],
                ],
            ],
            'ex' => null,
        ]);

        $doc = $this->client()->documents()->getCustomerReturnTyped(6552);
        $this->assertFetched('getDocCustomerReturn', 6552);

        $this->assertSame(6552, $doc->id);
        $this->assertSame('0526302888', $doc->waybillNum);
        $this->assertSame(65.7, $doc->amount);
        $this->assertSame(8, $doc->customer);
        $this->assertSame(2, $doc->user);
        $this->assertSame(3, $doc->staff);
        $this->assertSame(1, $doc->payType);
        $this->assertSame(2, $doc->tPayer);
        $this->assertSame(0.5, $doc->wCost);
        $this->assertSame('SAK001', $doc->carNum);
        $this->assertCount(2, $doc->products);
        $this->assertSame(6547, $doc->products[0]->outId);
        $this->assertSame(0, $doc->products[1]->outId);
        $this->assertSame(35.7, $doc->products[0]->price);
        $this->assertSame(1.69491525423729, $doc->products[0]->selfCost);
        $this->assertSame(65.7, array_sum(array_map(static fn (DocProductLineDto $l) => $l->price * $l->quantity, $doc->products)));
    }

    public function test_customer_order_typed(): void
    {
        $this->fakeDoc('getDocCustomerOrder', 4165, [
            'customer_order' => [
                'id' => 4165, 'date' => '2018-12-03T11:50:45.517', 'num_pfx' => 't', 'num' => 8, 'purpose' => 'შეკვეთა მყიდველისგან № 8',
                'amount' => 4.58, 'currency' => 'USD', 'rate' => 2.6744, 'store' => 1, 'customer' => 1, 'user' => 1, 'staff' => 3,
                'project' => 1, 'is_vat' => true, 'pay_type' => 1, 'tr_start' => '', 'tr_end' => '', 'reserved' => true, 'order_status' => 1,
                'add_fields' => [['field' => 'usr_column_511', 'value' => '41.710456, 44.769520']],
                'products' => [['id' => 2, 'sub_id' => 1, 'price' => 0.93, 'quantity' => 2], ['id' => 1, 'sub_id' => 0, 'price' => 2.71, 'quantity' => 1]],
                'services' => [['id' => 11, 'quantity' => 2.0, 'price' => 120.0]],
            ],
            'ex' => null,
        ]);

        $doc = $this->client()->documents()->getCustomerOrderTyped(4165);
        $this->assertFetched('getDocCustomerOrder', 4165);

        $this->assertSame(4165, $doc->id);
        $this->assertSame('t', $doc->numPfx);
        $this->assertSame(8, $doc->num);
        $this->assertSame('USD', $doc->currency);
        $this->assertSame(2.6744, $doc->rate);
        $this->assertSame(3, $doc->staff);
        $this->assertTrue($doc->reserved);
        $this->assertSame(1, $doc->orderStatus);
        $this->assertSame('41.710456, 44.769520', $doc->addFields[0]['value']);
        $this->assertSame(2.0, $doc->products[0]->quantity);
        $this->assertSame(1, $doc->products[0]->subId);
        $this->assertCount(1, $doc->services);
        $this->assertInstanceOf(DocServiceLineDto::class, $doc->services[0]);
        $this->assertSame(11, $doc->services[0]->id);
        $this->assertSame(120.0, $doc->services[0]->price);
        $this->assertSame(['id' => 11, 'quantity' => 2.0, 'price' => 120.0], $doc->services[0]->toArray());
    }

    public function test_typed_getters_surface_ex(): void
    {
        $this->fakeDoc('getDocProductOut', 9, ['product_out' => null, 'ex' => 'operation not found']);

        $this->expectException(FinaRemoteException::class);

        $this->client()->documents()->getProductOutTyped(9);
    }

    public function test_typed_getters_tolerate_missing_root_key(): void
    {
        $this->fakeDoc('getDocProductMove', 1, ['ex' => null]);

        $doc = $this->client()->documents()->getProductMoveTyped(1);

        $this->assertSame(0, $doc->id);
        $this->assertSame([], $doc->products);
    }
}
