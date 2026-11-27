<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Exceptions\FinaValidationException;
use Fina\Sdk\Laravel\Operation\Dto\AddField;
use Fina\Sdk\Laravel\Operation\Dto\CafeOrderPayload;
use Fina\Sdk\Laravel\Operation\Dto\CafeOrderProductLine;
use Fina\Sdk\Laravel\Operation\Dto\ConsumedMaterialLine;
use Fina\Sdk\Laravel\Operation\Dto\ContragentPayload;
use Fina\Sdk\Laravel\Operation\Dto\CustomerOrderFulfillmentPayload;
use Fina\Sdk\Laravel\Operation\Dto\DiscountCardPayload;
use Fina\Sdk\Laravel\Operation\Dto\DiscountOperationPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyBonusPointPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerGroupLine;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerProgramLine;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyGroupPayload;
use Fina\Sdk\Laravel\Operation\Dto\Production\AdvProductionLine;
use Fina\Sdk\Laravel\Operation\Dto\Production\AdvProductionPayload;
use Fina\Sdk\Laravel\Operation\Dto\ProductLine;
use Fina\Sdk\Laravel\Operation\Dto\ProductMovePayload;
use Fina\Sdk\Laravel\Operation\Dto\ProductOutPayload;
use Fina\Sdk\Laravel\Operation\Dto\ProductPayload;
use Fina\Sdk\Laravel\Operation\Dto\ProductPriceLine;
use Fina\Sdk\Laravel\Operation\Dto\ProvidedServicePayload;
use Fina\Sdk\Laravel\Operation\Dto\ReceivedServicePayload;
use Fina\Sdk\Laravel\Operation\Dto\ServiceLine;
use Fina\Sdk\Laravel\Support\PayloadValidator;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Request side of FINA WEB API 10.0: new payloads, new optional request fields, and proof that
 * a payload built without the new fields sends exactly the bytes v0.2.5 sent.
 */
