<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Exceptions\FinaRemoteException;
use Fina\Sdk\Laravel\Operation\Dto\AdditionalFieldDescriptorDto;
use Fina\Sdk\Laravel\Operation\Dto\ContragentDto;
use Fina\Sdk\Laravel\Operation\Dto\CustomerOrderFulfillmentPayload;
use Fina\Sdk\Laravel\Operation\Dto\DiscountOperationPayload;
use Fina\Sdk\Laravel\Operation\Dto\DiscountOperationResponse;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyBalanceDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyBonusHistoryDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyBonusPointPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyBonusProductDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerGroupDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerGroupLine;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerProgramDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerProgramLine;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyGroupDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyGroupPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyProgramDto;
use Fina\Sdk\Laravel\Operation\Dto\Production\AdvProductionDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Production\AdvProductionLine;
use Fina\Sdk\Laravel\Operation\Dto\Production\AdvProductionLineDto;
use Fina\Sdk\Laravel\Operation\Dto\Production\AdvProductionPayload;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Operation\Dto\ServiceConsumptionDto;
use Fina\Sdk\Laravel\Operation\Dto\ServiceConsumptionLineDto;
use Fina\Sdk\Laravel\Operation\Dto\StoreDto;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionProperty;

/**
 * The methods FINA WEB API 10.0 adds (10.0.20260625 - 10.0.20261027): verb, path, body and
 * mapping, fed with the examples printed in the document.
 */
