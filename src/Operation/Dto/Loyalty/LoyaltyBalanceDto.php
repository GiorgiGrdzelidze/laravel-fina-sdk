<?php

declare(strict_types=1);

/**
 * Operation DTO: loyalty (Cloud) card balance from getLoyaltyCustomerBalances / getLoyaltyCustomerBalance (10.0.20260713).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use Fina\Sdk\Laravel\Operation\LoyaltyApi;

/**
 * A field the answer lacks is null. An answer without a `loyalty_balance` object (missing or null)
 * gives a DTO whose fields are all null, not card 0 with balance 0.
 *
 * @see LoyaltyApi::customerBalances()
 * @see LoyaltyApi::customerBalance()
 */
final readonly class LoyaltyBalanceDto
{
    public function __construct(
        public ?int $id,
        public ?string $code,
        public ?string $name,
        public ?string $tel,
        public ?string $qrCode,
        public ?float $balance,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            tel: isset($data['tel']) ? (string) $data['tel'] : null,
            qrCode: isset($data['qr_code']) ? (string) $data['qr_code'] : null,
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            raw: $data,
        );
    }
}
