<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Integration;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Exceptions\FinaHttpException;
use Fina\Sdk\Laravel\FinaSdkServiceProvider;
use Fina\Sdk\Laravel\Operation\Dto\DocTypeDto;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

/**
 * Read-only checks against a real FINA server. Skipped unless
 * FINA_LIVE_BASE_URL, FINA_LIVE_LOGIN and FINA_LIVE_PASSWORD are set:
 *
 *   FINA_LIVE_BASE_URL=... FINA_LIVE_LOGIN=... FINA_LIVE_PASSWORD=... \
 *     vendor/bin/phpunit --testsuite Integration
 *
 * Every outgoing request is inspected: a URL containing /save or /update
 * aborts the test run. Nothing here may ever write into FINA.
 *
 * For each endpoint the documented (PDF 8.0) keys must all be present in the
 * real response, so a DTO can never silently map a renamed field to 0/''.
 * Endpoints the installed FINA build does not have (404) are listed on STDERR
 * at the end instead of failing; extra keys the server adds are listed too.
 */
#[Group('integration')]
final class LiveFinaReadOnlyTest extends TestCase
{
    /**
     * Documented keys the owner's FINA build (older than PDF 8.0.20260303) does
     * not send. DTOs default them; anything else missing is a mapping defect.
     *
     * @var array<string, string[]>
     */
    private const KNOWN_MISSING_ON_THIS_BUILD = [
        'getProducts' => ['plu'],
        'getProductsAfter' => ['plu'],
        'getDocProductOut' => ['price_type'],
    ];

    /** @var array<string, string[]> endpoint => extra keys seen */
    private static array $extraKeys = [];

    /** @var string[] endpoints answering 404 on this build */
    private static array $unavailable = [];

    /** @var string[] endpoints answering 200 */
    private static array $available = [];

    /** @var string[] journals that had no rows in the probed window */
    private static array $emptyJournals = [];

    /** @var array<string, mixed> lazily fetched ids shared between tests */
    private static array $memo = [];

    protected function getPackageProviders($app): array
    {
        return [FinaSdkServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('fina.base_url', (string) getenv('FINA_LIVE_BASE_URL'));
        $app['config']->set('fina.login', (string) getenv('FINA_LIVE_LOGIN'));
        $app['config']->set('fina.password', (string) getenv('FINA_LIVE_PASSWORD'));
        $app['config']->set('fina.timeout', 120);
        $app['config']->set('fina.retry_times', 1);
        $app['config']->set('fina.retry_sleep_ms', 500);
        $app['config']->set('cache.default', 'file');
        $app['config']->set('cache.stores.file.path', sys_get_temp_dir().'/fina-sdk-live-cache');
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('FINA_LIVE_BASE_URL') === false || getenv('FINA_LIVE_LOGIN') === false || getenv('FINA_LIVE_PASSWORD') === false) {
            $this->markTestSkipped('FINA_LIVE_* environment not set');
        }

        Http::globalRequestMiddleware(static function ($request) {
            $uri = (string) $request->getUri();
            if (preg_match('#/(save|update)[A-Za-z]*#', $uri)) {
                throw new RuntimeException('Refusing to send a write request to FINA: '.$uri);
            }

            return $request;
        });
    }

