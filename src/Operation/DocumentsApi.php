<?php

declare(strict_types=1);

/**
 * Operation API client for saving and retrieving FINA documents.
 */

namespace Fina\Sdk\Laravel\Operation;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Contracts\ArrayPayload;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Operation\Dto\AutoServiceDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Production\ProductionDocDto;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Support\PayloadValidator;

/**
 * Provides generic and typed methods for saving and retrieving documents
 * (orders, returns, money operations, productions, etc.) via the FINA Operation API.
 *
 * Payloads implementing {@see ValidatesPayload} are validated automatically before submission.
 */
final class DocumentsApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'operation');
    }

    /**
     * Generic saver for any saveDoc* endpoint.
     * - If payload implements ValidatesPayload -> validates automatically (fail-fast)
     */
    public function save(string $methodName, ArrayPayload|array $payload): SaveDocResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
        }

        $body = $payload instanceof ArrayPayload ? $payload->toArray() : $payload;

        $data = $this->post($methodName, $body, "operation.{$methodName} returned ex");

        return SaveDocResponse::fromArray($data);
    }

    /**
     * Generic getter for any getDoc* endpoint.
     */
    public function getDoc(string $methodName, int $id): array
    {
        return $this->get($methodName.'/'.$id, [], "operation.{$methodName} returned ex");
    }

    // -----------------------
    // saveDoc* wrappers
    // -----------------------

    /** Save a customer order document. */
    public function saveCustomerOrder(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocCustomerOrder', $payload);
    }

    /** Save a product-out (sale/shipment) document. */
    public function saveProductOut(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocProductOut', $payload);
    }

    /** Save a product move (inter-store transfer) document. */
    public function saveProductMove(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocProductMove', $payload);
    }

    /** Save a provided service document. */
    public function saveProvidedService(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocProvidedService', $payload);
    }

    /** Save a customer return document. */
    public function saveCustomerReturn(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocCustomerReturn', $payload);
    }

    /** Save a product-in (purchase/receipt) document. */
    public function saveProductIn(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocProductIn', $payload);
    }

    /** Save a product cancellation (write-off) document. */
    public function saveProductCancel(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocProductCancel', $payload);
    }

    /** Save a cafe order document. */
    public function saveCafeOrder(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocCafeOrder', $payload);
    }

    /** Save a customer money-in (payment received) document. */
    public function saveCustomerMoneyIn(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocCustomerMoneyIn', $payload);
    }

    /** Save a customer advance-in (prepayment) document. */
    public function saveCustomerAdvanceIn(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocCustomerAdvanceIn', $payload);
    }

    /** Save a customer money return document. */
    public function saveCustomerMoneyReturn(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocCustomerMoneyReturn', $payload);
    }

    /** Save a bonus payment document. */
    public function saveBonusPayment(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocBonusPayment', $payload);
    }

    // ---- Money docs ----

    /** Save a customer money-out document. */
    public function saveCustomerMoneyOut(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocCustomerMoneyOut', $payload);
    }

    /** Save a vendor money-in document. */
    public function saveVendorMoneyIn(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocVendorMoneyIn', $payload);
    }

    /** Save a vendor money-out document. */
    public function saveVendorMoneyOut(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocVendorMoneyOut', $payload);
    }

    /** Save a vendor money return document. */
    public function saveVendorMoneyReturn(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocVendorMoneyReturn', $payload);
    }

    // ---- Production docs ----

    /** Save a production document (saveDocProduction). */
    public function saveProduction(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocProduction', $payload);
    }

    /** Retrieve a production document as a typed DTO. */
    public function getProductionTyped(int $id): ProductionDocDto
    {
        $data = $this->getDoc('getDocProduction', $id);

        return ProductionDocDto::fromArray((array) ($data['production'] ?? []));
    }

    // ---- Received service docs (v8.0) ----

    /** Save a received service document (v8.0). */
    public function saveReceivedService(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocReceivedService', $payload);
    }

    // ---- Bonus card docs (v8.0) ----

    /** Save a bonus/discount card document (v8.0). */
    public function saveBonusCard(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocBonusCard', $payload);
    }

    // ---- Gift payment docs (v8.0) ----

    /** Save a gift card payment document (v8.0). */
    public function saveGiftPayment(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocGiftPayment', $payload);
    }

    // -----------------------
    // getDoc* wrappers (raw)
    // -----------------------

    /** Retrieve a customer order document (raw). */
    public function getCustomerOrder(int $id): array
    {
        return $this->getDoc('getDocCustomerOrder', $id);
    }

    /** Retrieve a product-out document (raw). */
    public function getProductOut(int $id): array
    {
        return $this->getDoc('getDocProductOut', $id);
    }

    /** Retrieve a retail product-out document (raw, `product_out_single`). */
    public function getProductOutSingle(int $id): array
    {
        return $this->getDoc('getDocProductOutSingle', $id);
    }

    /** Retrieve a product move document (raw). */
    public function getProductMove(int $id): array
    {
        return $this->getDoc('getDocProductMove', $id);
    }

    /** Retrieve a received service document (raw). */
    public function getReceivedService(int $id): array
    {
        return $this->getDoc('getDocReceivedService', $id);
    }

    /** Retrieve a customer return document (raw). */
    public function getCustomerReturn(int $id): array
    {
        return $this->getDoc('getDocCustomerReturn', $id);
    }

    /** Retrieve a production document (raw). */
    public function getProduction(int $id): array
    {
        return $this->getDoc('getDocProduction', $id);
    }

    // ---- Auto-service docs (v8.0) ----

    /** Retrieve an auto-service document (raw, v8.0). */
    public function getAutoService(int $id): array
    {
        return $this->getDoc('getDocAutoService', $id);
    }

    /** Retrieve an auto-service document as a typed DTO (v8.0). */
    public function getAutoServiceTyped(int $id): AutoServiceDocDto
    {
        $data = $this->getDoc('getDocAutoService', $id);

        return AutoServiceDocDto::fromArray((array) ($data['autoservice'] ?? []));
    }
}
