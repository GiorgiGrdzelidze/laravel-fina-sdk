<?php

declare(strict_types=1);

/**
 * Low-level Reporting API client for journal range queries.
 */

namespace Fina\Sdk\Laravel\Reporting;

use DateTimeInterface;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Support\FinaDate;

/**
 * Provides simple date-range journal queries against the FINA Reporting API.
 *
 * For typed responses and chunked queries, prefer {@see ReportingApi}.
 */
final class JournalsApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'reporting');
    }

    /**
     * Generic range caller:
     * GET api/reporting/{method}/{from}/{to}
     */
    public function range(string $method, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $fromStr = FinaDate::toFinaDateTime($from);
        $toStr = FinaDate::toFinaDateTime($to);

        return $this->get(
            $method.'/'.rawurlencode($fromStr).'/'.rawurlencode($toStr),
            [],
            "reporting.{$method} returned ex"
        );
    }

    // -------------------------
    // Journals (TOC coverage)
    // -------------------------

    /** getCustomersOrderJournal */
    public function customersOrders(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getCustomersOrderJournal', $from, $to);
    }

    /** getCustomersReturnJournal */
    public function customersReturns(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getCustomersReturnJournal', $from, $to);
    }

    /** getCustomersMoneyJournal */
    public function customersMoney(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getCustomersMoneyJournal', $from, $to);
    }

    /** getVendorsMoneyJournal */
    public function vendorsMoney(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getVendorsMoneyJournal', $from, $to);
    }

    /** getProductionsJournal */
    public function productions(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getProductionsJournal', $from, $to);
    }

    /** getEntriesJournal */
    public function entries(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getEntriesJournal', $from, $to);
    }

    /** getDiscountCardsJournal */
    public function discountCards(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getDiscountCardsJournal', $from, $to);
    }

    /** getDocProvidedServicesJournal */
    public function providedServices(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getDocProvidedServicesJournal', $from, $to);
    }

    /** getDocReceivedServicesJournal */
    public function receivedServices(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getDocReceivedServicesJournal', $from, $to);
    }

    /** getRealizesJournal */
    public function realizes(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getRealizesJournal', $from, $to);
    }

    /** getMovesJournal */
    public function moves(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getMovesJournal', $from, $to);
    }

    /** getAutoServicesOutJournal (v8.0) */
    public function autoServicesOut(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->range('getAutoServicesOutJournal', $from, $to);
    }
}