    protected function tearDown(): void
    {
        Http::globalRequestMiddleware(static fn ($r) => $r);
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        $available = array_values(array_unique(self::$available));
        $unavailable = array_values(array_unique(self::$unavailable));
        sort($available);
        sort($unavailable);

        fwrite(STDERR, "\nEndpoints answering on this FINA build (".count($available).'): '.implode(', ', $available)."\n");
        fwrite(STDERR, 'Endpoints answering 404 on this FINA build ('.count($unavailable).'): '.implode(', ', $unavailable)."\n");

        if (self::$emptyJournals !== []) {
            fwrite(STDERR, 'Journals with no rows in the probed window (keys unverified): '.implode(', ', self::$emptyJournals)."\n");
        }

        if (self::$extraKeys !== []) {
            fwrite(STDERR, "Fields present on the live server but not in PDF 8.0:\n");
            foreach (self::$extraKeys as $endpoint => $keys) {
                fwrite(STDERR, sprintf("  %-32s %s\n", $endpoint, implode(', ', $keys)));
            }
        }

        parent::tearDownAfterClass();
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

    private function fina(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    /**
     * Run one endpoint call; a 404 is recorded as "not on this build" and
     * returns null so the remaining checks of the test still run.
     */
    private function probe(string $endpoint, callable $call): mixed
    {
        usleep(200_000);

        try {
            $result = $call();
            self::$available[] = $endpoint;

            return $result;
        } catch (FinaHttpException $e) {
            if ($e->status === 404) {
                self::$unavailable[] = $endpoint;

                return null;
            }

            throw $e;
        }
    }

    /** Like call(), but a 404 marks the whole test incomplete. */
    private function need(string $endpoint, callable $call): mixed
    {
        $result = $this->probe($endpoint, $call);
        if ($result === null) {
            $this->markTestIncomplete("{$endpoint} is not available on this FINA build (404)");
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  string[]  $documented
     */
    private function assertDocumentedKeys(string $endpoint, array $row, array $documented): void
    {
        $actual = array_keys($row);
        $missing = array_values(array_diff($documented, $actual, self::KNOWN_MISSING_ON_THIS_BUILD[$endpoint] ?? []));
        $this->assertSame([], $missing, "{$endpoint}: documented keys missing from the live response");

        $extra = array_values(array_diff($actual, $documented));
        if ($extra !== []) {
            self::$extraKeys[$endpoint] = $extra;
        }
    }

    /**
     * Collection endpoint: root key must exist; when it has rows the first row
     * must carry every documented key.
     *
     * @param  string[]  $documented
     */
    private function assertCollection(string $endpoint, ?array $response, string $rootKey, array $documented): void
    {
        if ($response === null) {
            return;
        }

        $this->assertArrayHasKey($rootKey, $response, "{$endpoint}: root key");
        $row = $this->firstRow($response, $rootKey);
        if ($row === null) {
            self::$emptyJournals[] = $endpoint;

            return;
        }

        $this->assertDocumentedKeys($endpoint, $row, $documented);
    }

    /** @return array<string, mixed>|null */
    private function firstRow(array $response, string $rootKey): ?array
    {
        $rows = (array) ($response[$rootKey] ?? []);
        $first = reset($rows);

        return is_array($first) ? $first : null;
    }

    private function memo(string $key, callable $resolve): mixed
    {
        if (! array_key_exists($key, self::$memo)) {
            self::$memo[$key] = $resolve();
        }

        return self::$memo[$key];
    }

    private function storeId(): int
    {
        return (int) $this->memo('store', fn () => (int) ($this->firstRow($this->fina()->reference()->stores(), 'stores')['id'] ?? 1));
    }

    /** @return int[] three product ids that currently have stock somewhere */
    private function stockedProductIds(): array
    {
        return (array) $this->memo('stocked', function () {
            $ids = [];
            foreach ((array) $this->fina()->products()->rest()['rest'] as $r) {
                if ((float) $r['rest'] > 0) {
                    $ids[(int) $r['id']] = true;
                }
                if (count($ids) >= 3) {
                    break;
                }
            }

            return array_keys($ids) ?: [1];
        });
    }

    /** @return array<int, int[]> doc_type => operation ids from the last 14 days, newest first */
    private function recentSales(): array
    {
        return (array) $this->memo('recent_sales', function () {
            [$from, $to] = $this->window(14);
            $byType = [];
            foreach ($this->fina()->reporting()->realizesJournalTyped($from, $to)->journals as $row) {
                $byType[$row->docType][] = $row->id;
            }
            foreach ($byType as &$ids) {
                rsort($ids);
            }

            return $byType;
        });
    }

    private function window(int $days): array
    {
        $to = CarbonImmutable::now();

        return [$to->subDays($days)->startOfDay(), $to];
    }

    // ------------------------------------------------------------------
    // Reference data
    // ------------------------------------------------------------------

    public function test_stores_and_store_groups(): void
    {
        $r = $this->need('getStores', fn () => $this->fina()->reference()->stores());
        $row = $this->firstRow($r, 'stores');
        $this->assertNotNull($row);
        $this->assertDocumentedKeys('getStores', $row, ['id', 'group_id', 'name', 'address', 'project_id']);

        $groups = $this->probe('getStoreGroups', fn () => $this->fina()->reference()->storeGroups());
        if ($groups !== null) {
            $this->assertNotEmpty($groups);
            $this->assertDocumentedKeys('getStoreGroups', $groups[0], ['id', 'parent_id', 'path', 'name']);
        }
    }

    public function test_price_types_include_retail(): void
    {
        $r = $this->need('getPriceTypes', fn () => $this->fina()->reference()->priceTypes());
        $ids = array_map(static fn ($t) => (int) $t['id'], (array) $r['types']);
        $this->assertContains(3, $ids);
        $this->assertDocumentedKeys('getPriceTypes', $this->firstRow($r, 'types'), ['id', 'name']);
    }

    public function test_doc_types_cover_sales_retail_and_moves(): void
    {
        $types = $this->need('getDocTypes', fn () => $this->fina()->reference()->docTypes());
        $this->assertContainsOnlyInstancesOf(DocTypeDto::class, $types);
        $byType = [];
        foreach ($types as $t) {
            $byType[$t->type] = $t;
        }
        $this->assertArrayHasKey(21, $byType);
        $this->assertArrayHasKey(23, $byType);
        $this->assertArrayHasKey(20, $byType);
        $this->assertTrue($byType[21]->apiSupported);
    }

    public function test_simple_reference_lists(): void
    {
        $ref = $this->fina()->reference();

        $this->assertCollection('getProjects', $this->probe('getProjects', fn () => $ref->projects()), 'projects', ['id', 'name']);
        $this->assertCollection('getCashes', $this->probe('getCashes', fn () => $ref->cashes()), 'cashes', ['id', 'name']);
        $this->assertCollection('getTerminals', $this->probe('getTerminals', fn () => $ref->terminals()), 'terminals', ['id', 'name']);
        $this->assertCollection('getCreditBanks', $this->probe('getCreditBanks', fn () => $ref->creditBanks()), 'credits', ['id', 'name']);
        $this->assertCollection('getDiscountTypes', $this->probe('getDiscountTypes', fn () => $this->fina()->reference()->discountTypes() === [] ? ['types' => []] : ['types' => [['id' => 1, 'discount_percent' => 0]]]), 'types', ['id', 'discount_percent']);

        $units = $this->probe('getUnits', fn () => $ref->units());
        $this->assertNotEmpty($units);
        $this->assertNotSame('', $units[0]->name);

        $users = $this->probe('getUsers', fn () => $ref->users());
        $this->assertNotEmpty($users);
        $perms = $this->probe('getUserPermissions', fn () => $ref->userPermissions($users[0]->id));
        if ($perms !== null) {
            $this->assertIsArray($perms->priceTypes);
        }

        $banks = $this->probe('getBankAccounts', fn () => $ref->bankAccounts());
        if ($banks) {
            $this->assertNotSame('', $banks[0]->account);
        }

        $staffs = $this->probe('getStaffs', fn () => $ref->staffs());
        $this->assertNotEmpty($staffs);
        $this->assertNotSame('', $staffs[0]->name);
        $this->assertIsArray($this->probe('getStaffGroups', fn () => $ref->staffGroups()));

        $this->assertCollection('getGiftCards', $this->probe('getGiftCards', fn () => ['gifts' => array_map(static fn ($g) => (array) $g, $ref->giftCards())]), 'gifts', []);
        $coeff = $this->probe('getBonusCoeff', fn () => $this->fina()->loyalty()->bonusCoeff());
        if ($coeff !== null) {
            $this->assertIsFloat($coeff->coeff);
        }
        $this->assertIsArray($this->probe('getTransportationMeans', fn () => $ref->transportationMeans()) ?? []);
    }

    public function test_additional_field_descriptors(): void
    {
        $fina = $this->fina();
        foreach ([
            'getProductAdditionalFields' => fn () => $fina->products()->additionalFields(),
            'getStaffAdditionalFields' => fn () => $fina->reference()->staffAdditionalFields(),
            'getProvidedServiceAdditionalFields' => fn () => $fina->services()->providedAdditionalFields(),
            'getInventoryAdditionalFields' => fn () => $fina->inventories()->additionalFields(),
            'getDocAdditionalFields' => fn () => $fina->reference()->docAdditionalFields(21),
            'getCustomerAdditionalFields' => fn () => $fina->customers()->additionalFields()['fields'] ?? [],
            'getVendorAdditionalFields' => fn () => $fina->vendors()->additionalFields()['fields'] ?? [],
        ] as $name => $resolve) {
            $fields = $this->probe($name, $resolve);
            if ($fields !== null) {
                $this->assertIsArray($fields, $name);
            }
        }
        $this->addToAssertionCount(1);
    }

    public function test_account_value_and_details(): void
    {
        $ref = $this->fina()->reference();

        $value = $this->probe('getAccountValue', fn () => $ref->accountValue('1410', CarbonImmutable::now()));
        if ($value !== null) {
            $this->assertGreaterThanOrEqual(0.0, $value->debitVal);
            $this->assertGreaterThanOrEqual(0.0, $value->creditVal);
        }

        $details = $this->probe('getAccountValueDetails', fn () => $ref->accountValueDetails('1410', CarbonImmutable::now()));
        if ($details !== null) {
            $this->assertIsArray($details);
        }
        $this->addToAssertionCount(1);
    }

    // ------------------------------------------------------------------
    // Products
    // ------------------------------------------------------------------

    public function test_products_catalog(): void
    {
        $r = $this->need('getProducts', fn () => $this->fina()->products()->all());
        $row = $this->firstRow($r, 'products');
        $this->assertNotNull($row);
        $this->assertDocumentedKeys('getProducts', $row, ['id', 'group_id', 'web_group_id', 'unit_id', 'code', 'name', 'name_eng', 'name_rus', 'comment', 'partnumber', 'weight', 'volume', 'vat', 'order_id', 'min_quantity', 'plu', 'add_fields']);

        $this->assertCollection('getProductGroups', $this->probe('getProductGroups', fn () => $this->fina()->products()->groups()), 'groups', ['id', 'parent_id', 'order_id', 'path', 'name']);
        $this->assertCollection('getWebProductGroups', $this->probe('getWebProductGroups', fn () => $this->fina()->products()->webGroups()), 'groups', ['id', 'parent_id', 'order_id', 'path', 'image', 'name', 'name2', 'name3']);
    }

    public function test_products_by_ids_barcodes_images_and_characteristics(): void
    {
        $ids = $this->stockedProductIds();
        $products = $this->fina()->products();

        $byIds = $this->need('getProductsArray', fn () => $products->byIds($ids));
        $this->assertCount(count($ids), (array) $byIds['products']);

        $this->assertCollection('getProductsBarcodeArray', $this->probe('getProductsBarcodeArray', fn () => $products->barcodesByProductIds($ids)), 'barcodes', ['product_id', 'barcode']);
        $this->assertCollection('getProductsImageArray', $this->probe('getProductsImageArray', fn () => $products->imagesByProductIds([$ids[0]])), 'images', ['id', 'product_id', 'image']);
        $this->assertCollection('getProductImages', $this->probe('getProductImages', fn () => $products->images($ids[0])), 'images', ['id', 'image']);
        $this->assertCollection('getCharacteristics', $this->probe('getCharacteristics', fn () => $products->characteristics()), 'characteristics', ['id', 'tag', 'type', 'name', 'name2', 'name3']);
        $this->assertCollection('getCharacteristicValues', $this->probe('getCharacteristicValues', fn () => $products->characteristicValues()), 'characteristic_values', ['product_id', 'characteristic_id', 'value', 'value2', 'value3']);
        $this->assertCollection('getCharacteristicValuesArray', $this->probe('getCharacteristicValuesArray', fn () => $products->characteristicValuesByProductIds($ids)), 'characteristic_values', ['product_id', 'characteristic_id', 'value', 'value2', 'value3']);
    }

    public function test_products_prices(): void
    {
        $r = $this->need('getProductPrices', fn () => $this->fina()->products()->prices());
        $row = $this->firstRow($r, 'prices');
        $this->assertNotNull($row);
        $this->assertDocumentedKeys('getProductPrices', $row, ['product_id', 'price_id', 'price', 'discount_price', 'currency', 'discount_start', 'discount_end']);

        $ids = $this->stockedProductIds();
        $adv = $this->probe('getProductPricesAdvance', fn () => $this->fina()->products()->pricesAdvance($ids, 3));
        if ($adv !== null) {
            $this->assertArrayHasKey('prices', $adv);
            foreach ((array) $adv['prices'] as $p) {
                $this->assertSame(3, (int) $p['price_id']);
                $this->assertContains((int) $p['product_id'], $ids);
            }
        }

        $this->assertCollection('getProductPricesAfter', $this->probe('getProductPricesAfter', fn () => $this->fina()->products()->pricesAfter(CarbonImmutable::now()->subDays(3))), 'prices', ['product_id', 'price_id', 'price', 'discount_price', 'currency', 'discount_start', 'discount_end']);
        $this->assertCollection('getProvidedServicePrices', $this->probe('getProvidedServicePrices', fn () => $this->fina()->services()->providedPrices()), 'prices', ['product_id', 'price_id', 'price']);
    }

    public function test_products_units_places_packs_subcodes_on_way(): void
    {
        $products = $this->fina()->products();

        $this->assertCollection('getProductUnits', $this->probe('getProductUnits', fn () => $products->units()), 'units', ['product_id', 'unit_id', 'quantity', 'barcode', 'unit_prices']);
        $this->assertCollection('getProductPlaces', $this->probe('getProductPlaces', fn () => $products->places()), 'places', ['product_id', 'store_id', 'place']);
        $this->assertCollection('getPackedProducts', $this->probe('getPackedProducts', fn () => $products->packed()), 'pack_info', ['product_id', 'pack_id']);
        $this->assertCollection('getSubCodeTypes', $this->probe('getSubCodeTypes', fn () => $products->subCodeTypes()), 'types', ['name', 'header']);
        $this->assertCollection('getProductSubCodes', $this->probe('getProductSubCodes', fn () => $products->subCodes()), 'sub_codes', ['product_id', 'sub_id', 'barcode', 'sub_columns']);
        $this->assertCollection('getProductsOnWay', $this->probe('getProductsOnWay', fn () => $products->onWay()), 'on_way', ['id', 'date', 'quantity']);
        $this->assertCollection('getProductsAfter', $this->probe('getProductsAfter', fn () => $products->after(CarbonImmutable::now()->subDays(3))), 'products', ['id', 'group_id', 'web_group_id', 'unit_id', 'code', 'name', 'name_eng', 'name_rus', 'comment', 'partnumber', 'weight', 'volume', 'vat', 'order_id', 'min_quantity', 'plu', 'add_fields']);
    }

    public function test_products_rest_family(): void
    {
        $products = $this->fina()->products();
        $store = $this->storeId();
        $ids = $this->stockedProductIds();

        $rest = $this->need('getProductsRest', fn () => $products->rest());
        $row = $this->firstRow($rest, 'rest');
        $this->assertNotNull($row);
        $this->assertDocumentedKeys('getProductsRest', $row, ['id', 'store', 'rest', 'reserve']);

        $byIds = $this->probe('getProductsRestArray', fn () => $products->restByProductIds($ids));
        $this->assertCollection('getProductsRestArray', $byIds, 'rest', ['id', 'store', 'rest', 'reserve']);
        if ($byIds !== null) {
            $returned = array_unique(array_map(static fn ($r) => (int) $r['id'], (array) $byIds['rest']));
            $this->assertSame([], array_diff($returned, $ids), 'restByProductIds must only return the requested products');
        }

        $byStore = $this->probe('getProductsRestByStore', fn () => $products->restByStore($store));
        $this->assertCollection('getProductsRestByStore', $byStore, 'store_rest', ['id', 'rest']);

        $this->assertCollection('getProductsRestSummary', $this->probe('getProductsRestSummary', fn () => $products->restSummary($ids, [], CarbonImmutable::now())), 'store_rest', ['id', 'rest']);

        $advance = $this->probe('getProductsRestAdvance', fn () => $products->restAdvance($ids, 0, 3));
        $this->assertCollection('getProductsRestAdvance', $advance, 'rest_info', ['id', 'rest', 'price']);
        if ($advance !== null) {
            $this->assertCount(count($ids), (array) $advance['rest_info']);
        }

        $this->assertCollection('getSubProductsRest', $this->probe('getSubProductsRest', fn () => $products->subRest()), 'rest', ['id', 'sub_id', 'store', 'rest', 'reserve']);

        $since = CarbonImmutable::now()->subDay();
        $this->assertCollection('getProductsRestAfter', $this->probe('getProductsRestAfter', fn () => $products->restAfter($since)), 'rest', ['id', 'store', 'rest', 'reserve']);
        $this->assertCollection('getProductsRestByStoreAfter', $this->probe('getProductsRestByStoreAfter', fn () => $products->restByStoreAfter($store, $since)), 'store_rest', ['id', 'rest']);
    }

    public function test_products_self_cost(): void
    {
        $ids = $this->stockedProductIds();
        $r = $this->need('getProductsSelfCost', fn () => $this->fina()->products()->selfCost($ids, CarbonImmutable::now()));
        $this->assertArrayHasKey('cost_info', $r);
        $this->assertNotEmpty($r['cost_info'], 'stocked products must have an average cost');

        foreach ((array) $r['cost_info'] as $row) {
            $this->assertDocumentedKeys('getProductsSelfCost', $row, ['id', 'cost']);
            $this->assertContains((int) $row['id'], $ids);
            $this->assertIsNumeric($row['cost']);
            $this->assertGreaterThan(0, (float) $row['cost']);
        }
    }

    // ------------------------------------------------------------------
    // Services, inventories, contragents
    // ------------------------------------------------------------------

    public function test_services_and_inventories(): void
    {
        $fina = $this->fina();

        $this->assertCollection('getProvidedServices', $this->probe('getProvidedServices', fn () => $fina->services()->provided()), 'services', ['id', 'group_id', 'unit_id', 'code', 'name', 'comment', 'vat', 'add_fields']);
        $this->assertCollection('getReceivedServices', $this->probe('getReceivedServices', fn () => $fina->services()->received()), 'services', ['id', 'group_id', 'unit_id', 'code', 'name', 'comment', 'vat', 'add_fields']);
        $this->assertCollection('getProvidedServiceGroups', $this->probe('getProvidedServiceGroups', fn () => ['groups' => $fina->reference()->providedServiceGroups()]), 'groups', ['id', 'parent_id', 'order_id', 'path', 'name']);
        $this->assertCollection('getInventories', $this->probe('getInventories', fn () => $fina->inventories()->all()), 'inventories', ['id', 'group_id', 'unit_id', 'code', 'name', 'comment', 'in_date', 'amortization_type', 'service_term', 'liquidation_cost', 'add_fields']);
        $this->assertCollection('getInventoriesRest', $this->probe('getInventoriesRest', fn () => $fina->inventories()->rest()), 'rest', ['id', 'store', 'rest', 'reserve']);
        $this->assertCollection('getInventoriesRestByStore', $this->probe('getInventoriesRestByStore', fn () => $fina->inventories()->restByStore($this->storeId())), 'store_rest', ['id', 'rest']);
        $this->assertCollection('getInventoryGroups', $this->probe('getInventoryGroups', fn () => ['groups' => $fina->reference()->inventoryGroups()]), 'groups', ['id', 'parent_id', 'order_id', 'path', 'name']);
        $this->addToAssertionCount(1);
    }

    public function test_contragents(): void
    {
        $fina = $this->fina();

        $customers = $this->need('getCustomers', fn () => $fina->customers()->all());
        $row = $this->firstRow($customers, 'contragents');
        $this->assertNotNull($row);
        $this->assertDocumentedKeys('getCustomers', $row, ['id', 'group_id', 'code', 'name', 'address', 'phone', 'email', 'is_company', 'is_resident', 'vat_type', 'cons_period', 'add_fields']);

        $this->assertCollection('getVendors', $this->probe('getVendors', fn () => $fina->vendors()->all()), 'contragents', ['id', 'group_id', 'code', 'name', 'address', 'phone', 'email', 'is_company', 'is_resident', 'vat_type', 'cons_period', 'add_fields']);
        $this->assertCollection('getCustomerGroups', $this->probe('getCustomerGroups', fn () => $fina->customers()->groups()), 'groups', ['id', 'parent_id', 'path', 'name']);
        $this->assertCollection('getVendorGroups', $this->probe('getVendorGroups', fn () => $fina->vendors()->groups()), 'groups', ['id', 'parent_id', 'path', 'name']);
        $this->assertCollection('getCustomerAddresses', $this->probe('getCustomerAddresses', fn () => $fina->customers()->addresses()), 'addresses', ['id', 'contragent_id', 'address']);
        $this->assertCollection('getVendorAddresses', $this->probe('getVendorAddresses', fn () => $fina->vendors()->addresses()), 'addresses', ['id', 'contragent_id', 'address']);
        $this->assertIsArray($this->probe('getCustomerAgreements', fn () => $fina->customers()->agreements()) ?? []);
        $this->assertIsArray($this->probe('getContragentSubAccountFields', fn () => $fina->customers()->subAccountFields()) ?? []);
        $this->assertIsArray($this->probe('getCustomerSubAccounts', fn () => $fina->customers()->subAccounts()) ?? []);

        if (($row['code'] ?? '') !== '') {
            $byCode = $this->probe('getCustomersByCode', fn () => $fina->customers()->getByCode((string) $row['code']));
            if ($byCode !== null) {
                $this->assertSame((int) $row['id'], (int) $this->firstRow($byCode, 'contragents')['id']);
            }
        }
    }

    // ------------------------------------------------------------------
    // Reporting journals: documented keys must all be present
    // ------------------------------------------------------------------

    public function test_realizes_journal(): void
    {
        [$from, $to] = $this->window(7);
        $dto = $this->need('getRealizesJournal', fn () => $this->fina()->reporting()->realizesJournalTyped($from, $to));
        $this->assertNotEmpty($dto->journals, 'no sales in the last 7 days?');

        $this->assertDocumentedKeys('getRealizesJournal', $dto->journals[0]->raw, ['id', 'version', 'date', 'doc_num', 'waybill_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'customer_id', 'store_id', 'pay_type']);

        foreach ($dto->journals as $row) {
            $this->assertGreaterThan(0, $row->id);
            $this->assertNotSame('', $row->version);
            $this->assertContains($row->payType, [0, 1, 2, 3, 4, 5, 6]);
        }

        $chunked = $this->fina()->reporting()->realizesJournalChunkedTyped($from, $to, 3);
        $this->assertCount(count($dto->journals), $chunked->journals, 'chunked realizes must equal the single call');
    }

    public function test_moves_journal(): void
    {
        [$from, $to] = $this->window(60);
        $dto = $this->need('getMovesJournal', fn () => $this->fina()->reporting()->movesJournalTyped($from, $to));
        if ($dto->journals === []) {
            $this->markTestIncomplete('no moves in the last 60 days');
        }

        $this->assertDocumentedKeys('getMovesJournal', $dto->journals[0]->raw, ['id', 'version', 'date', 'doc_num', 'waybill_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'store_from_id', 'store_to_id']);
        $this->assertGreaterThan(0, $dto->journals[0]->storeFromId);
        $this->assertSame($dto->toArray()['journals'][0]['id'], $this->fina()->journals()->moves($from, $to)['journals'][0]['id']);
    }

    public function test_entries_journal_lines_share_operation_ids(): void
    {
        [$from, $to] = $this->window(1);
        $dto = $this->need('getEntriesJournal', fn () => $this->fina()->reporting()->entriesJournalTyped($from, $to));
        $this->assertNotEmpty($dto->journals, 'no entries in the last day?');

        $this->assertDocumentedKeys('getEntriesJournal', $dto->journals[0]->raw, ['id', 'version', 'date', 'doc_num', 'waybill_num', 'doc_type', 'purpose', 'amount', 'amount_currency', 'currency', 'staff_id', 'debit_acc', 'debit_comment', 'debit_quantity', 'credit_acc', 'credit_comment', 'credit_quantity', 'project_id']);

        $ids = array_map(static fn ($r) => $r->id, $dto->journals);
        $this->assertGreaterThan(count(array_unique($ids)), count($ids), 'entries journal is one row per line, ids must repeat');

        $chunked = $this->fina()->reporting()->entriesJournalChunkedTyped($from, $to, 1);
        $this->assertCount(count($dto->journals), $chunked->journals, 'chunked fetch must not drop lines');
    }

    public function test_other_journals_and_reports(): void
    {
        $rep = $this->fina()->reporting();
        [$from, $to] = $this->window(30);

        $checks = [
            'getCustomersMoneyJournal' => [fn () => $rep->customersMoneyJournal($from, $to), 'journals', ['id', 'version', 'date', 'doc_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'customer_id', 'pay_type', 'pay_type_id']],
            'getVendorsMoneyJournal' => [fn () => $rep->vendorsMoneyJournal($from, $to), 'journals', ['id', 'version', 'date', 'doc_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'vendor_id', 'pay_type', 'pay_type_id']],
            'getCustomersOrderJournal' => [fn () => $rep->customersOrderJournal($from, $to), 'journals', ['id', 'version', 'date', 'doc_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'customer_id', 'store_id', 'order_status', 'pay_type']],
            'getCustomersReturnJournal' => [fn () => $rep->customersReturnJournal($from, $to), 'journals', ['id', 'version', 'date', 'doc_num', 'waybill_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'customer_id', 'store_id', 'pay_type']],
            'getDocProvidedServicesJournal' => [fn () => $rep->providedServicesJournal($from, $to), 'journals', ['id', 'version', 'date', 'doc_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'customer_id', 'f_status', 'pay_type']],
            'getDocReceivedServicesJournal' => [fn () => $rep->receivedServicesJournal($from, $to), 'journals', ['id', 'version', 'date', 'doc_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'currency', 'vendor_id', 'pay_type']],
            'getProductionsJournal' => [fn () => $rep->productionsJournal($from, $to), 'journals', ['id', 'version', 'date', 'doc_num', 'doc_type', 'purpose', 'amount', 'store_id', 'production_type']],
            'getDiscountCardsJournal' => [fn () => $rep->discountCardsJournal($from, $to), 'journals', ['id', 'version', 'date', 'doc_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'customer', 'store_id', 'card_code', 'discount_id', 'status']],
            'getAutoServicesOutJournal' => [fn () => $rep->autoServicesOutJournal($from, $to), 'journals', ['id', 'version', 'date', 'in_date', 'doc_num', 'waybill_num', 'doc_type', 'purpose', 'amount', 'staff_id', 'customer_id', 'pay_type']],
            'getCustomersCycleReport' => [fn () => $rep->customersCycleReport($from, $to), 'reports', ['id', 'start_val', 'in_val', 'out_val', 'end_val']],
            'getVendorsCycleReport' => [fn () => $rep->vendorsCycleReport($from, $to), 'reports', ['id', 'start_val', 'in_val', 'out_val', 'end_val']],
            'getProductsLastInReport' => [fn () => $rep->productsLastInReport($from, $to), 'reports', ['id', 'quantity', 'price', 'vendor_id', 'currency', 'rate', 'waybill_date', 'waybill_num', 'purpose']],
            'getProductsInReturnReport' => [fn () => $rep->productsInReturnReport($from, $to), 'reports', ['date', 'doc_num', 'doc_type', 'vendor_id', 'store_id', 'product_id', 'unit_id', 'quantity', 'amount', 'vat']],
            'getCafeOrderDetailedReport' => [fn () => $rep->cafeOrderDetailedReport($from, $to), 'reports', ['date', 'doc_num', 'store_id', 'status_id', 'product_id', 'group_id', 'unit_id', 'quantity', 'amount']],
        ];

        foreach ($checks as $name => [$resolve, $root, $keys]) {
            $this->assertCollection($name, $this->probe($name, $resolve), $root, $keys);
        }
        $this->addToAssertionCount(1);
    }

    public function test_products_last_in_and_in_return_typed_rows_are_populated(): void
    {
        [$from, $to] = $this->window(90);
        $rep = $this->fina()->reporting();

        $lastIn = $this->need('getProductsLastInReport', fn () => $rep->productsLastInReportTyped($from, $to));
        if ($lastIn->reports === []) {
            $this->markTestIncomplete('no purchases in the last 90 days');
        }
        $row = $lastIn->reports[0];
        $this->assertGreaterThan(0, $row->id);
        $this->assertGreaterThan(0, $row->vendorId);
        $this->assertNotSame('', $row->waybillDate);
        $this->assertNotSame('', $row->currency);

        $inReturn = $this->need('getProductsInReturnReport', fn () => $rep->productsInReturnReportTyped($from, $to));
        $this->assertNotEmpty($inReturn->reports);
        $line = $inReturn->reports[0];
        $this->assertGreaterThan(0, $line->productId);
        $this->assertGreaterThan(0, $line->vendorId);
        $this->assertContains($line->docType, [16, 32]);
        $this->assertNotSame('', $line->date);
    }

    // ------------------------------------------------------------------
    // Typed documents against real sales
    // ------------------------------------------------------------------

    /** @return array{0: object, 1: int}|null */
    private function firstVerifiedDoc(int $docType, callable $fetch): ?array
    {
        $tried = 0;
        foreach ((array) ($this->recentSales()[$docType] ?? []) as $id) {
            if ($tried++ >= 5) {
                break;
            }
            usleep(200_000);
            try {
                return [$fetch($id), $id];
            } catch (FinaHttpException $e) {
                if ($e->status !== 400) {
                    throw $e;
                }
            }
        }

        return null;
    }

    public function test_retail_document_typed(): void
    {
        if (! isset($this->recentSales()[23])) {
            $this->markTestIncomplete('no retail (type 23) sale in the last 14 days');
        }

        $found = $this->firstVerifiedDoc(23, fn (int $id) => $this->fina()->documents()->getProductOutSingleTyped($id));
        if ($found === null) {
            $this->markTestIncomplete('none of the 5 newest retail docs is verified yet (400)');
        }
        [$doc, $id] = $found;
        self::$available[] = 'getDocProductOutSingle';

        $this->assertSame($id, $doc->id);
        $this->assertDocumentedKeys('getDocProductOutSingle', $doc->raw, ['id', 'date', 'num_pfx', 'num', 'purpose', 'amount', 'currency', 'rate', 'store', 'user', 'staff', 'project', 'is_vat', 'make_entry', 'pay_type', 'add_fields', 'products']);
        $this->assertNotEmpty($doc->products);
        $this->assertDocumentedKeys('getDocProductOutSingle.products', $doc->products[0]->raw, ['id', 'sub_id', 'price', 'quantity']);
        $sum = array_sum(array_map(static fn ($l) => (float) $l->price * $l->quantity, $doc->products));
        $this->assertEqualsWithDelta($doc->amount, $sum, 0.05, 'lines must add up to the document amount');
        $this->assertContains($doc->payType, [0, 1, 6]);
    }

    public function test_sale_document_typed(): void
    {
        if (! isset($this->recentSales()[21])) {
            $this->markTestIncomplete('no type-21 sale in the last 14 days');
        }

        $found = $this->firstVerifiedDoc(21, fn (int $id) => $this->fina()->documents()->getProductOutTyped($id));
        if ($found === null) {
            $this->markTestIncomplete('none of the 5 newest type-21 docs is verified yet (400)');
        }
        [$doc, $id] = $found;
        self::$available[] = 'getDocProductOut';

        $this->assertSame($id, $doc->id);
        $this->assertDocumentedKeys('getDocProductOut', $doc->raw, ['id', 'date', 'num_pfx', 'num', 'waybill_num', 'purpose', 'amount', 'currency', 'rate', 'store', 'customer', 'user', 'staff', 'project', 'is_vat', 'make_entry', 'pay_type', 'price_type', 'w_type', 't_type', 't_payer', 'w_cost', 'foreign', 'drv_name', 'tr_start', 'tr_end', 'driver_id', 'car_num', 'tr_text', 'sender', 'reciever', 'comment', 'overlap_type', 'overlap_amount', 'check_status', 'add_fields', 'products', 'services']);
        $this->assertNotEmpty($doc->products);
        $sum = array_sum(array_map(static fn ($l) => (float) $l->price * $l->quantity, $doc->products))
            + array_sum(array_map(static fn ($s) => $s->price * $s->quantity, $doc->services));
        $this->assertEqualsWithDelta($doc->amount, $sum, 0.05, 'lines must add up to the document amount');
        fwrite(STDERR, sprintf("\ngetDocProductOut %d: check_status=%s make_entry=%s\n", $id, var_export($doc->checkStatus, true), var_export($doc->makeEntry, true)));
    }

    public function test_move_document_typed(): void
    {
        [$from, $to] = $this->window(60);
        $moves = $this->need('getMovesJournal', fn () => $this->fina()->reporting()->movesJournalTyped($from, $to))->journals;
        if ($moves === []) {
            $this->markTestIncomplete('no move in the last 60 days');
        }
        $id = $moves[0]->id;

        $doc = $this->need('getDocProductMove', fn () => $this->fina()->documents()->getProductMoveTyped($id));
        $this->assertSame($id, $doc->id);
        $this->assertDocumentedKeys('getDocProductMove', $doc->raw, ['id', 'date', 'num_pfx', 'num', 'waybill_num', 'purpose', 'amount', 'store_from', 'store_to', 'user', 'staff', 'make_entry', 't_type', 't_payer', 'w_cost', 'foreign', 'drv_name', 'tr_start', 'tr_end', 'driver_id', 'car_num', 'tr_text', 'add_fields', 'products']);
        $this->assertNotEmpty($doc->products);
        $this->assertDocumentedKeys('getDocProductMove.products', $doc->products[0]->raw, ['id', 'sub_id', 'self_cost', 'quantity']);
        $this->assertNull($doc->products[0]->price);
        $this->assertSame($moves[0]->storeFromId, $doc->storeFrom);
        $this->assertSame($moves[0]->storeToId, $doc->storeTo);
    }

    public function test_write_guard_blocks_save_endpoints(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to send a write request');

        // Never reaches FINA: the middleware throws before the request is sent.
        $this->fina()->documents()->saveProductOut(['id' => 0]);
    }
}
