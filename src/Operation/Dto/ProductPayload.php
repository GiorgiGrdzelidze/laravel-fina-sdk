<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveProduct.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\ProductsApi;

/**
 * Insert (`id` 0) or update a catalog product. A non-null `barcodes` or `images` REPLACES the
 * product's existing ones; non-null `prices` sets the listed price types only. Optional fields left
 * null are not sent (FINA's default group is 11).
 *
 * @see ProductsApi::save()
 */
final class ProductPayload implements ValidatesPayload
{
    /**
     * @param  int  $id  Product ID (0 for new).
     * @param  int|null  $vat  1 taxable, 2 zero-rated, 3 exempt.
     * @param  int|null  $plu  PLU code of a weighed product (> 0).
     * @param  int|null  $unit  Unit ID.
     * @param  ProductPriceLine[]|null  $prices
     * @param  string[]|null  $barcodes
     * @param  string[]|null  $images  Base64 images.
     * @param  AddField[]|null  $addFields
     */
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $nameEng = null,
        public readonly ?string $nameRus = null,
        public readonly ?int $groupId = null,
        public readonly ?string $comment = null,
        public readonly ?int $vat = null,
        public readonly ?int $plu = null,
        public readonly ?int $unit = null,
        public readonly ?float $weight = null,
        public readonly ?array $prices = null,
        public readonly ?array $barcodes = null,
        public readonly ?array $images = null,
        public readonly ?array $addFields = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_eng' => $this->nameEng,
            'name_rus' => $this->nameRus,
            'group_id' => $this->groupId,
            'comment' => $this->comment,
            'vat' => $this->vat,
            'plu' => $this->plu,
            'unit' => $this->unit,
            'weight' => $this->weight,
            'prices' => $this->prices === null
                ? null
                : array_map(fn (ProductPriceLine $p) => $p->toArray(), $this->prices),
            'barcodes' => $this->barcodes === null ? null : array_values($this->barcodes),
            'images' => $this->images === null ? null : array_values($this->images),
            'add_fields' => $this->addFields === null
                ? null
                : array_map(fn (AddField $f) => $f->toArray(), $this->addFields),
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:0'],
            'code' => ['present', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:200'],
            'name_eng' => ['nullable', 'string', 'max:200'],
            'name_rus' => ['nullable', 'string', 'max:200'],
            'group_id' => ['nullable', 'integer', 'min:1'],
            'comment' => ['nullable', 'string', 'max:500'],
            'vat' => ['nullable', 'integer', 'in:1,2,3'],
            'plu' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', 'integer', 'min:1'],
            'weight' => ['nullable', 'numeric', 'gte:0'],
            'prices' => ['nullable', 'array'],
            'prices.*.id' => ['required', 'integer', 'min:1'],
            'prices.*.value' => ['required', 'numeric', 'gte:0'],
            'prices.*.currency' => ['nullable', 'string', 'max:10'],
            'prices.*.discount_value' => ['nullable', 'numeric', 'gte:0'],
            'prices.*.discount_start' => ['nullable', 'string'],
            'prices.*.discount_end' => ['nullable', 'string'],
            'barcodes' => ['nullable', 'array'],
            'barcodes.*' => ['string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'add_fields' => ['nullable', 'array'],
            'add_fields.*.field' => ['required', 'string'],
            'add_fields.*.value' => ['present', 'string'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'prices.*.id' => 'price type id',
            'prices.*.value' => 'price',
        ];
    }
}
