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
use Fina\Sdk\Laravel\Operation\Dto\Documents\CustomerOrderDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\CustomerReturnDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\ProductMoveDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\ProductOutDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Documents\ProductOutSingleDocDto;
use Fina\Sdk\Laravel\Operation\Dto\Production\ProductionDocDto;
use Fina\Sdk\Laravel\Operation\Dto\RsStatusResponse;
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

    // ---- Card issuance docs ----

    /** Save a discount card issuance document (saveDocDiscountCard). */
    public function saveDiscountCard(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocDiscountCard', $payload);
    }

    /** Save a gift card issuance document (saveDocGiftCard, insert only). */
    public function saveGiftCard(ArrayPayload|array $payload): SaveDocResponse
    {
        return $this->save('saveDocGiftCard', $payload);
    }

    // ---- RS.ge waybill status ----

    /**
     * POST api/operation/updateRsStatus
     * Request: {id (FINA operation id), w_id (RS waybill id), w_status, w_num}
     * Response: {status, ex}
     */
    public function updateRsStatus(int $operationId, int $waybillId, int $waybillStatus, string $waybillNum): RsStatusResponse
    {
        $data = $this->post('updateRsStatus', [
            'id' => $operationId,
            'w_id' => $waybillId,
            'w_status' => $waybillStatus,
            'w_num' => $waybillNum,
        ], 'operation.updateRsStatus returned ex');

        return RsStatusResponse::fromArray($data);
    }

    // -----------------------
    // getDoc* wrappers (raw)
    // -----------------------

    /** Retrieve a customer order document (raw). */
    public function getCustomerOrder(int $id): array
    {
        return $this->getDoc('getDocCustomerOrder', $id);
    }

    /** Retrieve a customer order document as a typed DTO (`customer_order`). */
    public function getCustomerOrderTyped(int $id): CustomerOrderDocDto
    {
        $data = $this->getDoc('getDocCustomerOrder', $id);

        return CustomerOrderDocDto::fromArray((array) ($data['customer_order'] ?? []));
    }

    /** Retrieve a product-out document (raw). */
    public function getProductOut(int $id): array
    {
        return $this->getDoc('getDocProductOut', $id);
    }

    /** Retrieve a product-out document as a typed DTO (`product_out`, doc type 21). */
    public function getProductOutTyped(int $id): ProductOutDocDto
    {
        $data = $this->getDoc('getDocProductOut', $id);

        return ProductOutDocDto::fromArray((array) ($data['product_out'] ?? []));
    }

    /** Retrieve a retail product-out document (raw, `product_out_single`). */
    public function getProductOutSingle(int $id): array
    {
        return $this->getDoc('getDocProductOutSingle', $id);
    }

    /** Retrieve a retail product-out document as a typed DTO (`product_out_single`, doc type 23). */
    public function getProductOutSingleTyped(int $id): ProductOutSingleDocDto
    {
        $data = $this->getDoc('getDocProductOutSingle', $id);

        return ProductOutSingleDocDto::fromArray((array) ($data['product_out_single'] ?? []));
    }

    /** Retrieve a product move document (raw). */
    public function getProductMove(int $id): array
    {
        return $this->getDoc('getDocProductMove', $id);
    }

    /** Retrieve a product move document as a typed DTO (`product_move`, doc type 20). */
    public function getProductMoveTyped(int $id): ProductMoveDocDto
    {
        $data = $this->getDoc('getDocProductMove', $id);

        return ProductMoveDocDto::fromArray((array) ($data['product_move'] ?? []));
    }

    /** Retrieve a fixed-asset sale document (raw, `inventory_out`). */
    public function getInventoryOut(int $id): array
    {
        return $this->getDoc('getDocInventoryOut', $id);
    }

    /** Retrieve a fixed-asset move document (raw, `inventory_move`). */
    public function getInventoryMove(int $id): array
    {
        return $this->getDoc('getDocInventoryMove', $id);
    }

    /** Retrieve a fixed-asset return from a customer (raw, `customer_inventory_return`). */
    public function getCustomerInventoryReturn(int $id): array
    {
        return $this->getDoc('getDocCustomerInventoryReturn', $id);
    }

    /** Retrieve a provided service document (raw, `doc_providedservice`). */
    public function getProvidedService(int $id): array
    {
        return $this->getDoc('getDocProvidedService', $id);
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

    /** Retrieve a customer return document as a typed DTO (`customer_return`, doc type 9). */
    public function getCustomerReturnTyped(int $id): CustomerReturnDocDto
    {
        $data = $this->getDoc('getDocCustomerReturn', $id);

        return CustomerReturnDocDto::fromArray((array) ($data['customer_return'] ?? []));
    }

    /** Retrieve an issued discount card document (raw, `discount_card`). */
    public function getDiscountCard(int $id): array
    {
        return $this->getDoc('getDocDiscountCard', $id);
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
