<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Cafe Order Detailed Report response (v8.0).
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

use Fina\Sdk\Laravel\Reporting\ReportingApi;

/**
 * Represents a single product line from getCafeOrderDetailedReport.
 * `user_id` (the waiter) and `guest_count` are in the 10.0 document; null when the FINA build
 * does not send them.
 *
 * @see ReportingApi::cafeOrderDetailedReportTyped()
 */
final class CafeOrderDetailedReportRowDto
{
    /**
     * @param  array<string, mixed>  $raw  Full raw API row for forward-compatibility.
     */
    public function __construct(
        public readonly string $date,
        public readonly string $docNum,
        public readonly int $storeId,
        public readonly int $statusId,
        public readonly int $productId,
        public readonly int $groupId,
        public readonly int $unitId,
        public readonly float $quantity,
        public readonly float $amount,
        public readonly array $raw = [],
        public readonly ?int $userId = null,
        public readonly ?int $guestCount = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row  Raw API row data.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            date: (string) ($row['date'] ?? ''),
            docNum: (string) ($row['doc_num'] ?? ''),
            storeId: (int) ($row['store_id'] ?? 0),
            statusId: (int) ($row['status_id'] ?? 0),
            userId: isset($row['user_id']) ? (int) $row['user_id'] : null,
            guestCount: isset($row['guest_count']) ? (int) $row['guest_count'] : null,
            productId: (int) ($row['product_id'] ?? 0),
            groupId: (int) ($row['group_id'] ?? 0),
            unitId: (int) ($row['unit_id'] ?? 0),
            quantity: (float) ($row['quantity'] ?? 0.0),
            amount: (float) ($row['amount'] ?? 0.0),
            raw: $row,
        );
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'doc_num' => $this->docNum,
            'store_id' => $this->storeId,
            'status_id' => $this->statusId,
            'user_id' => $this->userId,
            'guest_count' => $this->guestCount,
            'product_id' => $this->productId,
            'group_id' => $this->groupId,
            'unit_id' => $this->unitId,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'raw' => $this->raw,
        ];
    }
}
