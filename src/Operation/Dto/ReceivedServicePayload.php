<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocReceivedService (v8.0).
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\DocumentsApi;
use Fina\Sdk\Laravel\Support\FinaDate;

/**
 * Payload for recording a received service document via the FINA API.
 *
 * @see DocumentsApi::saveReceivedService()
 */
final class ReceivedServicePayload implements ValidatesPayload
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
        public readonly int $user,
        public readonly int $project,
        public readonly int $vendor,
        public readonly bool $isVat,
        public readonly bool $makeEntry,
        public readonly int $payType,
        public readonly int $overlapType = 0,
        public readonly float $overlapAmount = 0.0,
        public readonly array $addFields = [],
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
            'user' => $this->user,
            'project' => $this->project,
            'vendor' => $this->vendor,
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
            'user' => ['required', 'integer', 'min:1'],
            'project' => ['required', 'integer', 'min:0'],
            'vendor' => ['required', 'integer', 'min:1'],
            'is_vat' => ['required', 'boolean'],
            'make_entry' => ['required', 'boolean'],
            'pay_type' => ['required', 'integer', 'min:0'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.id' => ['required', 'integer', 'min:1'],
            'services.*.quantity' => ['required', 'numeric', 'gt:0'],
            'services.*.price' => ['required', 'numeric', 'gte:0'],
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
            'services.*.id' => 'service id',
            'services.*.quantity' => 'service quantity',
            'services.*.price' => 'service price',
        ];
    }
}
