<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Exceptions\FinaHttpException;
use Fina\Sdk\Laravel\Exceptions\FinaRemoteException;
use Fina\Sdk\Laravel\Exceptions\FinaValidationException;
use Fina\Sdk\Laravel\Operation\Dto\CustomerMoneyInPayload;
use Fina\Sdk\Laravel\Operation\Dto\CustomerMoneyReturnPayload;
use Fina\Sdk\Laravel\Operation\Dto\CustomerReturnPayload;
use Fina\Sdk\Laravel\Operation\Dto\ProductLine;
use Fina\Sdk\Laravel\Operation\Dto\ProductOutPayload;
use Fina\Sdk\Laravel\Support\PayloadValidator;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Writes reach FINA at most once per call: the transport never repeats a save*
 * or update*, because a timeout or 5xx after FINA saved would create it twice.
 */
final class WriteSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['fina.retry_times' => 2, 'fina.retry_sleep_ms' => 0]);
    }

    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    private function sent(string $path): int
    {
        return Http::recorded(fn (Request $r) => str_ends_with((string) parse_url($r->url(), PHP_URL_PATH), $path))->count();
    }

    private function productOut(array $overrides = []): ProductOutPayload
    {
        return new ProductOutPayload(...array_merge([
            'id' => 0, 'date' => CarbonImmutable::parse('2026-11-07 14:25'), 'numPrefix' => '', 'num' => 0, 'purpose' => 'x', 'amount' => 1.0,
            'currency' => 'GEL', 'rate' => 1.0, 'store' => 2, 'user' => 1, 'staff' => 0, 'project' => 0, 'customer' => 8,
            'isVat' => true, 'makeEntry' => true, 'payType' => 0, 'wType' => 2, 'tType' => 1, 'tPayer' => 1, 'wCost' => 0.0, 'foreign' => false,
            'products' => [new ProductLine(id: 2, quantity: 1.0, price: 1.0)],
        ], $overrides));
    }

    public function test_a_write_that_gets_a_server_error_is_sent_once(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveDocProductOut' => Http::sequence()->push('bad gateway', 502)->push(['id' => 1, 'ex' => null]),
        ]);

        try {
            $this->client()->documents()->saveProductOut($this->productOut());
            $this->fail('a 502 must surface, not be retried into a second document');
        } catch (FinaHttpException $e) {
            $this->assertSame(502, $e->status);
        }
        $this->assertSame(1, $this->sent('/saveDocProductOut'));
    }

    public function test_a_write_that_times_out_is_sent_once(): void
    {
        $attempts = 0;
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveDocCustomerMoneyIn' => function () use (&$attempts) {
                $attempts++;
                throw new ConnectionException('cURL error 28: timed out');
            },
        ]);

        $this->expectException(ConnectionException::class);
        try {
            $this->client()->documents()->saveCustomerMoneyIn(new CustomerMoneyInPayload(
                id: 0, date: CarbonImmutable::parse('2026-11-07'), numPrefix: '', num: 0, purpose: 'x', amount: 1.0, currency: 'GEL', rate: 1.0,
                store: 2, user: 1, staff: 0, project: 0, customer: 8, payType: 1, payTypeId: 1, refId: 7,
            ));
        } finally {
            $this->assertSame(1, $attempts);
        }
    }

    public function test_reads_and_query_posts_are_still_retried(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getStores' => Http::sequence()->push('busy', 503)->push(['stores' => [], 'ex' => null]),
            '*/api/operation/getProductsArray' => Http::sequence()->push('busy', 503)->push(['products' => [], 'ex' => null]),
        ]);

        $this->client()->request('get', '/api/operation/getStores');
        $this->client()->request('post', '/api/operation/getProductsArray', [1, 2]);

        $this->assertSame(2, $this->sent('/getStores'));
        $this->assertSame(2, $this->sent('/getProductsArray'));
    }

    public function test_an_expired_token_on_a_write_is_refreshed_and_the_write_sent_once_more(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::sequence()->push(['token' => 'old', 'ex' => null])->push(['token' => 'new', 'ex' => null]),
            '*/api/operation/saveDocProductOut' => Http::sequence()->push('expired', 401)->push(['id' => 5, 'ex' => null]),
        ]);

        $saved = $this->client()->documents()->saveProductOut($this->productOut());

        $this->assertSame(5, $saved->id);
        $this->assertSame(2, $this->sent('/saveDocProductOut'), '401 means FINA never processed it: one resend with a fresh token');
    }

    public function test_write_detection(): void
    {
        $this->assertTrue(FinaClient::isWrite('post', '/api/operation/saveDocProductOut'));
        $this->assertTrue(FinaClient::isWrite('POST', 'http://x/api/operation/updateRsStatus'));
        $this->assertTrue(FinaClient::isWrite('post', '/api/operation/saveCustomer'));
        $this->assertFalse(FinaClient::isWrite('post', '/api/operation/getProductsArray'));
        $this->assertFalse(FinaClient::isWrite('get', '/api/operation/getDocProductOut/5'));
    }

    public function test_staff_zero_means_the_users_linked_staff_on_every_document(): void
    {
        $return = new CustomerReturnPayload(
            id: 0, date: CarbonImmutable::parse('2026-11-07'), numPrefix: '', num: 0, purpose: 'x', amount: 1.0, currency: 'GEL', rate: 1.0,
            store: 2, user: 1, staff: 0, project: 0, customer: 8, isVat: true, makeEntry: true, payType: 0, tType: 1, tPayer: 1, wCost: 0.0, foreign: false,
            products: [new ProductLine(id: 2, quantity: 1.0, price: 1.0, outId: 9)],
        );
        $moneyBack = new CustomerMoneyReturnPayload(
            id: 0, date: CarbonImmutable::parse('2026-11-07'), numPrefix: '', num: 0, purpose: 'x', amount: 1.0, currency: 'GEL', rate: 1.0,
            store: 2, user: 1, staff: 0, project: 0, customer: 8, payType: 1, payTypeId: 1, refId: 3,
        );

        PayloadValidator::validate($return);
        PayloadValidator::validate($moneyBack);
        $this->addToAssertionCount(2);

        foreach (['tType' => 0, 'tPayer' => 0] as $field => $value) {
            $bad = new CustomerReturnPayload(...array_merge([
                'id' => 0, 'date' => CarbonImmutable::parse('2026-11-07'), 'numPrefix' => '', 'num' => 0, 'purpose' => 'x', 'amount' => 1.0, 'currency' => 'GEL', 'rate' => 1.0,
                'store' => 2, 'user' => 1, 'staff' => 0, 'project' => 0, 'customer' => 8, 'isVat' => true, 'makeEntry' => true, 'payType' => 0, 'tType' => 1, 'tPayer' => 1, 'wCost' => 0.0, 'foreign' => false,
                'products' => [new ProductLine(id: 2, quantity: 1.0, price: 1.0, outId: 9)],
            ], [$field => $value]));
            try {
                PayloadValidator::validate($bad);
                $this->fail("return accepted {$field} = {$value}");
            } catch (FinaValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_waybill_fields_fina_refuses_are_caught_before_sending(): void
    {
        foreach ([['wType' => 0], ['tType' => 0], ['tType' => 5], ['tPayer' => 0], ['tPayer' => 3]] as $bad) {
            try {
                PayloadValidator::validate($this->productOut($bad));
                $this->fail('accepted '.json_encode($bad));
            } catch (FinaValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        PayloadValidator::validate($this->productOut(['wType' => 3, 'tType' => 7, 'tPayer' => 2]));
        $this->addToAssertionCount(1);
    }

    public function test_an_error_on_a_save_carries_the_whole_answer_including_the_id(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveDocProductOut' => Http::sequence()
                ->push(['id' => 71351, 'ex' => 'saved with a warning'])
                ->push(['id' => 0, 'ex' => 'terminal closed']),
        ]);

        foreach ([[71351, 'saved with a warning'], [0, 'terminal closed']] as [$id, $ex]) {
            try {
                $this->client()->documents()->saveProductOut($this->productOut());
                $this->fail('expected a remote exception');
            } catch (FinaRemoteException $e) {
                $this->assertSame($ex, $e->ex);
                $this->assertSame(['id' => $id, 'ex' => $ex], $e->response);
            }
        }
        $this->assertSame(2, $this->sent('/saveDocProductOut'), 'each save went out once');
    }
}