final class Pdf10PayloadsTest extends TestCase
{
    /**
     * toArray() of these payloads as v0.2.5 produced it (json_encode with
     * JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION), captured before 0.3.0 was written.
     */
    private const GOLDEN = [
        'product_out' => '{"id":0,"date":"2018-12-08T18:00:00","num_pfx":"","num":0,"purpose":"რეალიზაცია","amount":65.7,"currency":"GEL","rate":1.0,"store":1,"user":2,"staff":3,"project":2,"customer":8,"is_vat":true,"make_entry":true,"pay_type":1,"price_type":3,"w_type":2,"t_type":1,"t_payer":2,"w_cost":0.5,"foreign":false,"drv_name":"მძღოლის სახელი, გვარი","tr_start":"ტრანსპორტირების დაწყების ადგილი","tr_end":"ტრანსპორტირების დასრულების ადგილი","driver_id":"12345678910","car_num":"SAK005","tr_text":"მისაბმელი\/გადამზიდავი","sender":"გამგზავნი\/ჩამბარებელი","reciever":"მიმღები","comment":"კომენტარის ტექსტი","overlap_type":0,"overlap_amount":0.0,"add_fields":[{"field":"usr_column_510","value":"test string"}],"products":[{"id":2,"sub_id":0,"quantity":1.0,"price":35.7},{"id":3,"sub_id":0,"quantity":3.0,"price":10.0}],"services":[{"id":20,"quantity":1.0,"price":2.0}]}',
        'provided_service' => '{"id":0,"date":"2018-12-08T18:00:00","num_pfx":"","num":0,"purpose":"მომსახურების გაწევა","amount":2.0,"currency":"GEL","rate":1.0,"store":1,"user":2,"staff":3,"project":2,"customer":8,"is_vat":true,"make_entry":true,"pay_type":1,"overlap_type":0,"overlap_amount":0.0,"add_fields":[{"field":"usr_column_510","value":"test string"}],"services":[{"id":2,"quantity":1.0,"price":2.0}]}',
        'product_move' => '{"id":0,"date":"2019-02-14T11:00:00","num_pfx":"","num":0,"purpose":"შიდა გადაზიდვა - Test","store_from":1,"store_to":2,"user":2,"staff":3,"make_entry":true,"t_type":1,"t_payer":2,"w_cost":0.5,"foreign":false,"drv_name":"მძღოლის სახელი, გვარი","tr_start":"ტრანსპორტირების დაწყების ადგილი","tr_end":"ტრანსპორტირების დასრულების ადგილი","driver_id":"12345678910","car_num":"SAK005","tr_text":"მისაბმელი\/გადამზიდავი","add_fields":[{"field":"usr_column_518","value":"test string123"}],"products":[{"id":2,"sub_id":0,"quantity":1.0},{"id":3,"sub_id":0,"quantity":3.0}]}',
        'received_service' => '{"id":0,"date":"2019-05-16T11:36:11","num_pfx":"","num":0,"purpose":"მომსახურების მიღება","amount":1000.0,"currency":"GEL","rate":1.0,"user":1,"project":2,"vendor":3,"is_vat":true,"make_entry":true,"pay_type":1,"overlap_type":0,"overlap_amount":0.0,"add_fields":[],"services":[{"id":7,"quantity":1.0,"price":1000.0}]}',
        'cafe_order' => '{"id":0,"date":"2020-08-21T13:01:28","num_pfx":"","num":0,"purpose":"შეკვეთა","amount":5.0,"store":1,"user":1,"project":1,"customer_name":"","customer_tel":"","customer_address":"","products":[{"id":1,"quantity":1.0,"price":2.5}],"services":[{"id":4,"quantity":1.0,"price":2.5}]}',
    ];

    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    private static function readable(ValidatesPayload $payload): string
    {
        return (string) json_encode($payload->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /** @param array<string, mixed> $overrides */
    private static function productOut(array $overrides = []): ProductOutPayload
    {
        return new ProductOutPayload(...array_merge([
            'id' => 0, 'date' => CarbonImmutable::parse('2018-12-08 18:00:00'), 'numPrefix' => '', 'num' => 0, 'purpose' => 'რეალიზაცია', 'amount' => 65.7,
            'currency' => 'GEL', 'rate' => 1.0, 'store' => 1, 'user' => 2, 'staff' => 3, 'project' => 2, 'customer' => 8, 'isVat' => true, 'makeEntry' => true,
            'payType' => 1, 'wType' => 2, 'tType' => 1, 'tPayer' => 2, 'wCost' => 0.5, 'foreign' => false,
            'drvName' => 'მძღოლის სახელი, გვარი', 'trStart' => 'ტრანსპორტირების დაწყების ადგილი', 'trEnd' => 'ტრანსპორტირების დასრულების ადგილი',
            'driverId' => '12345678910', 'carNum' => 'SAK005', 'trText' => 'მისაბმელი/გადამზიდავი', 'sender' => 'გამგზავნი/ჩამბარებელი', 'reciever' => 'მიმღები',
            'comment' => 'კომენტარის ტექსტი', 'overlapType' => 0, 'overlapAmount' => 0.0,
            'addFields' => [new AddField('usr_column_510', 'test string')],
            'products' => [new ProductLine(id: 2, subId: 0, quantity: 1.0, price: 35.7), new ProductLine(id: 3, subId: 0, quantity: 3.0, price: 10.0)],
            'services' => [new ServiceLine(id: 20, quantity: 1.0, price: 2.0)],
        ], $overrides));
    }

    /** @param ServiceLine[] $services */
    private static function providedService(array $services = []): ProvidedServicePayload
    {
        return new ProvidedServicePayload(
            id: 0, date: CarbonImmutable::parse('2018-12-08 18:00:00'), numPrefix: '', num: 0, purpose: 'მომსახურების გაწევა', amount: 2.0,
            currency: 'GEL', rate: 1.0, store: 1, user: 2, staff: 3, project: 2, customer: 8, isVat: true, makeEntry: true, payType: 1,
            overlapType: 0, overlapAmount: 0.0, addFields: [new AddField('usr_column_510', 'test string')],
            services: $services ?: [new ServiceLine(id: 2, quantity: 1.0, price: 2.0)],
        );
    }

    /** @param ProductLine[] $products */
    private static function productMove(array $products = []): ProductMovePayload
    {
        return new ProductMovePayload(
            id: 0, date: CarbonImmutable::parse('2019-02-14 11:00:00'), numPrefix: '', num: 0, purpose: 'შიდა გადაზიდვა - Test',
            storeFrom: 1, storeTo: 2, user: 2, staff: 3, makeEntry: true, tType: 1, tPayer: 2, wCost: 0.5, foreign: false,
            drvName: 'მძღოლის სახელი, გვარი', trStart: 'ტრანსპორტირების დაწყების ადგილი', trEnd: 'ტრანსპორტირების დასრულების ადგილი',
            driverId: '12345678910', carNum: 'SAK005', trText: 'მისაბმელი/გადამზიდავი',
            addFields: [new AddField('usr_column_518', 'test string123')],
            products: $products ?: [new ProductLine(id: 2, subId: 0, quantity: 1.0), new ProductLine(id: 3, subId: 0, quantity: 3.0)],
        );
    }

    /** @param ServiceLine[] $services */
    private static function receivedService(array $services = []): ReceivedServicePayload
    {
        return new ReceivedServicePayload(
            id: 0, date: CarbonImmutable::parse('2019-05-16 11:36:11'), numPrefix: '', num: 0, purpose: 'მომსახურების მიღება', amount: 1000.0,
            currency: 'GEL', rate: 1.0, user: 1, project: 2, vendor: 3, isVat: true, makeEntry: true, payType: 1,
            services: $services ?: [new ServiceLine(id: 7, quantity: 1.0, price: 1000.0)],
        );
    }

    /** @param ServiceLine[] $services */
    private static function cafeOrder(array $services = []): CafeOrderPayload
    {
        return new CafeOrderPayload(
            id: 0, date: CarbonImmutable::parse('2020-08-21 13:01:28'), numPrefix: '', num: 0, purpose: 'შეკვეთა', amount: 5.0,
            store: 1, user: 1, project: 1, products: [new CafeOrderProductLine(id: 1, quantity: 1.0, price: 2.5)],
            services: $services ?: [new ServiceLine(id: 4, quantity: 1.0, price: 2.5)],
        );
    }

    private function assertRefused(ValidatesPayload $payload, string $field): void
    {
        try {
            PayloadValidator::validate($payload);
            $this->fail("accepted {$field}");
        } catch (FinaValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors, json_encode($e->errors) ?: '');
        }
    }

    private function assertAccepted(ValidatesPayload $payload): void
    {
        PayloadValidator::validate($payload);
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------
    // Existing payloads are byte-identical without the new fields
    // -------------------------------------------------------

    public function test_existing_payloads_produce_the_v025_json_without_the_new_fields(): void
    {
        $this->assertSame(self::GOLDEN['product_out'], self::readable(self::productOut()));
        $this->assertSame(self::GOLDEN['provided_service'], self::readable(self::providedService()));
        $this->assertSame(self::GOLDEN['product_move'], self::readable(self::productMove()));
        $this->assertSame(self::GOLDEN['received_service'], self::readable(self::receivedService()));
        $this->assertSame(self::GOLDEN['cafe_order'], self::readable(self::cafeOrder()));
    }

    public function test_a_sale_goes_over_the_wire_exactly_as_v025_sent_it(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveDocProductOut' => Http::response(['id' => 3, 'ex' => null]),
        ]);

        $this->client()->documents()->saveProductOut(self::productOut());

        // the wire uses json_encode's default flags, as Guzzle does
        $wire = json_encode(json_decode(self::GOLDEN['product_out'], true));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/saveDocProductOut') && $r->body() === $wire);
    }

