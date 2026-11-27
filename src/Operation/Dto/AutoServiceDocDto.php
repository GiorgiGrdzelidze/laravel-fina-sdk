<?php

declare(strict_types=1);

/**
 * Operation DTO: auto-service document from getDocAutoService (v8.0).
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Operation\DocumentsApi;
use Fina\Sdk\Laravel\Operation\Dto\Documents\WaybillStatus;

/**
 * Represents a full auto-service document with transport, waybill, and product/service details.
 * `waybill_status` (10.0.20261106) holds a {@see WaybillStatus} code; null when the FINA build
 * does not send it.
 *
 * @see DocumentsApi::getAutoServiceTyped()
 */
final readonly class AutoServiceDocDto
{
    /**
     * @param  array<int, array<string, mixed>>  $addFields  Additional custom fields.
     * @param  array<int, array<string, mixed>>  $products  Product line items.
     * @param  array<int, array<string, mixed>>  $services  Service line items.
     * @param  array<int, array<string, mixed>>  $subAccounts  Sub-account entries.
     */
    public function __construct(
        public int $id,
        public string $date,
        public string $numPfx,
        public int $num,
        public ?string $waybillNum,
        public string $purpose,
        public float $amount,
        public string $currency,
        public float $rate,
        public int $store,
        public int $customer,
        public int $user,
        public int $staff,
        public int $project,
        public bool $isVat,
        public bool $makeEntry,
        public int $payType,
        public int $wType,
        public int $tType,
        public int $tPayer,
        public float $wCost,
        public bool $foreign,
        public string $drvName,
        public string $trStart,
        public string $trEnd,
        public string $driverId,
        public string $carNum,
        public string $trText,
        public string $sender,
        public string $reciever,
        public string $comment,
        public int $mileage,
        public string $inDate,
        public int $box,
        public int $car,
        public int $overlapType,
        public float $overlapAmount,
        public array $addFields,
        public array $products,
        public array $services,
        public array $subAccounts,
        public ?int $waybillStatus = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Raw API response array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            (string) ($data['date'] ?? ''),
            (string) ($data['num_pfx'] ?? ''),
            (int) ($data['num'] ?? 0),
            $data['waybill_num'] ?? null,
            (string) ($data['purpose'] ?? ''),
            (float) ($data['amount'] ?? 0),
            (string) ($data['currency'] ?? ''),
            (float) ($data['rate'] ?? 0),
            (int) ($data['store'] ?? 0),
            (int) ($data['customer'] ?? 0),
            (int) ($data['user'] ?? 0),
            (int) ($data['staff'] ?? 0),
            (int) ($data['project'] ?? 0),
            (bool) ($data['is_vat'] ?? false),
            (bool) ($data['make_entry'] ?? false),
            (int) ($data['pay_type'] ?? 0),
            (int) ($data['w_type'] ?? 0),
            (int) ($data['t_type'] ?? 0),
            (int) ($data['t_payer'] ?? 0),
            (float) ($data['w_cost'] ?? 0),
            (bool) ($data['foreign'] ?? false),
            (string) ($data['drv_name'] ?? ''),
            (string) ($data['tr_start'] ?? ''),
            (string) ($data['tr_end'] ?? ''),
            (string) ($data['driver_id'] ?? ''),
            (string) ($data['car_num'] ?? ''),
            (string) ($data['tr_text'] ?? ''),
            (string) ($data['sender'] ?? ''),
            (string) ($data['reciever'] ?? ''),
            (string) ($data['comment'] ?? ''),
            (int) ($data['mileage'] ?? 0),
            (string) ($data['in_date'] ?? ''),
            (int) ($data['box'] ?? 0),
            (int) ($data['car'] ?? 0),
            (int) ($data['overlap_type'] ?? 0),
            (float) ($data['overlap_amount'] ?? 0),
            (array) ($data['add_fields'] ?? []),
            (array) ($data['products'] ?? []),
            (array) ($data['services'] ?? []),
            (array) ($data['sub_accounts'] ?? []),
            isset($data['waybill_status']) ? (int) $data['waybill_status'] : null,
        );
    }
}
