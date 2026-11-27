<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocAdvProduction (რთული წარმოება/დაშლა, 10.0.20260625).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Production;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\DocumentsApi;
use Fina\Sdk\Laravel\Support\FinaDate;

/**
 * Complex production or disassembly: `materials` are consumed / taken apart, `products` are made,
 * each with its `cost_percent` share of the materials' cost.
 *
 * @see DocumentsApi::saveAdvProduction()
 */
final class AdvProductionPayload implements ValidatesPayload
{
    /**
     * @param  float  $portion  Quantity (`portion`).
     * @param  AdvProductionLine[]  $materials
     * @param  AdvProductionLine[]  $products
     */
    public function __construct(
        public readonly int $id,
        public readonly DateTimeInterface $date,
        public readonly string $numPrefix,
        public readonly int $num,
        public readonly string $purpose,
        public readonly int $store,
        public readonly float $portion,
        public readonly int $user,
        public readonly bool $makeEntry,
        public readonly array $materials = [],
        public readonly array $products = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date' => FinaDate::toFina($this->date),
            'num_pfx' => $this->numPrefix,
            'num' => $this->num,
            'purpose' => $this->purpose,
            'store' => $this->store,
            'portion' => $this->portion,
            'user' => $this->user,
            'make_entry' => $this->makeEntry,
            'materials' => array_map(fn (AdvProductionLine $l) => $l->toArray(), $this->materials),
            'products' => array_map(fn (AdvProductionLine $l) => $l->toArray(), $this->products),
        ];
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
            'portion' => ['required', 'numeric', 'gt:0'],
            'user' => ['required', 'integer', 'min:1'],
            'make_entry' => ['required', 'boolean'],
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.id' => ['required', 'integer', 'min:1'],
            'materials.*.quantity' => ['required', 'numeric', 'gt:0'],
            'materials.*.cost_percent' => ['prohibited'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'min:1'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],
            'products.*.cost_percent' => ['required', 'numeric', 'gte:0', 'lte:100'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'materials.*.id' => 'material id',
            'materials.*.quantity' => 'material quantity',
            'products.*.id' => 'product id',
            'products.*.quantity' => 'product quantity',
            'products.*.cost_percent' => 'product cost percent',
        ];
    }
}
