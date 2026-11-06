<?php

declare(strict_types=1);

/**
 * Operation API client for customer-related endpoints.
 */

namespace Fina\Sdk\Laravel\Operation;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Contracts\ArrayPayload;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Operation\Dto\ContragentSubAccountDto;
use Fina\Sdk\Laravel\Operation\Dto\ContragentSubAccountFieldDto;
use Fina\Sdk\Laravel\Operation\Dto\CustomerAgreementDto;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Support\PayloadValidator;

/**
 * Provides methods for retrieving customer data from the FINA Operation API.
 */
final class CustomersApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'operation');
    }

    /**
     * getCustomersByCode - Customers by identification code
     * GET api/operation/getCustomersByCode/{code}
     */
    public function getByCode(string $code): array
    {
        return $this->get('getCustomersByCode/'.rawurlencode($code), [], 'operation.getCustomersByCode returned ex');
    }

    /**
     * getCustomers - Customers list
     * GET api/operation/getCustomers
     */
    public function all(): array
    {
        return $this->get('getCustomers', [], 'operation.getCustomers returned ex');
    }

    /**
     * getCustomerGroups - Customer groups
     * GET api/operation/getCustomerGroups
     */
    public function groups(): array
    {
        return $this->get('getCustomerGroups', [], 'operation.getCustomerGroups returned ex');
    }

    /**
     * getCustomerAddresses - All customer addresses
     * GET api/operation/getCustomerAddresses
     *
     * Returns all addresses with `contragent_id` identifying the customer.
     */
    public function addresses(): array
    {
        return $this->get('getCustomerAddresses', [], 'operation.getCustomerAddresses returned ex');
    }

    /**
     * getCustomerAdditionalFields - Customer additional fields metadata
     * GET api/operation/getCustomerAdditionalFields
     */
    public function additionalFields(): array
    {
        return $this->get('getCustomerAdditionalFields', [], 'operation.getCustomerAdditionalFields returned ex');
    }

    /**
     * getCustomerAgreements - Customer agreements
     * GET api/operation/getCustomerAgreements
     *
     * @return CustomerAgreementDto[]
     */
    public function agreements(): array
    {
        $data = $this->get('getCustomerAgreements', [], 'operation.getCustomerAgreements returned ex');

        return array_map(
            fn ($a) => CustomerAgreementDto::fromArray((array) $a),
            (array) ($data['agreements'] ?? [])
        );
    }

    /**
     * getContragentSubAccountFields - Sub-account field definitions
     * GET api/operation/getContragentSubAccountFields
     *
     * @return ContragentSubAccountFieldDto[]
     */
    public function subAccountFields(): array
    {
        $data = $this->get('getContragentSubAccountFields', [], 'operation.getContragentSubAccountFields returned ex');

        return array_map(
            fn ($f) => ContragentSubAccountFieldDto::fromArray((array) $f),
            (array) ($data['fields'] ?? [])
        );
    }

    /**
     * getCustomerSubAccounts - Customer sub-accounts
     * GET api/operation/getCustomerSubAccounts
     *
     * @return ContragentSubAccountDto[]
     */
    public function subAccounts(): array
    {
        $data = $this->get('getCustomerSubAccounts', [], 'operation.getCustomerSubAccounts returned ex');

        return array_map(
            fn ($s) => ContragentSubAccountDto::fromArray((array) $s),
            (array) ($data['contragent_sub_accounts'] ?? [])
        );
    }

    /**
     * POST api/operation/saveCustomer — insert (id = 0) or update a customer.
     * Response: { "id", "ex" }.
     */
    public function save(ArrayPayload|array $payload): SaveDocResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
        }

        $body = $payload instanceof ArrayPayload ? $payload->toArray() : $payload;

        return SaveDocResponse::fromArray(
            $this->post('saveCustomer', $body, 'operation.saveCustomer returned ex')
        );
    }
}