    // -------------------------------------------------------
    // saveDocProductOut: gift_overlap
    // -------------------------------------------------------

    public function test_gift_overlap_is_sent_after_overlap_amount_on_a_free_sale(): void
    {
        $payload = self::productOut(['payType' => 5, 'giftOverlap' => 1]);
        $keys = array_keys($payload->toArray());

        $this->assertSame(1, $payload->toArray()['gift_overlap']);
        $this->assertSame('gift_overlap', $keys[array_search('overlap_amount', $keys, true) + 1], 'the document lists it right after overlap_amount');
        $this->assertSame(0, self::productOut(['payType' => 5, 'giftOverlap' => 0])->toArray()['gift_overlap'], '0 is sent, not dropped');
        $this->assertAccepted($payload);
        $this->assertAccepted(self::productOut(['payType' => 5, 'giftOverlap' => 0]));
    }

    public function test_gift_overlap_1_is_refused_outside_free_of_charge_and_anything_but_0_or_1_everywhere(): void
    {
        foreach ([0, 1, 2, 3, 4, 6] as $payType) {
            $this->assertRefused(self::productOut(['payType' => $payType, 'giftOverlap' => 1]), 'gift_overlap');
            $this->assertRefused(self::productOut(['payType' => $payType, 'giftOverlap' => 2]), 'gift_overlap');
        }
        $this->assertRefused(self::productOut(['payType' => 5, 'giftOverlap' => 2]), 'gift_overlap');
        $this->assertRefused(self::productOut(['payType' => 5, 'giftOverlap' => -1]), 'gift_overlap');
        $this->assertAccepted(self::productOut(['payType' => 1]));
    }

