<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Exceptions\FinaRemoteException;
use Fina\Sdk\Laravel\Operation\Dto\AccountValueDto;
use Fina\Sdk\Laravel\Operation\Dto\AdditionalFieldDescriptorDto;
use Fina\Sdk\Laravel\Operation\Dto\RsStatusResponse;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Operation endpoints added for PDF 8.0 parity: exact verb, path, body and
 * root key per the documentation, plus the `ex` error path.
 */
final class PdfOperationEndpointsTest extends TestCase
{
    private const BASE = 'http://fina-test.local:5007/api/operation/';

    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    /** @param array<string, mixed> $body */
    private function fake(string $path, array $body): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/'.$path => Http::response($body),
        ]);
    }

    private function assertGet(string $path): void
    {
        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && $r->url() === self::BASE.$path
            && $r->hasHeader('Authorization', 'Bearer tok'));
    }

    /** @param array<mixed> $expectedBody */
    private function assertPost(string $path, array $expectedBody): void
    {
        Http::assertSent(function (Request $r) use ($path, $expectedBody) {
            return $r->method() === 'POST'
                && $r->url() === self::BASE.$path
                && $r->hasHeader('Authorization', 'Bearer tok')
                && json_decode($r->body(), true) === $expectedBody;
        });
    }

    // -------------------------------------------------------
    // Raw GET endpoints: method -> [path, root key, example row]
    // -------------------------------------------------------

    /** @return iterable<string, array{callable(FinaClient): array, string, string, array<string, mixed>}> */
    public static function rawGetEndpoints(): iterable
    {
        yield 'products.restByStore' => [fn (FinaClient $c) => $c->products()->restByStore(2), 'getProductsRestByStore/2', 'store_rest', ['id' => 1, 'rest' => 0]];
        yield 'products.subRest' => [fn (FinaClient $c) => $c->products()->subRest(), 'getSubProductsRest', 'rest', ['id' => 9, 'sub_id' => 1, 'store' => 1, 'rest' => 6, 'reserve' => 0]];
        yield 'products.images' => [fn (FinaClient $c) => $c->products()->images(5), 'getProductImages/5', 'images', ['id' => 1, 'image' => 'image base64 string']];
        yield 'products.places' => [fn (FinaClient $c) => $c->products()->places(), 'getProductPlaces', 'places', ['product_id' => 10, 'store_id' => 1, 'place' => 'თარო#5']];
        yield 'products.characteristicValues' => [fn (FinaClient $c) => $c->products()->characteristicValues(), 'getCharacteristicValues', 'characteristic_values', ['product_id' => 43, 'characteristic_id' => 1, 'value' => 'შავი', 'value2' => 'black', 'value3' => 'чорний']];
        yield 'products.packed' => [fn (FinaClient $c) => $c->products()->packed(), 'getPackedProducts', 'pack_info', ['product_id' => 1, 'pack_id' => 1]];
        yield 'products.subCodeTypes' => [fn (FinaClient $c) => $c->products()->subCodeTypes(), 'getSubCodeTypes', 'types', ['name' => 'sub_column_1', 'header' => 'სერია']];
        yield 'products.subCodes' => [fn (FinaClient $c) => $c->products()->subCodes(), 'getProductSubCodes', 'sub_codes', ['product_id' => 9, 'sub_id' => 1, 'barcode' => '', 'sub_columns' => [['field' => 'sub_column_1', 'value' => '001']]]];
        yield 'products.onWay' => [fn (FinaClient $c) => $c->products()->onWay(), 'getProductsOnWay', 'on_way', ['id' => 10, 'date' => '2018-09-19', 'quantity' => 10]];
        yield 'services.provided' => [fn (FinaClient $c) => $c->services()->provided(), 'getProvidedServices', 'services', ['id' => 8, 'group_id' => 110, 'unit_id' => 17, 'code' => '02', 'name' => 'მომსახურება', 'comment' => '', 'vat' => 1, 'add_fields' => []]];
        yield 'services.received' => [fn (FinaClient $c) => $c->services()->received(), 'getReceivedServices', 'services', ['id' => 7, 'group_id' => 132, 'unit_id' => 17, 'code' => '151515', 'name' => 'მასაჟი', 'comment' => '', 'vat' => 1, 'add_fields' => [['field' => 'usr_column_535', 'value' => '13']]]];
        yield 'services.providedPrices' => [fn (FinaClient $c) => $c->services()->providedPrices(), 'getProvidedServicePrices', 'prices', ['product_id' => 1, 'price_id' => 3, 'price' => 20.0, 'discount_price' => 0.0, 'currency' => 'GEL', 'discount_start' => null, 'discount_end' => null]];
        yield 'inventories.all' => [fn (FinaClient $c) => $c->inventories()->all(), 'getInventories', 'inventories', ['id' => 22, 'group_id' => 48, 'unit_id' => 1, 'code' => '00124', 'name' => 'trans', 'comment' => '', 'in_date' => '2018-12-26T17:24:03', 'amortization_type' => 1, 'service_term' => 12.0, 'liquidation_cost' => 50.0, 'add_fields' => []]];
        yield 'inventories.rest' => [fn (FinaClient $c) => $c->inventories()->rest(), 'getInventoriesRest', 'rest', ['id' => 14, 'store' => 1, 'rest' => 0, 'reserve' => 2]];
        yield 'inventories.restByStore' => [fn (FinaClient $c) => $c->inventories()->restByStore(3), 'getInventoriesRestByStore/3', 'store_rest', ['id' => 14, 'rest' => 0]];
        yield 'documents.getInventoryOut' => [fn (FinaClient $c) => $c->documents()->getInventoryOut(6536), 'getDocInventoryOut/6536', 'inventory_out', ['id' => 6536, 'inventories' => [['id' => 24, 'price' => 114.0, 'self_cost' => 5000.0, 'quantity' => 1.0]]]];
        yield 'documents.getInventoryMove' => [fn (FinaClient $c) => $c->documents()->getInventoryMove(14831), 'getDocInventoryMove/14831', 'inventory_move', ['id' => 14831, 'store_from' => 1, 'store_to' => 6]];
        yield 'documents.getCustomerInventoryReturn' => [fn (FinaClient $c) => $c->documents()->getCustomerInventoryReturn(8743), 'getDocCustomerInventoryReturn/8743', 'customer_inventory_return', ['id' => 8743, 'customer' => 30]];
        yield 'documents.getProvidedService' => [fn (FinaClient $c) => $c->documents()->getProvidedService(6564), 'getDocProvidedService/6564', 'doc_providedservice', ['id' => 6564, 'services' => [['id' => 11, 'price' => 120.0, 'quantity' => 2.0]]]];
        yield 'documents.getDiscountCard' => [fn (FinaClient $c) => $c->documents()->getDiscountCard(8733), 'getDocDiscountCard/8733', 'discount_card', ['id' => 8733, 'card_code' => '2311', 'discount_id' => 2, 'status' => true, 'ref_operatons' => [8737, 8738]]];
    }

    /**
     * @param  callable(FinaClient): array  $call
     * @param  array<string, mixed>  $row
     */
    #[DataProvider('rawGetEndpoints')]
    public function test_raw_get_endpoint(callable $call, string $path, string $rootKey, array $row): void
    {
        $payload = str_starts_with($rootKey, 'inventory_') || in_array($rootKey, ['customer_inventory_return', 'doc_providedservice', 'discount_card'], true)
            ? [$rootKey => $row, 'ex' => null]
            : [$rootKey => [$row], 'ex' => null];

        $this->fake($path, $payload);

        $result = $call($this->client());

        $this->assertGet($path);
        $this->assertSame(json_decode((string) json_encode($payload), true), $result);
    }

    /**
     * @param  callable(FinaClient): array  $call
     * @param  array<string, mixed>  $row
     */
    #[DataProvider('rawGetEndpoints')]
    public function test_raw_get_endpoint_throws_on_ex(callable $call, string $path, string $rootKey, array $row): void
    {
        $this->fake($path, [$rootKey => null, 'ex' => 'Object reference not set']);

        try {
            $call($this->client());
            $this->fail('Expected FinaRemoteException');
        } catch (FinaRemoteException $e) {
            $this->assertSame('Object reference not set', $e->ex);
            $this->assertStringContainsString('operation.'.explode('/', $path)[0], $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // POST endpoints with documented bodies
    // -------------------------------------------------------

    public function test_products_self_cost_posts_prods_and_date(): void
    {
        $this->fake('getProductsSelfCost', ['cost_info' => [['id' => 1, 'cost' => 32.751412429378533], ['id' => 2, 'cost' => 4.0]], 'ex' => null]);

        $result = $this->client()->products()->selfCost([1, 2], CarbonImmutable::create(2018, 11, 15, 23, 59, 59));

        $this->assertPost('getProductsSelfCost', ['prods' => [1, 2], 'date' => '2018-11-15T23:59:59']);
        $this->assertSame(32.751412429378533, $result['cost_info'][0]['cost']);
        $this->assertEqualsWithDelta(4.0, $result['cost_info'][1]['cost'], 0.0);
    }

    public function test_products_prices_advance_defaults_to_retail_price_type(): void
    {
        $this->fake('getProductPricesAdvance', ['prices' => [['product_id' => 1, 'price_id' => 3, 'price' => 20.0, 'discount_price' => 7.0, 'currency' => 'GEL', 'discount_start' => '2018-12-03T00:00:00', 'discount_end' => '2018-12-05T00:00:00']], 'ex' => null]);

        $result = $this->client()->products()->pricesAdvance([1, 2]);

        $this->assertPost('getProductPricesAdvance', ['prods' => [1, 2], 'price' => 3]);
        $this->assertEqualsWithDelta(7.0, $result['prices'][0]['discount_price'], 0.0);

        $this->client()->products()->pricesAdvance([7 => 9], 5);
        $this->assertPost('getProductPricesAdvance', ['prods' => [9], 'price' => 5]);
    }

    public function test_products_rest_advance_posts_store_and_price_type(): void
    {
        $this->fake('getProductsRestAdvance', ['rest_info' => [['id' => 1, 'rest' => 0, 'price' => 7.25], ['id' => 2, 'rest' => 1, 'price' => 2.5]], 'ex' => null]);

        $result = $this->client()->products()->restAdvance([1, 2]);
        $this->assertPost('getProductsRestAdvance', ['prods' => [1, 2], 'store' => 0, 'price' => 3]);
        $this->assertSame(7.25, $result['rest_info'][0]['price']);

        $this->client()->products()->restAdvance([1], 4, 6);
        $this->assertPost('getProductsRestAdvance', ['prods' => [1], 'store' => 4, 'price' => 6]);
    }

    public function test_products_characteristic_values_by_ids_posts_bare_id_array(): void
    {
        $this->fake('getCharacteristicValuesArray', ['characteristic_values' => [['product_id' => 43, 'characteristic_id' => 1, 'value' => 'შავი', 'value2' => 'black', 'value3' => 'чорний']], 'ex' => null]);

        $result = $this->client()->products()->characteristicValuesByProductIds(['a' => 43, 'b' => 44]);

        $this->assertPost('getCharacteristicValuesArray', [43, 44]);
        $this->assertSame('black', $result['characteristic_values'][0]['value2']);
    }

    public function test_inventories_rest_by_ids_and_rest_advance(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getInventoriesRestArray' => Http::response(['rest' => [['id' => 14, 'store' => 1, 'rest' => 0, 'reserve' => 2]], 'ex' => null]),
            '*/api/operation/getInventoriesRestAdvance' => Http::response(['rest_info' => [['id' => 1, 'rest' => 0, 'price' => 7.25]], 'ex' => null]),
        ]);

        $this->assertSame(14, $this->client()->inventories()->restByIds([14, 21])['rest'][0]['id']);
        $this->assertPost('getInventoriesRestArray', ['prods' => [14, 21]]);

        $this->assertSame(7.25, $this->client()->inventories()->restAdvance([1, 2], 1, 3)['rest_info'][0]['price']);
        $this->assertPost('getInventoriesRestAdvance', ['prods' => [1, 2], 'store' => 1, 'price' => 3]);
    }

    public function test_reference_account_value(): void
    {
        $this->fake('getAccountValue', ['values' => ['debit_val' => 1320.00, 'credit_val' => 0.00], 'ex' => null]);

        $dto = $this->client()->reference()->accountValue('1410', CarbonImmutable::create(2019, 9, 12, 13, 0, 0), 6);

        $this->assertPost('getAccountValue', ['primary_acc' => '1410', 'secondary_acc' => '', 'date' => '2019-09-12T13:00:00', 'obj' => 6, 'is_gel' => true]);
        $this->assertInstanceOf(AccountValueDto::class, $dto);
        $this->assertSame(1320.0, $dto->debitVal);
        $this->assertSame(0.0, $dto->creditVal);
    }

    public function test_update_rs_status(): void
    {
        $this->fake('updateRsStatus', ['status' => true, 'ex' => null]);

        $resp = $this->client()->documents()->updateRsStatus(4164, 352264608, 1, '0123654878');

        $this->assertPost('updateRsStatus', ['id' => 4164, 'w_id' => 352264608, 'w_status' => 1, 'w_num' => '0123654878']);
        $this->assertInstanceOf(RsStatusResponse::class, $resp);
        $this->assertTrue($resp->status);
        $this->assertNull($resp->ex);
    }

    // -------------------------------------------------------
    // Additional-field descriptors
    // -------------------------------------------------------

    /** @return iterable<string, array{callable(FinaClient): array, string, string}> */
    public static function additionalFieldEndpoints(): iterable
    {
        yield 'products' => [fn (FinaClient $c) => $c->products()->additionalFields(), 'getProductAdditionalFields', 'მწარმოებელი კომპანია'];
        yield 'services' => [fn (FinaClient $c) => $c->services()->providedAdditionalFields(), 'getProvidedServiceAdditionalFields', 'About'];
        yield 'inventories' => [fn (FinaClient $c) => $c->inventories()->additionalFields(), 'getInventoryAdditionalFields', 'გახარჯვა'];
        yield 'staff' => [fn (FinaClient $c) => $c->reference()->staffAdditionalFields(), 'getStaffAdditionalFields', 'ასაკი'];
        yield 'doc type 21' => [fn (FinaClient $c) => $c->reference()->docAdditionalFields(21), 'getDocAdditionalFields/21', 'ლოკაცია'];
    }

    /** @param callable(FinaClient): array $call */
    #[DataProvider('additionalFieldEndpoints')]
    public function test_additional_field_descriptors(callable $call, string $path, string $header): void
    {
        $this->fake($path, ['fields' => [['name' => 'usr_column_503', 'header' => $header]], 'ex' => null]);

        $result = $call($this->client());

        $this->assertGet($path);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(AdditionalFieldDescriptorDto::class, $result[0]);
        $this->assertSame('usr_column_503', $result[0]->name);
        $this->assertSame($header, $result[0]->header);
    }

    public function test_store_groups_unwraps_groups(): void
    {
        $this->fake('getStoreGroups', ['groups' => [['id' => 2, 'parent_id' => 1, 'path' => '0#1#2', 'name' => 'საწყობები'], ['id' => 4, 'parent_id' => 1, 'path' => '0#1#4', 'name' => 'ბორტები']], 'ex' => null]);

        $groups = $this->client()->reference()->storeGroups();

        $this->assertGet('getStoreGroups');
        $this->assertCount(2, $groups);
        $this->assertSame('ბორტები', $groups[1]['name']);
    }

    // -------------------------------------------------------
    // Catalog writes (saveCustomer / saveVendor / saveStaff / saveProduct / saveProvidedService)
    // -------------------------------------------------------

    /** @return iterable<string, array{callable(FinaClient, array): SaveDocResponse, string}> */
    public static function catalogSaves(): iterable
    {
        yield 'customer' => [fn (FinaClient $c, array $p) => $c->customers()->save($p), 'saveCustomer'];
        yield 'vendor' => [fn (FinaClient $c, array $p) => $c->vendors()->save($p), 'saveVendor'];
        yield 'staff' => [fn (FinaClient $c, array $p) => $c->reference()->saveStaff($p), 'saveStaff'];
        yield 'product' => [fn (FinaClient $c, array $p) => $c->products()->save($p), 'saveProduct'];
        yield 'provided service' => [fn (FinaClient $c, array $p) => $c->services()->saveProvided($p), 'saveProvidedService'];
    }

    /** @param callable(FinaClient, array): SaveDocResponse $call */
    #[DataProvider('catalogSaves')]
    public function test_catalog_save_posts_the_body_as_is(callable $call, string $path): void
    {
        $this->fake($path, ['id' => 15, 'ex' => null]);

        $body = ['id' => 0, 'code' => '12345678910', 'name' => 'name1', 'group_id' => 5, 'add_fields' => [['field' => 'usr_column_515', 'value' => '20']]];
        $resp = $call($this->client(), $body);

        $this->assertPost($path, $body);
        $this->assertInstanceOf(SaveDocResponse::class, $resp);
        $this->assertSame(15, $resp->id);
        $this->assertNull($resp->ex);
    }

    public function test_card_issuance_saves_hit_the_documented_paths(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveDocDiscountCard' => Http::response(['id' => 2, 'ex' => null]),
            '*/api/operation/saveDocGiftCard' => Http::response(['id' => 2, 'ex' => null]),
        ]);

        $discount = ['id' => 0, 'date' => '2019-11-08T18:00:00', 'num_pfx' => '', 'num' => 0, 'purpose' => 'ფასდაკლების ბარათის გაცემა', 'customer' => 31, 'store' => 1, 'user' => 1, 'card_code' => '231', 'discount_id' => 1, 'status' => true];
        $this->assertSame(2, $this->client()->documents()->saveDiscountCard($discount)->id);
        $this->assertPost('saveDocDiscountCard', $discount);

        $gift = ['date' => '2019-11-11T18:00:00', 'amount' => 100, 'pay_amount' => 100, 'code' => 'TT3359', 'store' => 1, 'user' => 1];
        $this->assertSame(2, $this->client()->documents()->saveGiftCard($gift)->id);
        $this->assertPost('saveDocGiftCard', $gift);
    }

    public function test_services_and_inventories_accessors_are_memoised(): void
    {
        $client = $this->client();

        $this->assertSame($client->services(), $client->services());
        $this->assertSame($client->inventories(), $client->inventories());
    }
}
