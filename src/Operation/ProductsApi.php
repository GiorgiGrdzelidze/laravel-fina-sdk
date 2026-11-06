<?php

declare(strict_types=1);

/**
 * Operation API client for product-related endpoints.
 */

namespace Fina\Sdk\Laravel\Operation;

use DateTimeInterface;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Contracts\ArrayPayload;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Operation\Dto\AdditionalFieldDescriptorDto;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Support\FinaDate;
use Fina\Sdk\Laravel\Support\PayloadValidator;

/**
 * Provides methods for retrieving product data, prices, units, characteristics,
 * images, barcodes, and stock from the FINA Operation API.
 */
final class ProductsApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'operation');
    }

    /**
     * GET api/operation/getProductGroups
     */
    public function groups(): array
    {
        return $this->get('getProductGroups', [], 'operation.getProductGroups returned ex');
    }

    /**
     * GET api/operation/getWebProductGroups
     */
    public function webGroups(): array
    {
        return $this->get('getWebProductGroups', [], 'operation.getWebProductGroups returned ex');
    }

    /**
     * GET api/operation/getProducts
     */
    public function all(): array
    {
        return $this->get('getProducts', [], 'operation.getProducts returned ex');
    }

    /**
     * POST api/operation/getProductsArray
     * Request body: int[] (product ids)
     */
    public function byIds(array $productIds): array
    {
        // API expects a raw int[] array in JSON
        return $this->post('getProductsArray', array_values($productIds), 'operation.getProductsArray returned ex');
    }

    /**
     * GET api/operation/getProductsAfter/{after_date}
     */
    public function after(DateTimeInterface $afterDate): array
    {
        $after = rawurlencode(FinaDate::toFina($afterDate));

        return $this->get("getProductsAfter/{$after}", [], 'operation.getProductsAfter returned ex');
    }

    /**
     * POST api/operation/getProductsImageArray
     * Request body: int[] (recommended max ~20)
     */
    public function imagesByProductIds(array $productIds): array
    {
        return $this->post('getProductsImageArray', array_values($productIds), 'operation.getProductsImageArray returned ex');
    }

    /**
     * POST api/operation/getProductsBarcodeArray
     * Request body: int[] (product ids)
     */
    public function barcodesByProductIds(array $productIds): array
    {
        return $this->post('getProductsBarcodeArray', array_values($productIds), 'operation.getProductsBarcodeArray returned ex');
    }

    /**
     * GET api/operation/getProductPrices
     */
    public function prices(): array
    {
        return $this->get('getProductPrices', [], 'operation.getProductPrices returned ex');
    }

    /**
     * GET api/operation/getProductPricesAfter/{after_date}
     */
    public function pricesAfter(DateTimeInterface $afterDate): array
    {
        $after = rawurlencode(FinaDate::toFina($afterDate));

        return $this->get("getProductPricesAfter/{$after}", [], 'operation.getProductPricesAfter returned ex');
    }

    /**
     * GET api/operation/getProductUnits
     */
    public function units(): array
    {
        return $this->get('getProductUnits', [], 'operation.getProductUnits returned ex');
    }

    /**
     * GET api/operation/getCharacteristics
     */
    public function characteristics(): array
    {
        return $this->get('getCharacteristics', [], 'operation.getCharacteristics returned ex');
    }

    /**
     * GET api/operation/getProductsRest
     * Current rest of every product for every store.
     * Response: { "rest": [{ "id": int, "store": int, "rest": decimal, "reserve": decimal }], "ex": null }
     */
    public function rest(): array
    {
        return $this->get('getProductsRest', [], 'operation.getProductsRest returned ex');
    }

    /**
     * POST api/operation/getProductsRestArray
     * Request body: { "prods": int[] }
     */
    public function restByProductIds(array $productIds): array
    {
        return $this->post(
            'getProductsRestArray',
            ['prods' => array_values($productIds)],
            'operation.getProductsRestArray returned ex'
        );
    }

    /**
     * GET api/operation/getProductsRestAfter/{after_date}
     * Returns product rests changed after the given date.
     */
    public function restAfter(DateTimeInterface $afterDate): array
    {
        $after = rawurlencode(FinaDate::toFina($afterDate));

        return $this->get("getProductsRestAfter/{$after}", [], 'operation.getProductsRestAfter returned ex');
    }

    /**
     * POST api/operation/getProductsRestSummary
     * Request body: { "prods": int[], "stores": int[], "date": "yyyy-MM-ddTHH:mm:ss" }
     */
    public function restSummary(array $prods, array $stores, DateTimeInterface $date): array
    {
        return $this->post('getProductsRestSummary', [
            'prods' => array_values($prods),
            'stores' => array_values($stores),
            'date' => FinaDate::toFina($date),
        ], 'operation.getProductsRestSummary returned ex');
    }

    /**
     * GET api/operation/getProductsRestByStoreAfter/{store}/{after_date}
     * Returns product rests changed after the given date for a specific store.
     */
    public function restByStoreAfter(int $store, DateTimeInterface $afterDate): array
    {
        $after = rawurlencode(FinaDate::toFina($afterDate));

        return $this->get("getProductsRestByStoreAfter/{$store}/{$after}", [], 'operation.getProductsRestByStoreAfter returned ex');
    }

    /**
     * GET api/operation/getProvidedServicePrices
     * Returns provided service prices (same structure as getProductPrices).
     */
    public function providedServicePrices(): array
    {
        return $this->get('getProvidedServicePrices', [], 'operation.getProvidedServicePrices returned ex');
    }

    /**
     * GET api/operation/getProductsRestByStore/{store}
     * Response: { "store_rest": [{ "id": int, "rest": decimal }], "ex": null }
     */
    public function restByStore(int $store): array
    {
        return $this->get("getProductsRestByStore/{$store}", [], 'operation.getProductsRestByStore returned ex');
    }

    /**
     * POST api/operation/getProductsRestAdvance
     * Request body: { "prods": int[], "store": int (0 = all stores), "price": int (price type id, 3 = retail) }
     * Response: { "rest_info": [{ "id": int, "rest": decimal, "price": double }], "ex": null }
     */
    public function restAdvance(array $productIds, int $store = 0, int $priceTypeId = 3): array
    {
        return $this->post('getProductsRestAdvance', [
            'prods' => array_values($productIds),
            'store' => $store,
            'price' => $priceTypeId,
        ], 'operation.getProductsRestAdvance returned ex');
    }

    /**
     * GET api/operation/getSubProductsRest
     * Response: { "rest": [{ "id": int, "sub_id": int, "store": int, "rest": decimal, "reserve": decimal }], "ex": null }
     */
    public function subRest(): array
    {
        return $this->get('getSubProductsRest', [], 'operation.getSubProductsRest returned ex');
    }

    /**
     * POST api/operation/getProductsSelfCost
     * Request body: { "prods": int[], "date": "yyyy-MM-ddTHH:mm:ss" }
     * Response: { "cost_info": [{ "id": int, "cost": double }], "ex": null } — AVERAGE cost at the given date.
     */
    public function selfCost(array $productIds, DateTimeInterface $date): array
    {
        return $this->post('getProductsSelfCost', [
            'prods' => array_values($productIds),
            'date' => FinaDate::toFina($date),
        ], 'operation.getProductsSelfCost returned ex');
    }

    /**
     * POST api/operation/getProductPricesAdvance
     * Request body: { "prods": int[], "price": int (price type id, 3 = retail) }
     * Response: same shape as getProductPrices, limited to one price type.
     */
    public function pricesAdvance(array $productIds, int $priceTypeId = 3): array
    {
        return $this->post('getProductPricesAdvance', [
            'prods' => array_values($productIds),
            'price' => $priceTypeId,
        ], 'operation.getProductPricesAdvance returned ex');
    }

    /**
     * GET api/operation/getProductImages/{product}
     * Response: { "images": [{ "id": int, "image": base64 }], "ex": null }
     */
    public function images(int $productId): array
    {
        return $this->get("getProductImages/{$productId}", [], 'operation.getProductImages returned ex');
    }

    /**
     * GET api/operation/getProductPlaces
     * Response: { "places": [{ "product_id": int, "store_id": int, "place": string }], "ex": null }
     */
    public function places(): array
    {
        return $this->get('getProductPlaces', [], 'operation.getProductPlaces returned ex');
    }

    /**
     * GET api/operation/getCharacteristicValues
     * Response: { "characteristic_values": [{ "product_id", "characteristic_id", "value", "value2", "value3" }], "ex": null }
     */
    public function characteristicValues(): array
    {
        return $this->get('getCharacteristicValues', [], 'operation.getCharacteristicValues returned ex');
    }

    /**
     * POST api/operation/getCharacteristicValuesArray
     * Request body: int[] (product ids)
     */
    public function characteristicValuesByProductIds(array $productIds): array
    {
        return $this->post('getCharacteristicValuesArray', array_values($productIds), 'operation.getCharacteristicValuesArray returned ex');
    }

    /**
     * GET api/operation/getPackedProducts
     * Response: { "pack_info": [{ "product_id": int, "pack_id": int }], "ex": null }
     */
    public function packed(): array
    {
        return $this->get('getPackedProducts', [], 'operation.getPackedProducts returned ex');
    }

    /**
     * GET api/operation/getSubCodeTypes
     * Response: { "types": [{ "name": "sub_column_N", "header": string }], "ex": null }
     */
    public function subCodeTypes(): array
    {
        return $this->get('getSubCodeTypes', [], 'operation.getSubCodeTypes returned ex');
    }

    /**
     * GET api/operation/getProductSubCodes
     * Response: { "sub_codes": [{ "product_id", "sub_id", "barcode", "sub_columns": [{field, value}] }], "ex": null }
     */
    public function subCodes(): array
    {
        return $this->get('getProductSubCodes', [], 'operation.getProductSubCodes returned ex');
    }

    /**
     * GET api/operation/getProductsOnWay
     * Response: { "on_way": [{ "id": int, "date": date, "quantity": decimal }], "ex": null }
     */
    public function onWay(): array
    {
        return $this->get('getProductsOnWay', [], 'operation.getProductsOnWay returned ex');
    }

    /**
     * GET api/operation/getProductAdditionalFields
     *
     * @return AdditionalFieldDescriptorDto[]
     */
    public function additionalFields(): array
    {
        $data = $this->get('getProductAdditionalFields', [], 'operation.getProductAdditionalFields returned ex');

        return array_map(
            static fn ($f) => AdditionalFieldDescriptorDto::fromArray((array) $f),
            (array) ($data['fields'] ?? [])
        );
    }

    /**
     * POST api/operation/saveProduct — insert (id = 0) or update a catalog product.
     * A non-null `barcodes` or `images` array REPLACES the product's existing ones;
     * `prices` only sets the listed price types. Response: { "id", "ex" }.
     */
    public function save(ArrayPayload|array $payload): SaveDocResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
        }

        $body = $payload instanceof ArrayPayload ? $payload->toArray() : $payload;

        return SaveDocResponse::fromArray(
            $this->post('saveProduct', $body, 'operation.saveProduct returned ex')
        );
    }
}