    public function test_gift_overlap_0_is_accepted_on_any_pay_type_as_the_documented_example_sends_it(): void
    {
        foreach ([0, 1, 2, 3, 4, 5, 6] as $payType) {
            $this->assertAccepted(self::productOut(['payType' => $payType, 'giftOverlap' => 0]));
        }

        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveDocProductOut' => Http::response(['id' => 3, 'ex' => null]),
        ]);

        // the document's saveDocProductOut example: "pay_type": 1 with "gift_overlap": 0
        $this->assertSame(3, $this->client()->documents()->saveProductOut(self::productOut(['giftOverlap' => 0]))->id);
        Http::assertSent(function (Request $r) {
            $body = (array) json_decode($r->body(), true);

            return str_ends_with($r->url(), '/saveDocProductOut') && $body['pay_type'] === 1 && $body['gift_overlap'] === 0;
        });
        $this->assertArrayNotHasKey('gift_overlap', self::productOut()->toArray(), 'null is still not sent');
    }

    // -------------------------------------------------------
    // saveDocProductMove: products[].price
    // -------------------------------------------------------

    public function test_move_lines_send_a_price_only_when_given(): void
    {
        $priced = self::productMove([new ProductLine(id: 2, subId: 0, quantity: 1.0, price: 2.0), new ProductLine(id: 3, subId: 0, quantity: 3.0, price: 2.0)]);

        // the document's example lines
        $this->assertSame([['id' => 2, 'sub_id' => 0, 'quantity' => 1.0, 'price' => 2.0], ['id' => 3, 'sub_id' => 0, 'quantity' => 3.0, 'price' => 2.0]], $priced->toArray()['products']);
        $this->assertArrayNotHasKey('price', self::productMove()->toArray()['products'][0]);
        $this->assertAccepted($priced);
        $this->assertRefused(self::productMove([new ProductLine(id: 2, quantity: 1.0, price: -1.0)]), 'products.0.price');
    }

    // -------------------------------------------------------
    // saveDocProvidedService: services[].consumed_materials
    // -------------------------------------------------------

    public function test_provided_service_sends_consumed_materials_as_documented(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveDocProvidedService' => Http::response(['id' => 3, 'ex' => null]),
        ]);

        $payload = self::providedService([new ServiceLine(id: 2, quantity: 1.0, price: 2.0, consumedMaterials: [new ConsumedMaterialLine(id: 29, quantity: 3.0), new ConsumedMaterialLine(id: 28, quantity: 2.0)])]);
        $this->assertSame(3, $this->client()->documents()->saveProvidedService($payload)->id);

        $documented = json_decode('[{"id": 2, "quantity": 1.0, "price": 2.0, "consumed_materials": [{"id": 29, "quantity": 3}, {"id": 28, "quantity": 2}]}]', true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/saveDocProvidedService')
            && json_encode(json_decode($r->body(), true)['services']) === json_encode($documented));
        $this->assertArrayNotHasKey('consumed_materials', (new ServiceLine(id: 2, quantity: 1.0, price: 2.0))->toArray());
    }

    public function test_consumed_materials_are_validated_and_refused_where_undocumented(): void
    {
        $line = static fn (int $id, float $qty) => new ServiceLine(id: 2, quantity: 1.0, price: 2.0, consumedMaterials: [new ConsumedMaterialLine($id, $qty)]);

        $this->assertAccepted(self::providedService([$line(29, 3.0)]));
        $this->assertAccepted(self::providedService([new ServiceLine(id: 2, quantity: 1.0, price: 2.0, consumedMaterials: [])]));
        $this->assertRefused(self::providedService([$line(0, 3.0)]), 'services.0.consumed_materials.0.id');
        $this->assertRefused(self::providedService([$line(29, 0.0)]), 'services.0.consumed_materials.0.quantity');

        // documented for saveDocProvidedService only
        $this->assertRefused(self::productOut(['services' => [$line(29, 3.0)]]), 'services.0.consumed_materials');
        $this->assertRefused(self::receivedService([$line(29, 3.0)]), 'services.0.consumed_materials');
        $this->assertRefused(self::cafeOrder([$line(29, 3.0)]), 'services.0.consumed_materials');
    }

    public function test_an_empty_consumed_materials_list_is_refused_where_undocumented_and_null_is_not_sent(): void
    {
        $empty = new ServiceLine(id: 2, quantity: 1.0, price: 2.0, consumedMaterials: []);
        $plain = new ServiceLine(id: 2, quantity: 1.0, price: 2.0);

        // [] still goes out as "consumed_materials": [], so it is refused like a filled list
        $this->assertSame([], $empty->toArray()['consumed_materials']);
        $this->assertRefused(self::productOut(['services' => [$empty]]), 'services.0.consumed_materials');
        $this->assertRefused(self::receivedService([$empty]), 'services.0.consumed_materials');
        $this->assertRefused(self::cafeOrder([$empty]), 'services.0.consumed_materials');
        $this->assertRefused(self::cafeOrder([$plain, $empty]), 'services.1.consumed_materials');

        // null leaves the key out of the line, so nothing is refused
        $this->assertArrayNotHasKey('consumed_materials', $plain->toArray());
        $this->assertAccepted(self::productOut(['services' => [$plain]]));
        $this->assertAccepted(self::receivedService([$plain]));
        $this->assertAccepted(self::cafeOrder([$plain]));
        $this->assertAccepted(self::cafeOrder([$plain, $plain]));
    }

    // -------------------------------------------------------
    // saveDocDiscountCard: person_*
    // -------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private static function discountCard(array $overrides = []): DiscountCardPayload
    {
        return new DiscountCardPayload(...array_merge([
            'id' => 0, 'date' => CarbonImmutable::parse('2019-11-08 18:00:00'), 'numPrefix' => '', 'num' => 0, 'purpose' => 'ფასდაკლების ბარათის გაცემა',
            'customer' => 31, 'store' => 1, 'user' => 1, 'cardCode' => '231', 'discountId' => 1, 'status' => true,
        ], $overrides));
    }

    public function test_discount_card_without_holder_is_the_80_body(): void
    {
        // the array PdfOperationEndpointsTest posts as the 8.0 example
        $this->assertSame(
            ['id' => 0, 'date' => '2019-11-08T18:00:00', 'num_pfx' => '', 'num' => 0, 'purpose' => 'ფასდაკლების ბარათის გაცემა', 'customer' => 31, 'store' => 1, 'user' => 1, 'card_code' => '231', 'discount_id' => 1, 'status' => true],
            self::discountCard()->toArray()
        );
    }

    public function test_discount_card_with_holder_is_the_100_example(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveDocDiscountCard' => Http::response(['id' => 2, 'ex' => null]),
        ]);

        $payload = self::discountCard(['personCode' => '0100010101', 'personName' => 'სახელი, გვარი', 'personAddress' => 'მისამართი', 'personTel' => '+995597222222']);
        $this->assertSame(2, $this->client()->documents()->saveDiscountCard($payload)->id);

        $documented = json_decode('{"id": 0, "date": "2019-11-08T18:00:00", "num_pfx": "", "num": 0, "purpose": "ფასდაკლების ბარათის გაცემა", "customer": 31, "store": 1, "user": 1, "card_code": "231", "person_code": "0100010101", "person_name": "სახელი, გვარი", "person_address": "მისამართი", "person_tel": "+995597222222", "discount_id": 1, "status": true}', true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/saveDocDiscountCard') && json_decode($r->body(), true) === $documented);
    }

    public function test_discount_card_validation(): void
    {
        $this->assertAccepted(self::discountCard());
        $this->assertAccepted(self::discountCard(['personCode' => '', 'personName' => 'x']));
        $this->assertRefused(self::discountCard(['cardCode' => '']), 'card_code');
        $this->assertRefused(self::discountCard(['discountId' => 0]), 'discount_id');
        $this->assertRefused(self::discountCard(['customer' => 0]), 'customer');
    }

    // -------------------------------------------------------
    // saveCustomer / saveVendor: marketing_promotions
    // -------------------------------------------------------

    public function test_contragent_payload_is_the_documented_customer_body(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveCustomer' => Http::response(['id' => 15, 'ex' => null]),
            '*/api/operation/saveVendor' => Http::response(['id' => 16, 'ex' => null]),
        ]);

        $customer = new ContragentPayload(
            id: 0, code: '12345678910', name: 'name1', groupId: 5, address: 'Address1', phone: '+99555000000', email: 'test@test.com',
            vatType: 1, isResident: false, isCompany: true, consPeriod: 30, birthDate: CarbonImmutable::parse('2010-11-08 18:00:00'),
            marketingPromotions: false, addFields: [new AddField('usr_column_515', '20')],
        );

        $this->assertSame(15, $this->client()->customers()->save($customer)->id);
        // the document's example; its curly quotes around birth_date are straight here
        $documented = json_decode('{"id": 0, "code": "12345678910", "name": "name1", "group_id": 5, "address": "Address1", "phone": "+99555000000", "email": "test@test.com", "vat_type": 1, "is_resident": false, "is_company": true, "cons_period": 30, "birth_date": "2010-11-08T18:00:00", "marketing_promotions": false, "add_fields": [{"field": "usr_column_515", "value": "20"}]}', true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/saveCustomer') && json_decode($r->body(), true) === $documented);

        $this->assertSame(16, $this->client()->vendors()->save(new ContragentPayload(id: 0, code: '21345678910', name: 'name2', groupId: 3, marketingPromotions: true))->id);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/saveVendor')
            && json_decode($r->body(), true) === ['id' => 0, 'code' => '21345678910', 'name' => 'name2', 'group_id' => 3, 'marketing_promotions' => true]);
    }

    public function test_contragent_payload_leaves_unset_fields_out(): void
    {
        $this->assertSame(['id' => 0, 'code' => '01', 'name' => 'n'], (new ContragentPayload(id: 0, code: '01', name: 'n'))->toArray());
    }

    public function test_contragent_payload_validation(): void
    {
        $this->assertAccepted(new ContragentPayload(id: 0, code: '', name: 'n'));
        $this->assertRefused(new ContragentPayload(id: 0, code: '01', name: ''), 'name');
        $this->assertRefused(new ContragentPayload(id: 0, code: str_repeat('1', 51), name: 'n'), 'code');
        $this->assertRefused(new ContragentPayload(id: 0, code: '01', name: 'n', vatType: 4), 'vat_type');
        $this->assertRefused(new ContragentPayload(id: -1, code: '01', name: 'n'), 'id');
        $this->assertRefused(new ContragentPayload(id: 0, code: '01', name: 'n', groupId: 0), 'group_id');
    }

    // -------------------------------------------------------
    // saveProduct: prices[].currency / discount_*
    // -------------------------------------------------------

    public function test_product_payload_is_the_documented_body(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/saveProduct' => Http::response(['id' => 1, 'ex' => null]),
        ]);

        $product = new ProductPayload(
            id: 0, code: '123456-1', name: 'საქონელი X', nameEng: '', nameRus: '', groupId: 11, comment: '', vat: 1, plu: 0, unit: 1, weight: 0.0,
            prices: [new ProductPriceLine(id: 3, value: 10.0, currency: 'GEL', discountValue: 8.0, discountEnd: CarbonImmutable::parse('2026-09-30 11:15:14'))],
            barcodes: ['QK124', 'QK125'], images: ['image base64 string'],
            addFields: [new AddField('usr_column_501', 'თეთრი'), new AddField('usr_column_502', 'XXL')],
        );

        $this->assertSame(1, $this->client()->products()->save($product)->id);
        // the document's example, except "discount_start": null, which is left out (null = not sent)
        $documented = json_decode('{"id": 0, "code": "123456-1", "name": "საქონელი X", "name_eng": "", "name_rus": "", "group_id": 11, "comment": "", "vat": 1, "plu": 0, "unit": 1, "weight": 0, "prices": [{"id": 3, "value": 10, "currency": "GEL", "discount_value": 8, "discount_end": "2026-09-30T11:15:14"}], "barcodes": ["QK124", "QK125"], "images": ["image base64 string"], "add_fields": [{"field": "usr_column_501", "value": "თეთრი"}, {"field": "usr_column_502", "value": "XXL"}]}', true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/saveProduct') && json_decode($r->body(), true) === $documented);
    }

    public function test_product_payload_never_sends_barcodes_or_images_it_was_not_given(): void
    {
        $this->assertSame(['id' => 7, 'code' => 'A1', 'name' => 'n'], (new ProductPayload(id: 7, code: 'A1', name: 'n'))->toArray());
        $this->assertSame(['id' => 3, 'value' => 10.0], (new ProductPriceLine(id: 3, value: 10.0))->toArray(), 'an 8.0 price line');
        $this->assertSame(['id' => 7, 'code' => 'A1', 'name' => 'n', 'barcodes' => []], (new ProductPayload(id: 7, code: 'A1', name: 'n', barcodes: []))->toArray(), '[] is sent: it clears');
    }

    public function test_product_payload_validation(): void
    {
        $price = static fn (array $o = []) => new ProductPriceLine(...array_merge(['id' => 3, 'value' => 10.0], $o));
        $product = static fn (array $o = []) => new ProductPayload(...array_merge(['id' => 0, 'code' => 'A1', 'name' => 'n'], $o));

        $this->assertAccepted($product(['prices' => [$price(['currency' => 'USD', 'discountValue' => 8.0, 'discountStart' => CarbonImmutable::parse('2026-09-01'), 'discountEnd' => CarbonImmutable::parse('2026-09-30')])]]));
        $this->assertRefused($product(['prices' => [$price(['id' => 0])]]), 'prices.0.id');
        $this->assertRefused($product(['prices' => [$price(['value' => -1.0])]]), 'prices.0.value');
        $this->assertRefused($product(['prices' => [$price(['discountValue' => -0.5])]]), 'prices.0.discount_value');
        $this->assertRefused($product(['prices' => [$price(['currency' => 'GEORGIAN-LARI'])]]), 'prices.0.currency');
        $this->assertRefused($product(['vat' => 4]), 'vat');
        $this->assertRefused($product(['name' => '']), 'name');
        $this->assertRefused($product(['comment' => str_repeat('x', 501)]), 'comment');
    }

    // -------------------------------------------------------
    // Validation of the new 10.0 payloads
    // -------------------------------------------------------

    /** @return iterable<string, array{ValidatesPayload, string}> */
    public static function refusedNewPayloads(): iterable
    {
        $date = CarbonImmutable::parse('2026-07-10 15:45:31');
        $adv = static fn (array $o) => new AdvProductionPayload(...array_merge([
            'id' => 0, 'date' => $date, 'numPrefix' => '', 'num' => 0, 'purpose' => 'x', 'store' => 1, 'portion' => 1.0, 'user' => 1, 'makeEntry' => true,
            'materials' => [new AdvProductionLine(4, 3.0)], 'products' => [new AdvProductionLine(106, 1.0, 100.0)],
        ], $o));

        yield 'fulfillment of order 0' => [new CustomerOrderFulfillmentPayload(0), 'id'];
        yield 'discount operation without a card' => [new DiscountOperationPayload(0, 16975), 'card_id'];
        yield 'discount operation without an operation' => [new DiscountOperationPayload(2013, 0), 'ref_id'];
        yield 'bonus point on card 0' => [new LoyaltyBonusPointPayload(0, $date, 9.0, -1), 'id'];
        yield 'bonus point coeff 2' => [new LoyaltyBonusPointPayload(45, $date, 9.0, 2), 'coeff'];
        yield 'bonus point of 0' => [new LoyaltyBonusPointPayload(45, $date, 0.0, 1), 'amount'];
        yield 'loyalty group without a name' => [new LoyaltyGroupPayload(0, ''), 'name'];
        yield 'loyalty card without a name' => [new LoyaltyCustomerPayload(0, ''), 'name'];
        yield 'loyalty card in program 0' => [new LoyaltyCustomerPayload(0, 'n', loyaltyPrograms: [new LoyaltyCustomerProgramLine(0, 0, true)]), 'loyalty_programs.0.program_id'];
        yield 'loyalty card in group 0' => [new LoyaltyCustomerPayload(0, 'n', loyaltyGroups: [new LoyaltyCustomerGroupLine(0, 0)]), 'loyalty_groups.0.group_id'];
        yield 'adv production without materials' => [$adv(['materials' => []]), 'materials'];
        yield 'adv production without products' => [$adv(['products' => []]), 'products'];
        yield 'adv production product without cost percent' => [$adv(['products' => [new AdvProductionLine(106, 1.0)]]), 'products.0.cost_percent'];
        yield 'adv production cost percent over 100' => [$adv(['products' => [new AdvProductionLine(106, 1.0, 100.5)]]), 'products.0.cost_percent'];
        yield 'adv production material with a cost percent' => [$adv(['materials' => [new AdvProductionLine(4, 3.0, 10.0)]]), 'materials.0.cost_percent'];
        yield 'adv production portion 0' => [$adv(['portion' => 0.0]), 'portion'];
        yield 'the document\'s all-zero template' => [$adv(['materials' => [new AdvProductionLine(0, 0.0)], 'products' => [new AdvProductionLine(0, 0.0, 0.0)]]), 'materials.0.id'];
    }

    #[DataProvider('refusedNewPayloads')]
    public function test_new_payload_is_refused_before_sending(ValidatesPayload $payload, string $field): void
    {
        $this->assertRefused($payload, $field);
    }

    public function test_new_payloads_accept_the_documented_values(): void
    {
        $date = CarbonImmutable::parse('2026-07-10 15:45:31');

        $this->assertAccepted(new CustomerOrderFulfillmentPayload(4164));
        $this->assertAccepted(new DiscountOperationPayload(2013, 16975));
        $this->assertAccepted(new LoyaltyBonusPointPayload(45, $date, 9.0, -1, 'ჩამოჭრა'));
        $this->assertAccepted(new LoyaltyBonusPointPayload(45, $date, 0.5, 1));
        $this->assertAccepted(new LoyaltyGroupPayload(0, 'ჯგუფი #1', ''));
        $this->assertAccepted(new LoyaltyCustomerPayload(0, 'ბარათის მფლობელი', loyaltyPrograms: [new LoyaltyCustomerProgramLine(0, 63, true)], loyaltyGroups: [new LoyaltyCustomerGroupLine(0, 16)]));
        $this->assertAccepted(new LoyaltyCustomerPayload(44, 'n', loyaltyPrograms: [], loyaltyGroups: []));
        $this->assertAccepted(new AdvProductionPayload(0, $date, '', 3, 'რთული წარმოება/დაშლა', 1, 2.0, 1, true, [new AdvProductionLine(4, 3.0)], [new AdvProductionLine(106, 1.0, 40.0), new AdvProductionLine(105, 2.0, 60.0)]));
    }

    public function test_an_invalid_new_payload_never_reaches_fina(): void
    {
        Http::fake();

        foreach ([
            fn () => $this->client()->documents()->fulfillCustomerOrder(0),
            fn () => $this->client()->loyalty()->saveDiscountOperation(new DiscountOperationPayload(0, 1)),
            fn () => $this->client()->loyalty()->saveBonusPoint(new LoyaltyBonusPointPayload(45, CarbonImmutable::now(), 1.0, 5)),
            fn () => $this->client()->loyalty()->saveGroup(new LoyaltyGroupPayload(0, '')),
            fn () => $this->client()->loyalty()->saveCustomer(new LoyaltyCustomerPayload(0, '')),
            fn () => $this->client()->documents()->saveProductOut(self::productOut(['giftOverlap' => 1])),
            fn () => $this->client()->documents()->saveProductOut(self::productOut(['services' => [new ServiceLine(20, 1.0, 2.0, consumedMaterials: [])]])),
            fn () => $this->client()->documents()->saveReceivedService(self::receivedService([new ServiceLine(7, 1.0, 1000.0, consumedMaterials: [])])),
            fn () => $this->client()->documents()->saveCafeOrder(self::cafeOrder([new ServiceLine(4, 1.0, 2.5, consumedMaterials: [])])),
            fn () => $this->client()->documents()->saveDiscountCard(self::discountCard(['cardCode' => ''])),
            fn () => $this->client()->customers()->save(new ContragentPayload(0, '01', '')),
            fn () => $this->client()->products()->save(new ProductPayload(0, 'A1', '', vat: 9)),
        ] as $i => $call) {
            try {
                $call();
                $this->fail("call {$i} was not refused");
            } catch (FinaValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        Http::assertNothingSent();
    }
}
