<?php

declare(strict_types=1);

/**
 * Operation DTO: RS.ge waybill status codes (`waybill_status`, 10.0.20261106).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

/**
 * Codes of `waybill_status` on getDocProductOut, getDocInventoryOut, getDocProductMove,
 * getDocInventoryMove, getDocCustomerInventoryReturn, getDocCustomerReturn and getDocAutoService.
 */
final class WaybillStatus
{
    /** სტატუსი არ აქვს */
    public const NONE = -1;

    /** შენახული */
    public const SAVED = 0;

    /** აქტივირებული */
    public const ACTIVATED = 1;

    /** დასრულებული */
    public const COMPLETED = 2;

    /** გადამზიდავთან */
    public const WITH_CARRIER = 8;

    /** გაუქმებული */
    public const CANCELLED = -2;
}
