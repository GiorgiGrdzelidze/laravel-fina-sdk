<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for fulfillmentCustomerOrder (10.0.20261009).
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\DocumentsApi;

/**
 * Turns a customer order into a sale; FINA answers with the new sale's `id`.
 *
 * @see DocumentsApi::fulfillCustomerOrder()
 */
final class CustomerOrderFulfillmentPayload implements ValidatesPayload
{
    /**
     * @param  int  $orderId  ID of the customer order operation (sent as `id`).
     */
    public function __construct(
        public readonly int $orderId,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->orderId,
        ];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'id' => 'customer order id',
        ];
    }
}
