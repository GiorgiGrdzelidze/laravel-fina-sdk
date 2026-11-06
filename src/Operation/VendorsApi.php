<?php

declare(strict_types=1);

/**
 * Operation API client for vendor-related endpoints.
 */

namespace Fina\Sdk\Laravel\Operation;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Contracts\ArrayPayload;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Operation\Dto\ContragentSubAccountDto;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Support\PayloadValidator;

/**
 * Provides methods for retrieving vendor data from the FINA Operation API.
 */
final class VendorsApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'operation');
    }

    /**
     * getVendorsByCode - Vendors by identification code
     * GET api/operation/getVendorsByCode/{code}
     */
    public function getByCode(string $code): array
    {
        return $this->get('getVendorsByCode/'.rawurlencode($code), [], 'operation.getVendorsByCode returned ex');
    }

    /**
     * getVendors - Vendors list
     * GET api/operation/getVendors
     */
    public function all(): array
    {
        return $this->get('getVendors', [], 'operation.getVendors returned ex');
    }

    /**
     * getVendorGroups - Vendor groups
     * GET api/operation/getVendorGroups
     */
    public function groups(): array
    {
        return $this->get('getVendorGroups', [], 'operation.getVendorGroups returned ex');
    }

    /**
     * getVendorAddresses - All vendor addresses
     * GET api/operation/getVendorAddresses
     *
     * Returns all addresses with `contragent_id` identifying the vendor.
     */
    public function addresses(): array
    {
        return $this->get('getVendorAddresses', [], 'operation.getVendorAddresses returned ex');
    }

    /**
     * getVendorAdditionalFields - Vendor additional fields metadata
     * GET api/operation/getVendorAdditionalFields
     */
    public function additionalFields(): array
    {
        return $this->get('getVendorAdditionalFields', [], 'operation.getVendorAdditionalFields returned ex');
    }

    /**
     * getVendorSubAccounts - Vendor sub-accounts
     * GET api/operation/getVendorSubAccounts
     *
     * @return ContragentSubAccountDto[]
     */
    public function subAccounts(): array
    {
        $data = $this->get('getVendorSubAccounts', [], 'operation.getVendorSubAccounts returned ex');

        return array_map(
            fn ($s) => ContragentSubAccountDto::fromArray((array) $s),
            (array) ($data['contragent_sub_accounts'] ?? [])
        );
    }

    /**
     * POST api/operation/saveVendor — insert (id = 0) or update a vendor.
     * Response: { "id", "ex" }.
     */
    public function save(ArrayPayload|array $payload): SaveDocResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
        }

        $body = $payload instanceof ArrayPayload ? $payload->toArray() : $payload;

        return SaveDocResponse::fromArray(
            $this->post('saveVendor', $body, 'operation.saveVendor returned ex')
        );
    }
}
