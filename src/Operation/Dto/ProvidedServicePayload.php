<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocProvidedService.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Support\FinaDate;

final class ProvidedServicePayload implements ValidatesPayload
{
    /**
     * @param  AddField[]  $addFields
     * @param  ServiceLine[]  $services
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
        public readonly int $overlapType = 0,
        public readonly float $overlapAmount = 0.0,

        /** @var AddField[] */
        public readonly array $addFields = [],
        /** @var ServiceLine[] */
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
            'overlap_type' => $this->overlapType,
            'overlap_amount' => $this->overlapAmount,

            'add_fields' => array_map(fn (AddField $f) => $f->toArray(), $this->addFields),
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
            'currency' => ['required', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'gt:0'],

            'store' => ['required', 'integer', 'min:1'],
            'user' => ['required', 'integer', 'min:1'],
            'staff' => ['required', 'integer', 'min:0'],
            'project' => ['required', 'integer', 'min:0'],
            'customer' => ['required', 'integer', 'min:1'],

            'is_vat' => ['required', 'boolean'],
            'make_entry' => ['required', 'boolean'],
            'pay_type' => ['required', 'integer', 'min:0'],

            'services' => ['required', 'array', 'min:1'],
            'services.*.id' => ['required', 'integer', 'min:1'],
            'services.*.quantity' => ['required', 'numeric', 'gt:0'],
            'services.*.price' => ['required', 'numeric', 'gte:0'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'services.*.id' => 'service id',
            'services.*.quantity' => 'service quantity',
            'services.*.price' => 'service price',
        ];
    }
}
