<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocProductMove.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Support\FinaDate;

final class ProductMovePayload implements ValidatesPayload
{
    /**
     * @param  AddField[]  $addFields
     * @param  ProductLine[]  $products
     */
    public function __construct(
        public readonly int $id,
        public readonly DateTimeInterface $date,
        public readonly string $numPrefix,
        public readonly int $num,
        public readonly string $purpose,
        public readonly int $storeFrom,
        public readonly int $storeTo,
        public readonly int $user,
        public readonly int $staff,
        public readonly bool $makeEntry,
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

        /** @var AddField[] */
        public readonly array $addFields = [],
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
            'store_from' => $this->storeFrom,
            'store_to' => $this->storeTo,
            'user' => $this->user,
            'staff' => $this->staff,
            'make_entry' => $this->makeEntry,
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

            'add_fields' => array_map(fn (AddField $f) => $f->toArray(), $this->addFields),
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

            'store_from' => ['required', 'integer', 'min:1'],
            'store_to' => ['required', 'integer', 'min:1', 'different:store_from'],
            'user' => ['required', 'integer', 'min:1'],
            'staff' => ['required', 'integer', 'min:0'],

            'make_entry' => ['required', 'boolean'],
            't_type' => ['required', 'integer', 'min:0'],
            't_payer' => ['required', 'integer', 'min:0'],
            'w_cost' => ['required', 'numeric', 'gte:0'],
            'foreign' => ['required', 'boolean'],

            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'min:1'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],
            'products.*.sub_id' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'store_from' => 'store from',
            'store_to' => 'store to',
            'products.*.id' => 'product id',
            'products.*.quantity' => 'product quantity',
        ];
    }
}
