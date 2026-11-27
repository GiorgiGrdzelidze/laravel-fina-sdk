<?php

declare(strict_types=1);

/**
 * Operation API client for loyalty and bonus card endpoints.
 */

namespace Fina\Sdk\Laravel\Operation;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Operation\Dto\BonusCoeffResponse;
use Fina\Sdk\Laravel\Operation\Dto\BonusOperationPayload;
use Fina\Sdk\Laravel\Operation\Dto\BonusOperationResponse;
use Fina\Sdk\Laravel\Operation\Dto\DiscountOperationPayload;
use Fina\Sdk\Laravel\Operation\Dto\DiscountOperationResponse;
use Fina\Sdk\Laravel\Operation\Dto\GiftCardInfoDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyBalanceDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyBonusHistoryDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyBonusPointPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyCustomerPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyGroupDto;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyGroupPayload;
use Fina\Sdk\Laravel\Operation\Dto\Loyalty\LoyaltyProgramDto;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Support\PayloadValidator;

/**
 * Provides methods for loyalty card lookups, bonus coefficients,
 * and bonus operations via the FINA Operation API, and the
 * loyalty (Cloud) cards, programs, groups and balances of 10.0.
 */
final class LoyaltyApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'operation');
    }

    /**
     * GET api/operation/getBonusCoeff
     */
    public function bonusCoeff(): BonusCoeffResponse
    {
        $data = $this->get('getBonusCoeff', [], 'operation.getBonusCoeff returned ex');

        return BonusCoeffResponse::fromArray($data);
    }

    /**
     * GET api/operation/getLoyaltyCardsByHolder/{holder_code}
     * Returns array with 'cards' key (raw)
     */
    public function cardsByHolder(string $holderCode): array
    {
        return $this->get(
            'getLoyaltyCardsByHolder/'.rawurlencode($holderCode),
            [],
            'operation.getLoyaltyCardsByHolder returned ex'
        );
    }

    /**
     * POST api/operation/saveDocBonusOperation
     * Request: {card_id, ref_id, coeff(1|-1), amount}
     * Response: {res, ex}
     */
    public function saveBonusOperation(BonusOperationPayload|array $payload): BonusOperationResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
            $body = $payload->toArray();
        } else {
            $body = $payload;
        }

        $data = $this->post('saveDocBonusOperation', $body, 'operation.saveDocBonusOperation returned ex');

        return BonusOperationResponse::fromArray($data);
    }

    /**
     * GET api/operation/getGiftCardInfoByCode/{code}
     * Returns single gift card info by code.
     */
    public function giftCardInfoByCode(string $code): GiftCardInfoDto
    {
        $data = $this->get(
            'getGiftCardInfoByCode/'.rawurlencode($code),
            [],
            'operation.getGiftCardInfoByCode returned ex'
        );

        return GiftCardInfoDto::fromArray((array) ($data['gift'] ?? []));
    }

    /**
     * GET api/operation/getBonusCardRestByCode/{code}
     * Returns bonus card rest by code (raw).
     */
    public function bonusCardRestByCode(string $code): array
    {
        return $this->get(
            'getBonusCardRestByCode/'.rawurlencode($code),
            [],
            'operation.getBonusCardRestByCode returned ex'
        );
    }

    /**
     * POST api/operation/saveDocDiscountOperation (10.0)
     * Request: {card_id, ref_id}
     * Response: {res, ex}; `res` is null when the answer has none
     */
    public function saveDiscountOperation(DiscountOperationPayload|array $payload): DiscountOperationResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
            $body = $payload->toArray();
        } else {
            $body = $payload;
        }

        $data = $this->post('saveDocDiscountOperation', $body, 'operation.saveDocDiscountOperation returned ex');

        return DiscountOperationResponse::fromArray($data);
    }

    // -----------------------
    // Loyalty (Cloud), 10.0
    // -----------------------

    /**
     * GET api/operation/getLoyaltyCustomers
     *
     * @return LoyaltyCustomerDto[]
     */
    public function customers(): array
    {
        $data = $this->get('getLoyaltyCustomers', [], 'operation.getLoyaltyCustomers returned ex');

        return array_map(
            static fn ($c) => LoyaltyCustomerDto::fromArray((array) $c),
            array_values((array) ($data['loyalty_customers'] ?? []))
        );
    }

    /**
     * GET api/operation/getLoyaltyCustomer/{id}
     * One card with its programs and groups; without a `loyalty_customer` object every field is null.
     */
    public function customer(int $id): LoyaltyCustomerDto
    {
        $data = $this->get("getLoyaltyCustomer/{$id}", [], 'operation.getLoyaltyCustomer returned ex');

        return LoyaltyCustomerDto::fromArray((array) ($data['loyalty_customer'] ?? []));
    }

    /**
     * GET api/operation/getLoyaltyCustomerPrograms
     *
     * @return LoyaltyProgramDto[]
     */
    public function customerPrograms(): array
    {
        $data = $this->get('getLoyaltyCustomerPrograms', [], 'operation.getLoyaltyCustomerPrograms returned ex');

        return array_map(
            static fn ($p) => LoyaltyProgramDto::fromArray((array) $p),
            array_values((array) ($data['programs'] ?? []))
        );
    }

    /**
     * GET api/operation/getLoyaltyCustomerGroups
     *
     * @return LoyaltyGroupDto[]
     */
    public function customerGroups(): array
    {
        $data = $this->get('getLoyaltyCustomerGroups', [], 'operation.getLoyaltyCustomerGroups returned ex');

        return array_map(
            static fn ($g) => LoyaltyGroupDto::fromArray((array) $g),
            array_values((array) ($data['loyalty_groups'] ?? []))
        );
    }

    /**
     * GET api/operation/getLoyaltyCustomerBalances
     *
     * @return LoyaltyBalanceDto[]
     */
    public function customerBalances(): array
    {
        $data = $this->get('getLoyaltyCustomerBalances', [], 'operation.getLoyaltyCustomerBalances returned ex');

        return array_map(
            static fn ($b) => LoyaltyBalanceDto::fromArray((array) $b),
            array_values((array) ($data['loyalty_balances'] ?? []))
        );
    }

    /**
     * GET api/operation/getLoyaltyCustomerBalance/{qr_code}
     * Without a `loyalty_balance` object (missing or null) every field is null, `balance` included.
     */
    public function customerBalance(string $qrCode): LoyaltyBalanceDto
    {
        $data = $this->get(
            'getLoyaltyCustomerBalance/'.rawurlencode($qrCode),
            [],
            'operation.getLoyaltyCustomerBalance returned ex'
        );

        return LoyaltyBalanceDto::fromArray((array) ($data['loyalty_balance'] ?? []));
    }

    /**
     * GET api/operation/getLoyaltyBonusHistory/{qr_code}
     *
     * @return LoyaltyBonusHistoryDto[]
     */
    public function bonusHistory(string $qrCode): array
    {
        $data = $this->get(
            'getLoyaltyBonusHistory/'.rawurlencode($qrCode),
            [],
            'operation.getLoyaltyBonusHistory returned ex'
        );

        return array_map(
            static fn ($h) => LoyaltyBonusHistoryDto::fromArray((array) $h),
            array_values((array) ($data['bonus_history'] ?? []))
        );
    }

    /**
     * POST api/operation/saveLoyaltyCustomer — insert (id = 0) or update a card.
     * Response: { "id", "ex" }.
     */
    public function saveCustomer(LoyaltyCustomerPayload|array $payload): SaveDocResponse
    {
        return $this->saveLoyalty('saveLoyaltyCustomer', $payload);
    }

    /**
     * POST api/operation/saveLoyaltyGroup — insert (id = 0) or update a group.
     * Response: { "id", "ex" }.
     */
    public function saveGroup(LoyaltyGroupPayload|array $payload): SaveDocResponse
    {
        return $this->saveLoyalty('saveLoyaltyGroup', $payload);
    }

    /**
     * POST api/operation/saveLoyaltyBonusPoint — give or take points (insert only).
     * Response: { "id" (the operation created), "ex" }.
     */
    public function saveBonusPoint(LoyaltyBonusPointPayload|array $payload): SaveDocResponse
    {
        return $this->saveLoyalty('saveLoyaltyBonusPoint', $payload);
    }

    private function saveLoyalty(string $method, ValidatesPayload|array $payload): SaveDocResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
            $body = $payload->toArray();
        } else {
            $body = $payload;
        }

        return SaveDocResponse::fromArray(
            $this->post($method, $body, "operation.{$method} returned ex")
        );
    }
}
