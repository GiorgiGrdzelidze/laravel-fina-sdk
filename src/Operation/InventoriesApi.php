<?php

declare(strict_types=1);

/**
 * Operation API client for fixed assets (ძირითადი საშუალებები).
 */

namespace Fina\Sdk\Laravel\Operation;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Operation\Dto\AdditionalFieldDescriptorDto;

/**
 * Fixed-asset catalog and rests. Mirrors the product rest family; groups are
 * under {@see ReferenceApi::inventoryGroups()}.
 */
final class InventoriesApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'operation');
    }

    /**
     * GET api/operation/getInventories
     * Response: { "inventories": [{id, group_id, unit_id, code, name, comment, in_date,
     *   amortization_type (0 residual, 1 linear), service_term, liquidation_cost, add_fields}], "ex" }
     */
    public function all(): array
    {
        return $this->get('getInventories', [], 'operation.getInventories returned ex');
    }

    /**
     * GET api/operation/getInventoryAdditionalFields
     *
     * @return AdditionalFieldDescriptorDto[]
     */
    public function additionalFields(): array
    {
        $data = $this->get('getInventoryAdditionalFields', [], 'operation.getInventoryAdditionalFields returned ex');

        return array_map(
            static fn ($f) => AdditionalFieldDescriptorDto::fromArray((array) $f),
            (array) ($data['fields'] ?? [])
        );
    }

    /**
     * GET api/operation/getInventoriesRest
     * Response: { "rest": [{id, store, rest, reserve}], "ex" }
     */
    public function rest(): array
    {
        return $this->get('getInventoriesRest', [], 'operation.getInventoriesRest returned ex');
    }

    /**
     * POST api/operation/getInventoriesRestArray
     * Request body: { "prods": int[] }
     */
    public function restByIds(array $inventoryIds): array
    {
        return $this->post(
            'getInventoriesRestArray',
            ['prods' => array_values($inventoryIds)],
            'operation.getInventoriesRestArray returned ex'
        );
    }

    /**
     * GET api/operation/getInventoriesRestByStore/{store}
     * Response: { "store_rest": [{id, rest}], "ex" }
     */
    public function restByStore(int $store): array
    {
        return $this->get("getInventoriesRestByStore/{$store}", [], 'operation.getInventoriesRestByStore returned ex');
    }

    /**
     * POST api/operation/getInventoriesRestAdvance
     * Request body: { "prods": int[], "store": int (0 = all stores), "price": int (price type id, 3 = retail) }
     * Response: { "rest_info": [{id, rest, price}], "ex" }
     */
    public function restAdvance(array $inventoryIds, int $store = 0, int $priceTypeId = 3): array
    {
        return $this->post('getInventoriesRestAdvance', [
            'prods' => array_values($inventoryIds),
            'store' => $store,
            'price' => $priceTypeId,
        ], 'operation.getInventoriesRestAdvance returned ex');
    }
}
