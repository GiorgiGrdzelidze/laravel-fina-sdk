<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocCafeOrder.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Support\FinaDate;

final class CafeOrderPayload implements ValidatesPayload
{
    /**
     * @param  CafeOrderProductLine[]  $products
     * @param  ServiceLine[]  $services
     */
    public function __construct(
        public readonly int $id,
        public readonly DateTimeInterface $date,
        public readonly string $numPrefix,
        public readonly int $num,
        public readonly string $purpose,
        public readonly float $amount,
        public readonly int $store,
        public readonly int $user,
        public readonly int $project,
        public readonly string $customerName = '',
        public readonly string $customerTel = '',
        public readonly string $customerAddress = '',
        public readonly array $products = [],
        public readonly array $services = [],
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'date' => FinaDate::toFina($this->date),
            'num_pfx' => $this->numPrefix,
            'num' => $this->num,
            'purpose' => $this->purpose,
            'amount' => $this->amount,
            'store' => $this->store,
            'user' => $this->user,
            'project' => $this->project,
            'customer_name' => $this->customerName,
            'customer_tel' => $this->customerTel,
            'customer_address' => $this->customerAddress,
            'products' => array_map(fn (CafeOrderProductLine $p) => $p->toArray(), $this->products),
            'services' => array_map(fn (ServiceLine $s) => $s->toArray(), $this->services),
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'string'],
            'num_pfx' => ['nullable', 'string', 'max:20'],
            'num' => ['required', 'integer', 'min:0'],
            'purpose' => ['required', 'string', 'max:750'],
            'amount' => ['required', 'numeric', 'gte:0'],
            'store' => ['required', 'integer', 'min:1'],
            'user' => ['required', 'integer', 'min:1'],
            'project' => ['required', 'integer', 'min:0'],

            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_tel' => ['nullable', 'string', 'max:20'],
            'customer_address' => ['nullable', 'string', 'max:255'],

            // products[] is required per API spec
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'min:1'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],
            'products.*.price' => ['required', 'numeric', 'gte:0'],

            // services may be empty
            'services' => ['present', 'array'],
            // documented for saveDocProvidedService only; `missing` refuses an empty list too
            'services.*.consumed_materials' => ['missing'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'products.*.id' => 'product id',
            'products.*.quantity' => 'product quantity',
            'products.*.price' => 'product price',
        ];
    }
}
