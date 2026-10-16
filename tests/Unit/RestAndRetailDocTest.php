<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Exceptions\FinaHttpException;
use Fina\Sdk\Laravel\Exceptions\FinaRemoteException;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

final class RestAndRetailDocTest extends TestCase
{
    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    /** @return array<string, mixed> */
    private function retailDoc(): array
    {
        return [
            'product_out_single' => [
                'id' => 16902,
                'date' => '2020-08-26T23:59:58',
                'num_pfx' => '',
                'num' => 20,
                'purpose' => 'დაჯგუფებული საცალო გაყიდვა staff1',
                'amount' => 11.0,
                'currency' => 'GEL',
                'rate' => 1.0,
                'store' => 1,
                'user' => 1,
                'staff' => 2,
                'project' => 1,
                'is_vat' => true,
                'make_entry' => true,
                'pay_type' => 6,
                'add_fields' => [],
                'products' => [
                    ['id' => 1, 'sub_id' => 0, 'price' => 5.0, 'quantity' => 1.0],
                    ['id' => 1, 'sub_id' => 0, 'price' => 6.0, 'quantity' => 1.0],
                ],
            ],
            'ex' => null,
        ];
    }

    public function test_rest_gets_current_rest_per_store_with_bearer_token(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok-1', 'ex' => null]),
            '*/api/operation/getProductsRest' => Http::response([
                'rest' => [
                    ['id' => 1, 'store' => 1, 'rest' => 0, 'reserve' => 2],
                    ['id' => 1, 'store' => 2, 'rest' => 1.5, 'reserve' => 0],
                ],
                'ex' => null,
            ]),
        ]);

        $result = $this->client()->products()->rest();

        $this->assertCount(2, $result['rest']);
        $this->assertSame(['id' => 1, 'store' => 2, 'rest' => 1.5, 'reserve' => 0], $result['rest'][1]);

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && $r->url() === 'http://fina-test.local:5007/api/operation/getProductsRest'
            && $r->hasHeader('Authorization', 'Bearer tok-1'));
    }

    public function test_rest_does_not_hit_the_dated_or_array_variants(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/*' => Http::response(['rest' => [], 'ex' => null]),
        ]);

        $this->assertSame([], $this->client()->products()->rest()['rest']);

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'getProductsRestAfter')
            || str_contains($r->url(), 'getProductsRestArray')
            || str_contains($r->url(), 'getProductsRestByStore'));
    }

    public function test_rest_throws_remote_exception_when_fina_returns_ex(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getProductsRest' => Http::response(['rest' => null, 'ex' => 'database is locked']),
        ]);

        try {
            $this->client()->products()->rest();
            $this->fail('Expected FinaRemoteException');
        } catch (FinaRemoteException $e) {
            $this->assertSame('database is locked', $e->ex);
            $this->assertStringContainsString('operation.getProductsRest', $e->getMessage());
        }
    }

    public function test_rest_throws_http_exception_on_server_error(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getProductsRest' => Http::response('boom', 500),
        ]);

        try {
            $this->client()->products()->rest();
            $this->fail('Expected FinaHttpException');
        } catch (FinaHttpException $e) {
            $this->assertSame(500, $e->status);
        }
    }

    public function test_rest_refreshes_an_expired_token_once(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::sequence()
                ->push(['token' => 'old', 'ex' => null])
                ->push(['token' => 'new', 'ex' => null]),
            '*/api/operation/getProductsRest' => Http::sequence()
                ->push('', 401)
                ->push(['rest' => [['id' => 7, 'store' => 1, 'rest' => 3, 'reserve' => 0]], 'ex' => null]),
        ]);

        $this->assertSame(7, $this->client()->products()->rest()['rest'][0]['id']);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'getProductsRest') && $r->hasHeader('Authorization', 'Bearer new'));
    }

    public function test_get_product_out_single_returns_the_retail_document(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getDocProductOutSingle/16902' => Http::response($this->retailDoc()),
        ]);

        $result = $this->client()->documents()->getProductOutSingle(16902);

        $this->assertSame(json_decode((string) json_encode($this->retailDoc()), true), $result);
        $this->assertSame('დაჯგუფებული საცალო გაყიდვა staff1', $result['product_out_single']['purpose']);
        $this->assertCount(2, $result['product_out_single']['products']);

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && $r->url() === 'http://fina-test.local:5007/api/operation/getDocProductOutSingle/16902');
    }

    public function test_get_product_out_single_matches_the_generic_getter(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getDocProductOutSingle/*' => Http::response($this->retailDoc()),
        ]);

        $documents = $this->client()->documents();

        $this->assertSame($documents->getDoc('getDocProductOutSingle', 16902), $documents->getProductOutSingle(16902));
    }

    public function test_get_product_out_single_surfaces_unverified_document_400(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getDocProductOutSingle/555' => Http::response('{"Message":"document not verified"}', 400),
        ]);

        try {
            $this->client()->documents()->getProductOutSingle(555);
            $this->fail('Expected FinaHttpException');
        } catch (FinaHttpException $e) {
            $this->assertSame(400, $e->status);
            $this->assertStringContainsString('not verified', (string) $e->body);
        }
    }

    public function test_get_product_out_single_throws_remote_exception_on_ex(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getDocProductOutSingle/9' => Http::response(['product_out_single' => null, 'ex' => 'operation not found']),
        ]);

        $this->expectException(FinaRemoteException::class);

        $this->client()->documents()->getProductOutSingle(9);
    }
}
