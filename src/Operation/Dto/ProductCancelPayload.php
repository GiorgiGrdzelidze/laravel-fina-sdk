<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocProductCancel.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Support\FinaDate;

final class ProductCancelPayload implements ValidatesPayload
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
        public readonly int $store,
        public readonly int $user,
        public readonly int $staff,
        public readonly int $project,
        public readonly bool $makeEntry,
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
            'store' => $this->store,
            'user' => $this->user,
            'staff' => $this->staff,
            'project' => $this->project,
            'make_entry' => $this->makeEntry,
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

            'store' => ['required', 'integer', 'min:1'],
            'user' => ['required', 'integer', 'min:1'],
            'staff' => ['required', 'integer', 'min:0'],
            'project' => ['required', 'integer', 'min:0'],
            'make_entry' => ['required', 'boolean'],

            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'min:1'],
            'products.*.sub_id' => ['nullable', 'integer', 'min:0'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],

            // product cancel does not require price/out_id
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
        ];
    }
}
