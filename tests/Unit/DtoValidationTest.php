<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Exceptions\FinaValidationException;
use Fina\Sdk\Laravel\Operation\Dto\BonusOperationPayload;
use Fina\Sdk\Laravel\Operation\Dto\CafeOrderPayload;
use Fina\Sdk\Laravel\Operation\Dto\CafeOrderProductLine;
use Fina\Sdk\Laravel\Operation\Dto\ProductLine;
use Fina\Sdk\Laravel\Operation\Dto\ProductOutPayload;
use Fina\Sdk\Laravel\Support\PayloadValidator;
use Fina\Sdk\Laravel\Tests\TestCase;

final class DtoValidationTest extends TestCase
{
    // -----------------------------------------------------------------
    // BonusOperationPayload
    // -----------------------------------------------------------------

    public function test_valid_bonus_operation_passes(): void
    {
        $payload = new BonusOperationPayload(
            cardId: 1,
            refId: 100,
            coeff: 1,
            amount: 50.0,
        );

        // Should not throw
        PayloadValidator::validate($payload);
        $this->addToAssertionCount(1);
    }

    public function test_invalid_bonus_operation_throws(): void
    {
        $payload = new BonusOperationPayload(
            cardId: 0,   // min:1 violated
            refId: -1,   // min:0 violated
            coeff: 2,    // in:1,-1 violated
            amount: 0.0, // gt:0 violated
        );

        $this->expectException(FinaValidationException::class);
        PayloadValidator::validate($payload);
    }

    public function test_validation_exception_contains_errors(): void
    {
        $payload = new BonusOperationPayload(
            cardId: 0,
            refId: 0,
            coeff: 99,
            amount: -5.0,
        );

        try {
            PayloadValidator::validate($payload);
            $this->fail('Expected FinaValidationException');
        } catch (FinaValidationException $e) {
            $this->assertArrayHasKey('card_id', $e->errors);
            $this->assertArrayHasKey('coeff', $e->errors);
            $this->assertArrayHasKey('amount', $e->errors);
        }
    }

    // -----------------------------------------------------------------
    // CafeOrderPayload
    // -----------------------------------------------------------------

    public function test_valid_cafe_order_passes(): void
    {
        $payload = new CafeOrderPayload(
            id: 0,
            date: CarbonImmutable::now(),
            numPrefix: 'CO',
            num: 1,
            purpose: 'Test order',
            amount: 100.0,
            store: 1,
            user: 1,
            project: 0,
            products: [new CafeOrderProductLine(id: 1, quantity: 2.0, price: 50.0)],
        );

        PayloadValidator::validate($payload);
        $this->addToAssertionCount(1);
    }

    public function test_cafe_order_without_products_fails(): void
    {
        $payload = new CafeOrderPayload(
            id: 0,
            date: CarbonImmutable::now(),
            numPrefix: 'CO',
            num: 1,
            purpose: 'Test',
            amount: 100.0,
            store: 1,
            user: 1,
            project: 0,
            products: [], // min:1 violated
        );

        $this->expectException(FinaValidationException::class);
        PayloadValidator::validate($payload);
    }

    // -----------------------------------------------------------------
    // ProductOutPayload (complex)
    // -----------------------------------------------------------------

    public function test_valid_product_out_passes(): void
    {
        $payload = new ProductOutPayload(
            id: 0,
            date: CarbonImmutable::now(),
            numPrefix: 'PO',
            num: 1,
            purpose: 'Sale',
            amount: 500.0,
            currency: 'GEL',
            rate: 1.0,
            store: 1,
            user: 1,
            staff: 1,
            project: 0,
            customer: 10,
            isVat: false,
            makeEntry: true,
            payType: 0,
            wType: 2,
            tType: 1,
            tPayer: 1,
            wCost: 0.0,
            foreign: false,
            products: [new ProductLine(id: 1, subId: 0, quantity: 5.0, price: 100.0)],
        );

        PayloadValidator::validate($payload);
        $this->addToAssertionCount(1);
    }
}
