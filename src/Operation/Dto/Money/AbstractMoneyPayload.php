<?php

declare(strict_types=1);

/**
 * Abstract base for money document payloads (customer/vendor money in/out/return).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Money;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\Dto\AddField;
use Fina\Sdk\Laravel\Support\FinaDate;

abstract class AbstractMoneyPayload implements ValidatesPayload
{
    /**
     * @param  AddField[]  $addFields
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

        public readonly int $payType,
        public readonly int $payTypeId,
        public readonly int $refId,
        public readonly bool $makeEntry,

        public readonly array $addFields = [],
    ) {}

    /**
     * child must provide entity key + entity id
     * e.g. ['customer' => 10] or ['vendor' => 5]
     */
    abstract protected function entityPart(): array;

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

            ...$this->entityPart(),

            'pay_type' => $this->payType,
            'pay_type_id' => $this->payTypeId,
            'ref_id' => $this->refId,
            'make_entry' => $this->makeEntry,

            'add_fields' => array_map(fn (AddField $f) => $f->toArray(), $this->addFields),
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return array_merge([
            'id' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'string'],
            'num_pfx' => ['nullable', 'string', 'max:20'],
            'num' => ['required', 'integer', 'min:0'],
            'purpose' => ['required', 'string', 'max:750'],

            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'gt:0'],

            'store' => ['required', 'integer', 'min:1'],
            'user' => ['required', 'integer', 'min:1'],
            'staff' => ['required', 'integer', 'min:0'],
            'project' => ['required', 'integer', 'min:0'],

            'pay_type' => ['required', 'integer', 'min:0'],
            'pay_type_id' => ['required', 'integer', 'min:0'],
            'ref_id' => ['required', 'integer', 'min:0'],
            'make_entry' => ['required', 'boolean'],

            'add_fields' => ['present', 'array'],
            'add_fields.*.field' => ['required_with:add_fields', 'string'],
            'add_fields.*.value' => ['required_with:add_fields', 'string'],
        ], $this->entityRules());
    }

    /**
     * child must add rules for entity key (customer/vendor)
     */
    abstract protected function entityRules(): array;

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
