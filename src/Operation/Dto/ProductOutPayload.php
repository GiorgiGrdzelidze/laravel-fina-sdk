<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocProductOut.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Support\FinaDate;

final class ProductOutPayload implements ValidatesPayload
{
    /**
     * @param  ProductLine[]  $products
     * @param  ServiceLine[]  $services
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
        public readonly int $customer,
        public readonly bool $isVat,
        public readonly bool $makeEntry,
        public readonly int $payType,
        public readonly int $wType,
        public readonly int $tType,
        public readonly int $tPayer,
        public readonly float $wCost,
        public readonly bool $foreign,

        // transport + extra fields (added in v6.0: sender/reciever/comment)
        public readonly ?string $drvName = null,
        public readonly ?string $trStart = null,
        public readonly ?string $trEnd = null,
        public readonly ?string $driverId = null,
        public readonly ?string $carNum = null,
        public readonly ?string $trText = null,
        public readonly ?string $sender = null,
        public readonly ?string $reciever = null,
        public readonly ?string $comment = null,

        public readonly int $overlapType = 0,
        public readonly float $overlapAmount = 0.0,

        /** @var AddField[] */
        public readonly array $addFields = [],
        /** @var ProductLine[] */
        public readonly array $products = [],
        /** @var ServiceLine[] */
        public readonly array $services = [],

        // price type id the document is priced by (FINA default 3 = retail)
        public readonly int $priceType = 3,
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
            'price_type' => $this->priceType,
            'w_type' => $this->wType,
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
            'sender' => $this->sender,
            'reciever' => $this->reciever,
            'comment' => $this->comment,

            'overlap_type' => $this->overlapType,
            'overlap_amount' => $this->overlapAmount,

            'add_fields' => array_map(fn (AddField $f) => $f->toArray(), $this->addFields),
            'products' => array_map(fn (ProductLine $p) => $p->toArray(), $this->products),
            'services' => array_map(fn (ServiceLine $s) => $s->toArray(), $this->services),
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'string'],
            'currency' => ['required', 'string', 'max:10'],
            'rate' => ['required', 'numeric'],
            'store' => ['required', 'integer', 'min:1'],
            'user' => ['required', 'integer', 'min:1'],
            'customer' => ['required', 'integer', 'min:1'],
            'price_type' => ['required', 'integer', 'min:1'],
            // FINA refuses anything else even on a sale without a waybill (HTTP 400)
            'w_type' => ['required', 'integer', 'in:2,3'],
            't_type' => ['required', 'integer', 'in:1,2,3,4,6,7'],
            't_payer' => ['required', 'integer', 'in:1,2'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'min:1'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],
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
