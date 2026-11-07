<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocCustomerReturn.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Support\FinaDate;

final class CustomerReturnPayload implements ValidatesPayload
{
    /**
     * @param  ProductLine[]  $products
     */
    public function __construct(
        public readonly int $id,
        public readonly DateTimeInterface $date,
        public readonly string $numPrefix,
        public readonly int $num,
        public readonly string $purpose,
        public readonly float $amount,
        public readonly string $currency,
        public readonly float $rate,
        public readonly int $store,
        public readonly int $user,
        public readonly int $staff,
        public readonly int $project,
        public readonly int $customer,
        public readonly bool $isVat,
        public readonly bool $makeEntry,
        public readonly int $payType,
        public readonly int $tType,
        public readonly int $tPayer,
        public readonly float $wCost,
        public readonly bool $foreign,

        // transport fields
        public readonly ?string $drvName = null,
        public readonly ?string $trStart = null,
        public readonly ?string $trEnd = null,
        public readonly ?string $driverId = null,
        public readonly ?string $carNum = null,
        public readonly ?string $trText = null,

        /** @var ProductLine[] */
        public readonly array $products = [],
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
            'currency' => $this->currency,
            'rate' => $this->rate,
            'store' => $this->store,
            'user' => $this->user,
            'staff' => $this->staff,
            'project' => $this->project,
            'customer' => $this->customer,
            'is_vat' => $this->isVat,
            'make_entry' => $this->makeEntry,
            'pay_type' => $this->payType,
            't_type' => $this->tType,
            't_payer' => $this->tPayer,
            'w_cost' => $this->wCost,
            'foreign' => $this->foreign,

            'drv_name' => $this->drvName,
            'tr_start' => $this->trStart,
            'tr_end' => $this->trEnd,
            'driver_id' => $this->driverId,
            'car_num' => $this->carNum,
            'tr_text' => $this->trText,

            'products' => array_map(fn (ProductLine $p) => $p->toArray(), $this->products),
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
            'currency' => ['required', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'gt:0'],

            'store' => ['required', 'integer', 'min:1'],
            'user' => ['required', 'integer', 'min:1'],
            'staff' => ['required', 'integer', 'min:0'],
            't_type' => ['required', 'integer', 'in:1,2,3,4,6,7'],
            't_payer' => ['required', 'integer', 'in:1,2'],
            'project' => ['required', 'integer', 'min:0'],
            'customer' => ['required', 'integer', 'min:1'],

            'is_vat' => ['required', 'boolean'],
            'make_entry' => ['required', 'boolean'],
            'pay_type' => ['required', 'integer', 'min:0'],

            'w_cost' => ['required', 'numeric', 'gte:0'],
            'foreign' => ['required', 'boolean'],

            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'min:1'],
            'products.*.sub_id' => ['nullable', 'integer', 'min:0'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],
            'products.*.price' => ['required', 'numeric', 'gte:0'],
            'products.*.out_id' => ['required', 'integer', 'min:0'],
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
            'products.*.out_id' => 'product out_id',
        ];
    }
}
