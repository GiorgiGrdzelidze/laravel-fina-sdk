<?php

declare(strict_types=1);

/**
 * Operation API client for the provided / received service catalogs.
 */

namespace Fina\Sdk\Laravel\Operation;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Contracts\ArrayPayload;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Operation\Dto\AdditionalFieldDescriptorDto;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Support\PayloadValidator;

/**
 * Service catalogs (გაწეული / მიღებული მომსახურება). Service DOCUMENTS live in
 * {@see DocumentsApi} (saveProvidedService, getReceivedService, ...).
 */
final class ServicesApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'operation');
    }

    /**
     * GET api/operation/getProvidedServices
     * Response: { "services": [{id, group_id, unit_id, code, name, comment, vat, add_fields}], "ex" }
     */
    public function provided(): array
    {
        return $this->get('getProvidedServices', [], 'operation.getProvidedServices returned ex');
    }

    /**
     * GET api/operation/getReceivedServices
     * Response: { "services": [...], "ex" }
     */
    public function received(): array
    {
        return $this->get('getReceivedServices', [], 'operation.getReceivedServices returned ex');
    }

    /**
     * GET api/operation/getProvidedServiceAdditionalFields
     *
     * @return AdditionalFieldDescriptorDto[]
     */
    public function providedAdditionalFields(): array
    {
        $data = $this->get('getProvidedServiceAdditionalFields', [], 'operation.getProvidedServiceAdditionalFields returned ex');

        return array_map(
            static fn ($f) => AdditionalFieldDescriptorDto::fromArray((array) $f),
            (array) ($data['fields'] ?? [])
        );
    }

    /**
     * GET api/operation/getProvidedServicePrices
     * Same shape as getProductPrices.
     */
    public function providedPrices(): array
    {
        return $this->get('getProvidedServicePrices', [], 'operation.getProvidedServicePrices returned ex');
    }

    /**
     * POST api/operation/saveProvidedService — insert (id = 0) or update a
     * provided service catalog entry. Response: { "id", "ex" }.
     */
    public function saveProvided(ArrayPayload|array $payload): SaveDocResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
        }

        $body = $payload instanceof ArrayPayload ? $payload->toArray() : $payload;

        return SaveDocResponse::fromArray(
            $this->post('saveProvidedService', $body, 'operation.saveProvidedService returned ex')
        );
    }
}