final class Pdf10EndpointsTest extends TestCase
{
    private const BASE = 'http://fina-test.local:5007/api/operation/';

    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    /** @param array<string, mixed>|string $body array, or JSON as the document prints it */
    private function fake(string $path, array|string $body): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/'.$path => Http::response(is_string($body) ? self::documented($body) : $body),
        ]);
    }

    /** @return array<string, mixed> */
    private static function documented(string $json): array
    {
        return (array) json_decode($json, true, 512, JSON_THROW_ON_ERROR);
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
    // Reference, services, documents
    // -------------------------------------------------------

    public function test_service_consumption_materials(): void
    {
        $this->fake('getServiceConsumptionMaterials', <<<'JSON'
            {
                "services": [{
                   "id": 51,
                   "consumption": [{
                       "product_id": 29,
                       "quantity": 2
                     },{
                       "product_id": 28,
                       "quantity": 1
                     },{
                       "product_id": 27,
                       "quantity": 1
                     }]},
                 {
                   "id": 139,
                   "consumption": [{
                       "product_id": 13,
                       "quantity": 2
                     },{
                       "product_id": 12,
                       "quantity": 1
                     }]}],
                "ex": null
            }
            JSON);

        $services = $this->client()->services()->consumptionMaterials();

        $this->assertGet('getServiceConsumptionMaterials');
        $this->assertCount(2, $services);
        $this->assertSame(51, $services[0]->id);
        $this->assertCount(3, $services[0]->consumption);
        $this->assertSame(29, $services[0]->consumption[0]->productId);
        $this->assertSame(2.0, $services[0]->consumption[0]->quantity);
        $this->assertSame(27, $services[0]->consumption[2]->productId);
        $this->assertSame(139, $services[1]->id);
        $this->assertSame([13, 12], array_map(static fn ($c) => $c->productId, $services[1]->consumption));
        $this->assertSame(['product_id' => 12, 'quantity' => 1], $services[1]->consumption[1]->raw);
    }

    public function test_store_additional_fields(): void
    {
        $this->fake('getStoreAdditionalFields', <<<'JSON'
            {
                "fields": [{
                    "name": "usr_column_520",
                    "header": "ფართობი"
                }],
                "ex": null
            }
            JSON);

        $fields = $this->client()->reference()->storeAdditionalFields();

        $this->assertGet('getStoreAdditionalFields');
        $this->assertCount(1, $fields);
        $this->assertInstanceOf(AdditionalFieldDescriptorDto::class, $fields[0]);
        $this->assertSame('usr_column_520', $fields[0]->name);
        $this->assertSame('ფართობი', $fields[0]->header);
    }

    public function test_get_doc_adv_production_raw_and_typed(): void
    {
        $this->fake('getDocAdvProduction/36095', <<<'JSON'
            {
                "adv_production": {
                 "id": 36095,
                 "date": "2026-06-25T14:17:45",
                 "num_pfx": "",
                 "num": 3,
                 "purpose": "რთული წარმოება/დაშლა",
                 "amount": 1961.17,
                 "portion": 2,
                 "store": 1,
                 "user": 1,
                 "make_entry": true,
                 "materials": [{
                     "id": 4,
                     "quantity": 3
                   }],
                 "products": [{
                     "id": 106,
                     "quantity": 1,
                     "cost_percent": 40
                   }, {
                     "id": 105,
                     "quantity": 2,
                     "cost_percent": 60
                   }]},
                "ex": null
            }
            JSON);

        $raw = $this->client()->documents()->getAdvProduction(36095);
        $this->assertSame(1961.17, $raw['adv_production']['amount']);

        $doc = $this->client()->documents()->getAdvProductionTyped(36095);
        $this->assertGet('getDocAdvProduction/36095');

        $this->assertSame(36095, $doc->id);
        $this->assertSame('2026-06-25T14:17:45', $doc->date);
        $this->assertSame('', $doc->numPrefix);
        $this->assertSame(3, $doc->num);
        $this->assertSame('რთული წარმოება/დაშლა', $doc->purpose);
        $this->assertSame(1961.17, $doc->amount);
        $this->assertSame(2.0, $doc->portion);
        $this->assertSame(1, $doc->store);
        $this->assertSame(1, $doc->user);
        $this->assertTrue($doc->makeEntry);
        $this->assertCount(1, $doc->materials);
        $this->assertSame(4, $doc->materials[0]->id);
        $this->assertSame(3.0, $doc->materials[0]->quantity);
        $this->assertNull($doc->materials[0]->costPercent);
        $this->assertSame([106, 105], array_map(static fn ($p) => $p->id, $doc->products));
        $this->assertSame([40.0, 60.0], array_map(static fn ($p) => $p->costPercent, $doc->products));
        $this->assertSame(2.0, $doc->products[1]->quantity);
        $this->assertSame(36095, $doc->raw['id']);
    }

    public function test_save_doc_adv_production_sends_the_documented_fields_in_order(): void
    {
        $this->fake('saveDocAdvProduction', ['id' => 3, 'ex' => null]);

        $saved = $this->client()->documents()->saveAdvProduction(new AdvProductionPayload(
            id: 0, date: CarbonImmutable::parse('2026-06-25 14:17:45'), numPrefix: '', num: 3, purpose: 'რთული წარმოება/დაშლა',
            store: 1, portion: 2.0, user: 1, makeEntry: true,
            materials: [new AdvProductionLine(id: 4, quantity: 3.0)],
            products: [new AdvProductionLine(id: 106, quantity: 1.0, costPercent: 40.0), new AdvProductionLine(id: 105, quantity: 2.0, costPercent: 60.0)],
        ));

        $this->assertInstanceOf(SaveDocResponse::class, $saved);
        $this->assertSame(3, $saved->id);
        $this->assertPost('saveDocAdvProduction', [
            'id' => 0, 'date' => '2026-06-25T14:17:45', 'num_pfx' => '', 'num' => 3, 'purpose' => 'რთული წარმოება/დაშლა',
            'store' => 1, 'portion' => 2, 'user' => 1, 'make_entry' => true,
            'materials' => [['id' => 4, 'quantity' => 3]],
            'products' => [['id' => 106, 'quantity' => 1, 'cost_percent' => 40], ['id' => 105, 'quantity' => 2, 'cost_percent' => 60]],
        ]);

        // key order of the document's request example (v10 p. 223)
        $documented = self::documented('{"id": 0, "date": "2026-06-25T14:17:45", "num_pfx": "", "num": 3, "purpose": "x", "store": 1, "portion": 2, "user": 1, "make_entry": true, "materials": [{"id": 0, "quantity": 0}], "products": [{"id": 0, "quantity": 0, "cost_percent": 0}]}');
        Http::assertSent(fn (Request $r) => array_keys((array) json_decode($r->body(), true)) === array_keys($documented));
    }

    public function test_fulfill_customer_order_by_id_or_payload(): void
    {
        $this->fake('fulfillmentCustomerOrder', <<<'JSON'
            {
                "id": 3647,
                "ex": null
            }
            JSON);

        $sale = $this->client()->documents()->fulfillCustomerOrder(4164);

        $this->assertSame(3647, $sale->id, 'the answer is the id of the sale made from the order');
        // the document prints {"id": 4164,} (trailing comma); the body is that object
        $this->assertPost('fulfillmentCustomerOrder', ['id' => 4164]);

        $this->assertSame(3647, $this->client()->documents()->fulfillCustomerOrder(new CustomerOrderFulfillmentPayload(4165))->id);
        $this->assertPost('fulfillmentCustomerOrder', ['id' => 4165]);
    }

    // -------------------------------------------------------
    // Loyalty (Cloud) reads
    // -------------------------------------------------------

    public function test_loyalty_customers(): void
    {
        $this->fake('getLoyaltyCustomers', <<<'JSON'
            {
                "loyalty_customers": [
                  {
                    "id": 1,
                    "code": "1",
                    "name": "ელენე1234",
                    "qr_code": "1",
                    "tel": "99551121061",
                    "birth_day": "2025-05-28T16:55:01.507",
                    "email": "sdcsdcsdcsc@gmail.com",
                    "sex": false,
                    "comment": "",
                    "allow_promotions": false,
                    "cerate_date": "2025-06-03T16:55:01.51"
                  },
                  {
                    "id": 2,
                    "code": "2",
                    "name": "ლევანი",
                    "qr_code": "2",
                    "tel": "599705803",
                    "birth_day": "2025-06-05T00:00:00",
                    "email": "beqauri",
                    "sex": true,
                    "comment": "test",
                    "allow_promotions": false,
                    "cerate_date": "2025-06-05T12:59:56.837"
                  }
                ],
                "ex": null
            }
            JSON);

        $cards = $this->client()->loyalty()->customers();

        $this->assertGet('getLoyaltyCustomers');
        $this->assertCount(2, $cards);
        $first = $cards[0];
        $this->assertSame(1, $first->id);
        $this->assertSame('1', $first->code);
        $this->assertSame('ელენე1234', $first->name);
        $this->assertSame('1', $first->qrCode);
        $this->assertSame('99551121061', $first->tel);
        $this->assertSame('2025-05-28T16:55:01.507', $first->birthDay);
        $this->assertSame('sdcsdcsdcsc@gmail.com', $first->email);
        $this->assertFalse($first->sex, 'false = female');
        $this->assertSame('', $first->comment);
        $this->assertFalse($first->allowPromotions);
        $this->assertSame('2025-06-03T16:55:01.51', $first->cerateDate, 'read under the key the document spells');
        $this->assertNull($first->loyaltyPrograms, 'the list sends no programs: unknown, not none');
        $this->assertNull($first->loyaltyGroups, 'the list sends no groups: unknown, not none');

        $this->assertTrue($cards[1]->sex);
        $this->assertSame('beqauri', $cards[1]->email);
        $this->assertSame('2025-06-05T12:59:56.837', $cards[1]->raw['cerate_date']);
    }

    public function test_loyalty_customer_with_programs_and_groups(): void
    {
        $this->fake('getLoyaltyCustomer/44', <<<'JSON'
            {
                "loyalty_customer": {
                  "id": 44,
                  "code": "01001042222",
                  "name": "როლანდ რეიგანი",
                  "qr_code": "777888",
                  "tel": "+995598583444",
                  "birth_day": "1991-10-06T10:17:40",
                  "email": "",
                  "sex": true,
                  "comment": "კომენატიიიიიიი",
                  "allow_promotions": true,
                  "cerate_date": "2026-07-08T10:20:01.173",
                  "loyalty_programs": [
                    {
                      "id": 391,
                      "program_id": 63,
                      "start_date": "2026-07-08T11:12:45.637",
                      "end_date": null,
                      "status": true
                    }],
                  "loyalty_groups": [
                    {
                      "id": 595,
                      "group_id": 35
                    }]
                },
                "ex": null
            }
            JSON);

        $card = $this->client()->loyalty()->customer(44);

        $this->assertGet('getLoyaltyCustomer/44');
        $this->assertSame(44, $card->id);
        $this->assertSame('01001042222', $card->code);
        $this->assertSame('777888', $card->qrCode);
        $this->assertSame('+995598583444', $card->tel);
        $this->assertSame('1991-10-06T10:17:40', $card->birthDay);
        $this->assertTrue($card->sex);
        $this->assertTrue($card->allowPromotions);
        $this->assertSame('2026-07-08T10:20:01.173', $card->cerateDate);

        $this->assertCount(1, $card->loyaltyPrograms);
        $program = $card->loyaltyPrograms[0];
        $this->assertSame(391, $program->id);
        $this->assertSame(63, $program->programId);
        $this->assertSame('2026-07-08T11:12:45.637', $program->startDate);
        $this->assertNull($program->endDate, 'open-ended enrolment');
        $this->assertTrue($program->status);

        $this->assertCount(1, $card->loyaltyGroups);
        $this->assertSame(595, $card->loyaltyGroups[0]->id);
        $this->assertSame(35, $card->loyaltyGroups[0]->groupId);
    }

    public function test_loyalty_customer_programs(): void
    {
        $this->fake('getLoyaltyCustomerPrograms', <<<'JSON'
            {
                "programs": [
                  {
                    "id": 59,
                    "name": "აქცია",
                    "start_date": "2026-05-05T00:00:00",
                    "end_date": "2026-08-01T23:59:59",
                    "comment": "",
                    "status": true,
                    "program_type": 1,
                    "usage_type": 1
                  },
                  {
                    "id": 61,
                    "name": "ხვ",
                    "start_date": "2026-06-17T00:00:00",
                    "end_date": "2026-08-01T23:59:59",
                    "comment": "",
                    "status": true,
                    "program_type": 2,
                    "usage_type": 0
                  }
                ],
                "ex": null
            }
            JSON);

        $programs = $this->client()->loyalty()->customerPrograms();

        $this->assertGet('getLoyaltyCustomerPrograms');
        $this->assertCount(2, $programs);
        $this->assertSame(59, $programs[0]->id);
        $this->assertSame('აქცია', $programs[0]->name);
        $this->assertSame('2026-05-05T00:00:00', $programs[0]->startDate);
        $this->assertSame('2026-08-01T23:59:59', $programs[0]->endDate);
        $this->assertTrue($programs[0]->status);
        $this->assertSame(1, $programs[0]->programType);
        $this->assertSame(1, $programs[0]->usageType);
        $this->assertSame(2, $programs[1]->programType);
        $this->assertSame(0, $programs[1]->usageType);
    }

    public function test_loyalty_customer_groups(): void
    {
        $this->fake('getLoyaltyCustomerGroups', <<<'JSON'
            {
                "loyalty_groups": [
                  {
                    "id": 35,
                    "name": "პირველი ჯგუფი",
                    "comment": ""
                  },
                  {
                    "id": 36,
                    "name": "მეორე ჯგუფი",
                    "comment": ""
                  }
                ],
                "ex": null
            }
            JSON);

        $groups = $this->client()->loyalty()->customerGroups();

        $this->assertGet('getLoyaltyCustomerGroups');
        $this->assertSame([35, 36], array_map(static fn ($g) => $g->id, $groups));
        $this->assertSame('მეორე ჯგუფი', $groups[1]->name);
        $this->assertSame('', $groups[1]->comment);
    }

    public function test_loyalty_customer_balances(): void
    {
        $this->fake('getLoyaltyCustomerBalances', <<<'JSON'
            {
                "loyalty_balances": [
                  {
                    "id": 1,
                    "code": "1",
                    "name": "ელენე1234",
                    "tel": "99551121061",
                    "qr_code": "1",
                    "balance": 79.88
                  },
                  {
                    "id": 39,
                    "code": "852",
                    "name": "eli",
                    "tel": "",
                    "qr_code": "852",
                    "balance": 0.03
                  }
                ],
                "ex": null
            }
            JSON);

        $balances = $this->client()->loyalty()->customerBalances();

        $this->assertGet('getLoyaltyCustomerBalances');
        $this->assertCount(2, $balances);
        $this->assertSame(79.88, $balances[0]->balance);
        $this->assertSame('99551121061', $balances[0]->tel);
        $this->assertSame(39, $balances[1]->id);
        $this->assertSame('852', $balances[1]->code);
        $this->assertSame('852', $balances[1]->qrCode);
        $this->assertSame(0.03, $balances[1]->balance);
    }

    public function test_loyalty_customer_balance_by_qr_code(): void
    {
        $this->fake('getLoyaltyCustomerBalance/*', <<<'JSON'
            {
                "loyalty_balance": {
                  "id": 1,
                  "code": "1",
                  "name": "ელენე1234",
                  "tel": "99551121061",
                  "qr_code": "1",
                  "balance": 79.88
                },
                "ex": null
            }
            JSON);

        $balance = $this->client()->loyalty()->customerBalance('1');

        $this->assertGet('getLoyaltyCustomerBalance/1');
        $this->assertSame(1, $balance->id);
        $this->assertSame('ელენე1234', $balance->name);
        $this->assertSame(79.88, $balance->balance);

        $this->client()->loyalty()->customerBalance('77/88 9');
        $this->assertGet('getLoyaltyCustomerBalance/77%2F88%209');
    }

    public function test_loyalty_bonus_history(): void
    {
        $this->fake('getLoyaltyBonusHistory/*', <<<'JSON'
            {
                "bonus_history": [
                  {
                    "tdate": "2026-07-10T12:51:25.93",
                    "amount": 18,
                    "bonus": 0.18,
                    "coeff": 1,
                    "comment": null,
                    "id": 1299,
                    "products": [{
                        "id": 5,
                        "quantity": 3,
                        "price": 6
                      }]
                  },
                  {
                    "tdate": "2026-07-10T13:01:26.49",
                    "amount": 0,
                    "bonus": 1,
                    "coeff": -1,
                    "comment": "ჩამოწერა",
                    "id": null,
                    "products": []
                  }
                ],
                "ex": null
            }
            JSON);

        $history = $this->client()->loyalty()->bonusHistory('777888');

        $this->assertGet('getLoyaltyBonusHistory/777888');
        $this->assertCount(2, $history);
        $earned = $history[0];
        $this->assertSame(1299, $earned->id);
        $this->assertSame('2026-07-10T12:51:25.93', $earned->tdate);
        $this->assertSame(18.0, $earned->amount);
        $this->assertSame(0.18, $earned->bonus);
        $this->assertSame(1, $earned->coeff);
        $this->assertNull($earned->comment);
        $this->assertCount(1, $earned->products);
        $this->assertSame(5, $earned->products[0]->id);
        $this->assertSame(3.0, $earned->products[0]->quantity);
        $this->assertSame(6.0, $earned->products[0]->price);

        $spent = $history[1];
        $this->assertNull($spent->id, 'int? in the document');
        $this->assertSame(-1, $spent->coeff);
        $this->assertSame('ჩამოწერა', $spent->comment);
        $this->assertSame([], $spent->products);
    }

    // -------------------------------------------------------
    // Loyalty (Cloud) writes
    // -------------------------------------------------------

    public function test_save_loyalty_customer_sends_the_documented_body(): void
    {
        $this->fake('saveLoyaltyCustomer', ['id' => 1, 'ex' => null]);

        $saved = $this->client()->loyalty()->saveCustomer(new LoyaltyCustomerPayload(
            id: 0, name: 'ბარათის მფლობელი', code: '01001012345', tel: '+995598123456', email: 'customer_email@loyalty.ge',
            sex: true, birthDay: CarbonImmutable::parse('1991-10-06 10:17:40'), qrCode: '777888', comment: 'ბარათის კომენტარი',
            allowPromotions: true,
            loyaltyPrograms: [new LoyaltyCustomerProgramLine(id: 0, programId: 63, status: true)],
            loyaltyGroups: [new LoyaltyCustomerGroupLine(id: 0, groupId: 16)],
        ));

        $this->assertSame(1, $saved->id);
        // the document's example without its trailing comma after "group_id": 16
        $this->assertPost('saveLoyaltyCustomer', self::documented(<<<'JSON'
            {
                "id": 0,
                "name": "ბარათის მფლობელი",
                "code": "01001012345",
                "tel": "+995598123456",
                "email": "customer_email@loyalty.ge",
                "sex": true,
                "birth_day": "1991-10-06T10:17:40",
                "qr_code": "777888",
                "comment": "ბარათის კომენტარი",
                "allow_promotions": true,
                "loyalty_programs": [
                  {
                    "id": 0,
                    "program_id": 63,
                    "status": true
                  }],
                "loyalty_groups": [
                  {
                    "id": 0,
                    "group_id": 16
                  }]
            }
            JSON));
    }

    public function test_save_loyalty_customer_keeps_programs_and_groups_out_unless_given(): void
    {
        $this->fake('saveLoyaltyCustomer', ['id' => 44, 'ex' => null]);

        $this->client()->loyalty()->saveCustomer(new LoyaltyCustomerPayload(id: 44, name: 'როლანდ რეიგანი', sex: false, allowPromotions: false));
        $this->assertPost('saveLoyaltyCustomer', ['id' => 44, 'name' => 'როლანდ რეიგანი', 'sex' => false, 'allow_promotions' => false]);

        // an empty list is a deliberate "none": FINA deletes the card's programs
        $this->client()->loyalty()->saveCustomer(new LoyaltyCustomerPayload(id: 44, name: 'როლანდ რეიგანი', loyaltyPrograms: []));
        $this->assertPost('saveLoyaltyCustomer', ['id' => 44, 'name' => 'როლანდ რეიგანი', 'loyalty_programs' => []]);
    }

    public function test_save_loyalty_group(): void
    {
        $this->fake('saveLoyaltyGroup', ['id' => 1, 'ex' => null]);

        $saved = $this->client()->loyalty()->saveGroup(new LoyaltyGroupPayload(id: 0, name: 'ჯგუფი #1', comment: ''));

        $this->assertSame(1, $saved->id);
        $this->assertPost('saveLoyaltyGroup', self::documented('{"id": 0, "name": "ჯგუფი #1", "comment": ""}'));

        $this->client()->loyalty()->saveGroup(new LoyaltyGroupPayload(id: 7, name: 'VIP'));
        $this->assertPost('saveLoyaltyGroup', ['id' => 7, 'name' => 'VIP']);
    }

    public function test_save_loyalty_bonus_point(): void
    {
        $this->fake('saveLoyaltyBonusPoint', ['id' => 1, 'ex' => null]);

        $saved = $this->client()->loyalty()->saveBonusPoint(new LoyaltyBonusPointPayload(
            cardId: 45, date: CarbonImmutable::parse('2026-07-10 15:45:31'), amount: 9.0, coeff: -1, comment: 'ჩამოჭრა',
        ));

        $this->assertSame(1, $saved->id, 'the id of the operation FINA created');
        $this->assertPost('saveLoyaltyBonusPoint', self::documented('{"id": 45, "date": "2026-07-10T15:45:31", "amount": 9, "coeff": -1, "comment": "ჩამოჭრა"}'));
    }

    public function test_save_doc_discount_operation_maps_res(): void
    {
        $this->fake('saveDocDiscountOperation', ['res' => true, 'ex' => null]);

        $resp = $this->client()->loyalty()->saveDiscountOperation(new DiscountOperationPayload(cardId: 2013, refId: 16975));

        $this->assertInstanceOf(DiscountOperationResponse::class, $resp);
        $this->assertTrue($resp->res);
        $this->assertNull($resp->ex);
        // the document prints {"card_id": 2013, "ref_id": 16975,} (trailing comma)
        $this->assertPost('saveDocDiscountOperation', ['card_id' => 2013, 'ref_id' => 16975]);
    }

    public function test_save_doc_discount_operation_keeps_an_id_answer_reachable(): void
    {
        $this->fake('saveDocDiscountOperation', ['id' => 8800, 'ex' => null]);

        $resp = $this->client()->loyalty()->saveDiscountOperation(['card_id' => 2013, 'ref_id' => 16975]);

        $this->assertNull($resp->res, 'no res in the answer: unknown, not "not applied", so nobody sends it again');
        $this->assertSame(['id' => 8800, 'ex' => null], $resp->raw);
        $this->assertPost('saveDocDiscountOperation', ['card_id' => 2013, 'ref_id' => 16975]);

        $this->assertFalse(DiscountOperationResponse::fromArray(['res' => false, 'ex' => null])->res, 'a res FINA sends is kept');
        $this->assertNull(DiscountOperationResponse::fromArray(['res' => null, 'ex' => null])->res);
    }

    // -------------------------------------------------------
    // `ex` on every new endpoint
    // -------------------------------------------------------

    /** @return iterable<string, array{callable(FinaClient): mixed, string, string}> */
    public static function newEndpoints(): iterable
    {
        yield 'getServiceConsumptionMaterials' => [fn (FinaClient $c) => $c->services()->consumptionMaterials(), 'getServiceConsumptionMaterials', 'operation.getServiceConsumptionMaterials'];
        yield 'getStoreAdditionalFields' => [fn (FinaClient $c) => $c->reference()->storeAdditionalFields(), 'getStoreAdditionalFields', 'operation.getStoreAdditionalFields'];
        yield 'getStores typed' => [fn (FinaClient $c) => $c->reference()->storesTyped(), 'getStores', 'operation.getStores'];
        yield 'getLoyaltyCustomers' => [fn (FinaClient $c) => $c->loyalty()->customers(), 'getLoyaltyCustomers', 'operation.getLoyaltyCustomers'];
        yield 'getLoyaltyCustomer' => [fn (FinaClient $c) => $c->loyalty()->customer(44), 'getLoyaltyCustomer/44', 'operation.getLoyaltyCustomer'];
        yield 'getLoyaltyCustomerPrograms' => [fn (FinaClient $c) => $c->loyalty()->customerPrograms(), 'getLoyaltyCustomerPrograms', 'operation.getLoyaltyCustomerPrograms'];
        yield 'getLoyaltyCustomerGroups' => [fn (FinaClient $c) => $c->loyalty()->customerGroups(), 'getLoyaltyCustomerGroups', 'operation.getLoyaltyCustomerGroups'];
        yield 'getLoyaltyCustomerBalances' => [fn (FinaClient $c) => $c->loyalty()->customerBalances(), 'getLoyaltyCustomerBalances', 'operation.getLoyaltyCustomerBalances'];
        yield 'getLoyaltyCustomerBalance' => [fn (FinaClient $c) => $c->loyalty()->customerBalance('1'), 'getLoyaltyCustomerBalance/1', 'operation.getLoyaltyCustomerBalance'];
        yield 'getLoyaltyBonusHistory' => [fn (FinaClient $c) => $c->loyalty()->bonusHistory('1'), 'getLoyaltyBonusHistory/1', 'operation.getLoyaltyBonusHistory'];
        yield 'getDocAdvProduction' => [fn (FinaClient $c) => $c->documents()->getAdvProductionTyped(5), 'getDocAdvProduction/5', 'operation.getDocAdvProduction'];
        yield 'saveLoyaltyGroup' => [fn (FinaClient $c) => $c->loyalty()->saveGroup(new LoyaltyGroupPayload(0, 'g')), 'saveLoyaltyGroup', 'operation.saveLoyaltyGroup'];
        yield 'fulfillmentCustomerOrder' => [fn (FinaClient $c) => $c->documents()->fulfillCustomerOrder(4164), 'fulfillmentCustomerOrder', 'operation.fulfillmentCustomerOrder'];
        yield 'saveDocDiscountOperation' => [fn (FinaClient $c) => $c->loyalty()->saveDiscountOperation(new DiscountOperationPayload(1, 2)), 'saveDocDiscountOperation', 'operation.saveDocDiscountOperation'];
    }

    /** @param callable(FinaClient): mixed $call */
    #[DataProvider('newEndpoints')]
    public function test_new_endpoint_throws_on_ex(callable $call, string $path, string $context): void
    {
        $this->fake($path, ['id' => 0, 'ex' => 'Object reference not set']);

        try {
            $call($this->client());
            $this->fail('Expected FinaRemoteException');
        } catch (FinaRemoteException $e) {
            $this->assertSame('Object reference not set', $e->ex);
            $this->assertStringContainsString($context, $e->getMessage());
            $this->assertSame(['id' => 0, 'ex' => 'Object reference not set'], $e->response);
        }
    }

    public function test_new_typed_reads_tolerate_a_missing_root_key(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*' => Http::response(['ex' => null]),
        ]);
        $c = $this->client();

        $this->assertSame([], $c->services()->consumptionMaterials());
        $this->assertSame([], $c->reference()->storeAdditionalFields());
        $this->assertSame([], $c->reference()->storesTyped());
        $this->assertSame([], $c->loyalty()->customers());
        $this->assertSame([], $c->loyalty()->customerPrograms());
        $this->assertSame([], $c->loyalty()->customerGroups());
        $this->assertSame([], $c->loyalty()->customerBalances());
        $this->assertSame([], $c->loyalty()->bonusHistory('1'));

        // a single object that is not there reads as all null, not as card 0 / balance 0 / document 0
        $card = $c->loyalty()->customer(1);
        $this->assertNull($card->id);
        $this->assertNull($card->name);
        $this->assertNull($card->loyaltyPrograms);
        $balance = $c->loyalty()->customerBalance('1');
        $this->assertNull($balance->id);
        $this->assertNull($balance->balance);
        $doc = $c->documents()->getAdvProductionTyped(1);
        $this->assertNull($doc->id);
        $this->assertNull($doc->amount);
        $this->assertNull($doc->products);
    }

    public function test_a_balance_answer_without_a_card_reads_all_null(): void
    {
        $this->fake('getLoyaltyCustomerBalance/*', '{"loyalty_balance": null, "ex": null}');

        $balance = $this->client()->loyalty()->customerBalance('000000');

        $this->assertGet('getLoyaltyCustomerBalance/000000');
        foreach (['id', 'code', 'name', 'tel', 'qrCode', 'balance'] as $field) {
            $this->assertNull($balance->{$field}, $field);
        }
        $this->assertSame([], $balance->raw);
    }

    // -------------------------------------------------------
    // Fields an answer lacks
    // -------------------------------------------------------

    public function test_loyalty_fields_the_answer_lacks_are_null_and_sent_ones_are_kept(): void
    {
        $program = LoyaltyProgramDto::fromArray(['id' => 59, 'name' => 'აქცია']);
        $this->assertNull($program->status, 'an absent status is not "inactive"');
        $this->assertNull($program->usageType, 'an absent usage type is not 0 (chosen cards)');
        $this->assertNull($program->programType);
        $this->assertNull($program->startDate);
        $this->assertNull($program->comment);

        $sent = LoyaltyProgramDto::fromArray(['id' => 61, 'status' => false, 'program_type' => 2, 'usage_type' => 0]);
        $this->assertFalse($sent->status);
        $this->assertSame(0, $sent->usageType);

        $enrolment = LoyaltyCustomerProgramDto::fromArray(['id' => 391, 'program_id' => 63]);
        $this->assertNull($enrolment->status);
        $this->assertNull($enrolment->startDate);
        $this->assertFalse(LoyaltyCustomerProgramDto::fromArray(['id' => 391, 'status' => false])->status);
        $this->assertNull(LoyaltyCustomerProgramDto::fromArray(['id' => 391])->programId);
        $this->assertNull(LoyaltyCustomerGroupDto::fromArray(['id' => 595])->groupId);

        $balance = LoyaltyBalanceDto::fromArray(['id' => 1, 'qr_code' => '1']);
        $this->assertNull($balance->balance, 'an absent balance is not 0');
        $this->assertSame(1, $balance->id);
        $this->assertSame(0.0, LoyaltyBalanceDto::fromArray(['id' => 1, 'balance' => 0])->balance);

        $card = LoyaltyCustomerDto::fromArray(['id' => 7, 'name' => 'n']);
        $this->assertNull($card->allowPromotions, 'an absent consent is not "no"');
        $this->assertNull($card->code);
        $this->assertNull($card->tel);
        $this->assertNull($card->email);
        $this->assertNull($card->comment);
        $this->assertSame([], LoyaltyCustomerDto::fromArray(['id' => 7, 'loyalty_programs' => [], 'loyalty_groups' => []])->loyaltyPrograms, 'an empty list FINA sends is kept');

        $entry = LoyaltyBonusHistoryDto::fromArray(['tdate' => '2026-07-10T12:51:25.93']);
        $this->assertNull($entry->amount);
        $this->assertNull($entry->bonus);
        $this->assertNull($entry->coeff);
        $this->assertNull($entry->products);
        $line = LoyaltyBonusProductDto::fromArray(['id' => 5]);
        $this->assertNull($line->quantity);
        $this->assertNull($line->price);
    }

    public function test_a_card_from_the_list_saved_back_keeps_its_programs_and_groups(): void
    {
        $this->fake('saveLoyaltyCustomer', ['id' => 2, 'ex' => null]);

        // a row of getLoyaltyCustomers as the document prints it: no loyalty_programs / loyalty_groups
        $listed = LoyaltyCustomerDto::fromArray(self::documented('{"id": 2, "code": "2", "name": "ლევანი", "qr_code": "2", "tel": "599705803", "birth_day": "2025-06-05T00:00:00", "email": "beqauri", "sex": true, "comment": "test", "allow_promotions": false, "cerate_date": "2025-06-05T12:59:56.837"}'));
        $payload = self::payloadFrom($listed, 'ლევანი ახალი');

        $this->assertArrayNotHasKey('loyalty_programs', $payload->toArray());
        $this->assertArrayNotHasKey('loyalty_groups', $payload->toArray());
        $this->client()->loyalty()->saveCustomer($payload);
        $this->assertPost('saveLoyaltyCustomer', ['id' => 2, 'name' => 'ლევანი ახალი', 'code' => '2', 'tel' => '599705803', 'email' => 'beqauri', 'sex' => true, 'qr_code' => '2', 'comment' => 'test', 'allow_promotions' => false]);

        // the same card read on its own carries its lists, and they go back as they came
        $single = LoyaltyCustomerDto::fromArray(['id' => 44, 'name' => 'n', 'loyalty_programs' => [['id' => 391, 'program_id' => 63, 'status' => true]], 'loyalty_groups' => [['id' => 595, 'group_id' => 35]]]);
        $body = self::payloadFrom($single, 'n')->toArray();
        $this->assertSame([['id' => 391, 'program_id' => 63, 'status' => true]], $body['loyalty_programs']);
        $this->assertSame([['id' => 595, 'group_id' => 35]], $body['loyalty_groups']);

        // a card with no fields at all sends only what the caller set
        $this->assertSame(['id' => 9, 'name' => 'x'], self::payloadFrom(LoyaltyCustomerDto::fromArray(['id' => 9]), 'x')->toArray());
    }

    /** How a caller turns a card it read into the payload that saves it, passing null through. */
    private static function payloadFrom(LoyaltyCustomerDto $card, string $name): LoyaltyCustomerPayload
    {
        return new LoyaltyCustomerPayload(
            id: $card->id ?? throw new LogicException('no card id: nothing to update'),
            name: $name,
            code: $card->code,
            tel: $card->tel,
            email: $card->email,
            sex: $card->sex,
            qrCode: $card->qrCode,
            comment: $card->comment,
            allowPromotions: $card->allowPromotions,
            loyaltyPrograms: $card->loyaltyPrograms === null ? null : array_map(
                static fn (LoyaltyCustomerProgramDto $p) => new LoyaltyCustomerProgramLine(
                    $p->id,
                    $p->programId ?? throw new LogicException('enrolment without a program'),
                    $p->status ?? throw new LogicException('enrolment without a status'),
                ),
                $card->loyaltyPrograms
            ),
            loyaltyGroups: $card->loyaltyGroups === null ? null : array_map(
                static fn (LoyaltyCustomerGroupDto $g) => new LoyaltyCustomerGroupLine(
                    $g->id,
                    $g->groupId ?? throw new LogicException('membership without a group'),
                ),
                $card->loyaltyGroups
            ),
        );
    }

    /**
     * Every response DTO this release adds, and the properties allowed to be non-null when the
     * answer is empty: the id that names a list row or a line (0, as the package's older DTOs
     * read it) and `raw`.
     *
     * @return iterable<string, array{class-string, list<string>}>
     */
    public static function newResponseDtos(): iterable
    {
        yield 'LoyaltyCustomerDto' => [LoyaltyCustomerDto::class, []];
        yield 'LoyaltyBalanceDto' => [LoyaltyBalanceDto::class, []];
        yield 'LoyaltyProgramDto' => [LoyaltyProgramDto::class, ['id']];
        yield 'LoyaltyGroupDto' => [LoyaltyGroupDto::class, ['id']];
        yield 'LoyaltyCustomerProgramDto' => [LoyaltyCustomerProgramDto::class, ['id']];
        yield 'LoyaltyCustomerGroupDto' => [LoyaltyCustomerGroupDto::class, ['id']];
        yield 'LoyaltyBonusHistoryDto' => [LoyaltyBonusHistoryDto::class, []];
        yield 'LoyaltyBonusProductDto' => [LoyaltyBonusProductDto::class, ['id']];
        yield 'DiscountOperationResponse' => [DiscountOperationResponse::class, []];
        yield 'StoreDto' => [StoreDto::class, ['id']];
        yield 'ContragentDto' => [ContragentDto::class, ['id']];
        yield 'ServiceConsumptionDto' => [ServiceConsumptionDto::class, ['id']];
        yield 'ServiceConsumptionLineDto' => [ServiceConsumptionLineDto::class, ['productId']];
        yield 'AdvProductionDocDto' => [AdvProductionDocDto::class, []];
        yield 'AdvProductionLineDto' => [AdvProductionLineDto::class, ['id']];
    }

    /**
     * @param  class-string  $class
     * @param  list<string>  $rowIds
     */
    #[DataProvider('newResponseDtos')]
    public function test_every_field_a_new_dto_is_not_sent_is_null(string $class, array $rowIds): void
    {
        $dto = $class::fromArray([]);

        foreach ((new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            if ($name === 'raw') {
                $this->assertSame([], $dto->{$name});
            } elseif (in_array($name, $rowIds, true)) {
                $this->assertSame(0, $dto->{$name}, "{$class}::\${$name}");
            } else {
                $this->assertTrue($property->getType()?->allowsNull() ?? false, "{$class}::\${$name} must be nullable");
                $this->assertNull($dto->{$name}, "{$class}::\${$name}");
            }
        }
    }
}
