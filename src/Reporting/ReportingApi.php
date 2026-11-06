<?php

declare(strict_types=1);

/**
 * Full-featured Reporting API client with raw, typed, and chunked methods.
 */

namespace Fina\Sdk\Laravel\Reporting;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Reporting\Dto\AutoServicesOutJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\CafeOrderDetailedReportResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\CustomersOrderJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\CustomersReturnJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\CycleReportResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\DiscountCardsJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\EntriesJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\MoneyJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\MovesJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\ProductionsJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\ProductsInReturnReportResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\ProductsLastInReportResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\ProvidedServicesJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\RealizesJournalResponseDto;
use Fina\Sdk\Laravel\Reporting\Dto\ReceivedServicesJournalResponseDto;
use Fina\Sdk\Laravel\Support\FinaDate;
use InvalidArgumentException;

/**
 * Provides raw, typed, and chunked access to all FINA Reporting API endpoints.
 *
 * Chunked methods split large date ranges into smaller windows to avoid
 * API timeouts, then merge and deduplicate the results.
 */
final class ReportingApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'reporting');
    }

    /**
     * Low-level helper:
     * GET api/reporting/{method}/{date_from}/{date_to}
     * date format required: yyyy-MM-ddTHH:mm:ss
     */
    public function getRange(string $method, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $fromStr = FinaDate::toFinaDateTime($from);
        $toStr = FinaDate::toFinaDateTime($to);

        return $this->get(
            $method.'/'.rawurlencode($fromStr).'/'.rawurlencode($toStr),
            [],
            "reporting.{$method} returned ex"
        );
    }

    /**
     * Chunk + merge + dedupe by stable key.
     *
     * @param  string  $method  reporting method name (e.g. getEntriesJournal)
     * @param  string  $collectionKey  response key to merge (e.g. 'journals' or 'reports')
     * @param  callable|null  $dedupeKeyFn  fn(array $item): string
     */
    public function getRangeChunked(
        string $method,
        string $collectionKey,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $chunkDays = 7,
        ?callable $dedupeKeyFn = null
    ): array {
        $start = CarbonImmutable::instance($from)->startOfDay();
        $end = CarbonImmutable::instance($to);

        if ($chunkDays < 1) {
            throw new InvalidArgumentException('chunkDays must be at least 1');
        }

        $seenInEarlierChunks = [];
        $all = [];
        $last = null;

        for ($cursor = $start; $cursor->lt($end); $cursor = $cursor->addDays($chunkDays)) {
            $chunkFrom = $cursor;
            $chunkTo = $cursor->addDays($chunkDays)->min($end);

            $resp = $this->getRange($method, $chunkFrom, $chunkTo);
            $last = $resp;

            // A key is an OPERATION, not a row: getEntriesJournal returns one row
            // per entry line and every line of an operation carries the same
            // id + version. Rows are only dropped when their operation was already
            // delivered by an earlier chunk (window boundaries overlap by design);
            // inside one chunk every row is kept.
            $seenInThisChunk = [];

            $items = (array) ($resp[$collectionKey] ?? []);
            foreach ($items as $item) {
                $itemArr = (array) $item;

                $key = $dedupeKeyFn
                    ? (string) $dedupeKeyFn($itemArr)
                    : $this->defaultDedupeKey($itemArr);

                if (isset($seenInEarlierChunks[$key])) {
                    continue;
                }

                $seenInThisChunk[$key] = true;
                $all[] = $itemArr;
            }

            $seenInEarlierChunks += $seenInThisChunk;
        }

        return [
            $collectionKey => $all,
            'ex' => $last['ex'] ?? null,
        ];
    }

    private function defaultDedupeKey(array $item): string
    {
        // Journals often return id+version — most stable dedupe key
        if (isset($item['id']) && isset($item['version'])) {
            return 'idv:'.(string) $item['id'].':'.(string) $item['version'];
        }

        if (isset($item['id'])) {
            return 'id:'.(string) $item['id'];
        }

        return 'h:'.sha1(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }

    // ---------------------------------------------------------------------
    // Journals (raw)
    // ---------------------------------------------------------------------

    /** getCustomersOrderJournal (raw) */
    public function customersOrderJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getCustomersOrderJournal', $from, $to);
    }

    /** getCustomersReturnJournal (raw) */
    public function customersReturnJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getCustomersReturnJournal', $from, $to);
    }

    /** getCustomersMoneyJournal (raw) */
    public function customersMoneyJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getCustomersMoneyJournal', $from, $to);
    }

    /** getVendorsMoneyJournal (raw) */
    public function vendorsMoneyJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getVendorsMoneyJournal', $from, $to);
    }

    /** getProductionsJournal (raw) */
    public function productionsJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getProductionsJournal', $from, $to);
    }

    /** getEntriesJournal (raw) */
    public function entriesJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getEntriesJournal', $from, $to);
    }

    /** getDiscountCardsJournal (raw) */
    public function discountCardsJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getDiscountCardsJournal', $from, $to);
    }

    /** getDocProvidedServicesJournal (raw) */
    public function providedServicesJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getDocProvidedServicesJournal', $from, $to);
    }

    /** getDocReceivedServicesJournal (raw) */
    public function receivedServicesJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getDocReceivedServicesJournal', $from, $to);
    }

    /** getRealizesJournal (raw) */
    public function realizesJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getRealizesJournal', $from, $to);
    }

    /** getMovesJournal (raw) */
    public function movesJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getMovesJournal', $from, $to);
    }

    /** getAutoServicesOutJournal (raw, v8.0) */
    public function autoServicesOutJournal(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getAutoServicesOutJournal', $from, $to);
    }

    // ---------------------------------------------------------------------
    // Journals (chunked, timeout-safe)
    // ---------------------------------------------------------------------

    /** getEntriesJournal (chunked, deduplicated) */
    public function entriesJournalChunked(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 7): array
    {
        return $this->getRangeChunked('getEntriesJournal', 'journals', $from, $to, $chunkDays);
    }

    /** getRealizesJournal (chunked, deduplicated) */
    public function realizesJournalChunked(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): array
    {
        return $this->getRangeChunked('getRealizesJournal', 'journals', $from, $to, $chunkDays);
    }

    /** getMovesJournal (chunked, deduplicated) */
    public function movesJournalChunked(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): array
    {
        return $this->getRangeChunked('getMovesJournal', 'journals', $from, $to, $chunkDays);
    }

    /** getCustomersMoneyJournal (chunked, deduplicated) */
    public function customersMoneyJournalChunked(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): array
    {
        return $this->getRangeChunked('getCustomersMoneyJournal', 'journals', $from, $to, $chunkDays);
    }

    /** getVendorsMoneyJournal (chunked, deduplicated) */
    public function vendorsMoneyJournalChunked(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): array
    {
        return $this->getRangeChunked('getVendorsMoneyJournal', 'journals', $from, $to, $chunkDays);
    }

    // ---------------------------------------------------------------------
    // Reports (raw)
    // ---------------------------------------------------------------------

    /** getCustomersCycleReport (raw) */
    public function customersCycleReport(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getCustomersCycleReport', $from, $to);
    }

    /** getVendorsCycleReport (raw) */
    public function vendorsCycleReport(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getVendorsCycleReport', $from, $to);
    }

    /** getProductsLastInReport (raw) */
    public function productsLastInReport(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getProductsLastInReport', $from, $to);
    }

    /** getProductsInReturnReport (raw) */
    public function productsInReturnReport(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getProductsInReturnReport', $from, $to);
    }

    /** getCafeOrderDetailedReport (raw, v8.0) */
    public function cafeOrderDetailedReport(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->getRange('getCafeOrderDetailedReport', $from, $to);
    }

    // ---------------------------------------------------------------------
    // Typed: Cycle Reports
    // ---------------------------------------------------------------------

    /** getCustomersCycleReport (typed). */
    public function customersCycleReportTyped(DateTimeInterface $from, DateTimeInterface $to): CycleReportResponseDto
    {
        return CycleReportResponseDto::fromArray(
            $this->getRange('getCustomersCycleReport', $from, $to)
        );
    }

    /** getVendorsCycleReport (typed). */
    public function vendorsCycleReportTyped(DateTimeInterface $from, DateTimeInterface $to): CycleReportResponseDto
    {
        return CycleReportResponseDto::fromArray(
            $this->getRange('getVendorsCycleReport', $from, $to)
        );
    }

    // ---------------------------------------------------------------------
    // Typed: Money Journals
    // ---------------------------------------------------------------------

    /** getCustomersMoneyJournal (typed). */
    public function customersMoneyJournalTyped(DateTimeInterface $from, DateTimeInterface $to): MoneyJournalResponseDto
    {
        return MoneyJournalResponseDto::fromArray(
            $this->getRange('getCustomersMoneyJournal', $from, $to)
        );
    }

    /** getVendorsMoneyJournal (typed). */
    public function vendorsMoneyJournalTyped(DateTimeInterface $from, DateTimeInterface $to): MoneyJournalResponseDto
    {
        return MoneyJournalResponseDto::fromArray(
            $this->getRange('getVendorsMoneyJournal', $from, $to)
        );
    }

    /** getCustomersMoneyJournal (chunked + typed). */
    public function customersMoneyJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): MoneyJournalResponseDto
    {
        return MoneyJournalResponseDto::fromArray(
            $this->getRangeChunked('getCustomersMoneyJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getVendorsMoneyJournal (chunked + typed). */
    public function vendorsMoneyJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): MoneyJournalResponseDto
    {
        return MoneyJournalResponseDto::fromArray(
            $this->getRangeChunked('getVendorsMoneyJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getEntriesJournal (typed). */
    public function entriesJournalTyped(DateTimeInterface $from, DateTimeInterface $to): EntriesJournalResponseDto
    {
        return EntriesJournalResponseDto::fromArray(
            $this->getRange('getEntriesJournal', $from, $to)
        );
    }

    /** getEntriesJournal (chunked + typed). */
    public function entriesJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 7): EntriesJournalResponseDto
    {
        return EntriesJournalResponseDto::fromArray(
            $this->getRangeChunked('getEntriesJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getProductsLastInReport (typed). */
    public function productsLastInReportTyped(DateTimeInterface $from, DateTimeInterface $to): ProductsLastInReportResponseDto
    {
        return ProductsLastInReportResponseDto::fromArray(
            $this->getRange('getProductsLastInReport', $from, $to)
        );
    }

    /** getProductsInReturnReport (typed). */
    public function productsInReturnReportTyped(DateTimeInterface $from, DateTimeInterface $to): ProductsInReturnReportResponseDto
    {
        return ProductsInReturnReportResponseDto::fromArray(
            $this->getRange('getProductsInReturnReport', $from, $to)
        );
    }

    /** getCustomersOrderJournal (typed). */
    public function customersOrderJournalTyped(DateTimeInterface $from, DateTimeInterface $to): CustomersOrderJournalResponseDto
    {
        return CustomersOrderJournalResponseDto::fromArray(
            $this->getRange('getCustomersOrderJournal', $from, $to)
        );
    }

    /** getCustomersOrderJournal (chunked + typed). */
    public function customersOrderJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): CustomersOrderJournalResponseDto
    {
        return CustomersOrderJournalResponseDto::fromArray(
            $this->getRangeChunked('getCustomersOrderJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getCustomersReturnJournal (typed). */
    public function customersReturnJournalTyped(DateTimeInterface $from, DateTimeInterface $to): CustomersReturnJournalResponseDto
    {
        return CustomersReturnJournalResponseDto::fromArray(
            $this->getRange('getCustomersReturnJournal', $from, $to)
        );
    }

    /** getCustomersReturnJournal (chunked + typed). */
    public function customersReturnJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): CustomersReturnJournalResponseDto
    {
        return CustomersReturnJournalResponseDto::fromArray(
            $this->getRangeChunked('getCustomersReturnJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getProductionsJournal (typed). */
    public function productionsJournalTyped(DateTimeInterface $from, DateTimeInterface $to): ProductionsJournalResponseDto
    {
        return ProductionsJournalResponseDto::fromArray(
            $this->getRange('getProductionsJournal', $from, $to)
        );
    }

    /** getProductionsJournal (chunked + typed). */
    public function productionsJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): ProductionsJournalResponseDto
    {
        return ProductionsJournalResponseDto::fromArray(
            $this->getRangeChunked('getProductionsJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getDiscountCardsJournal (typed). */
    public function discountCardsJournalTyped(DateTimeInterface $from, DateTimeInterface $to): DiscountCardsJournalResponseDto
    {
        return DiscountCardsJournalResponseDto::fromArray(
            $this->getRange('getDiscountCardsJournal', $from, $to)
        );
    }

    /** getDiscountCardsJournal (chunked + typed). */
    public function discountCardsJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): DiscountCardsJournalResponseDto
    {
        return DiscountCardsJournalResponseDto::fromArray(
            $this->getRangeChunked('getDiscountCardsJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getDocProvidedServicesJournal (typed). */
    public function providedServicesJournalTyped(DateTimeInterface $from, DateTimeInterface $to): ProvidedServicesJournalResponseDto
    {
        return ProvidedServicesJournalResponseDto::fromArray(
            $this->getRange('getDocProvidedServicesJournal', $from, $to)
        );
    }

    /** getDocProvidedServicesJournal (chunked + typed). */
    public function providedServicesJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): ProvidedServicesJournalResponseDto
    {
        return ProvidedServicesJournalResponseDto::fromArray(
            $this->getRangeChunked('getDocProvidedServicesJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getDocReceivedServicesJournal (typed). */
    public function receivedServicesJournalTyped(DateTimeInterface $from, DateTimeInterface $to): ReceivedServicesJournalResponseDto
    {
        return ReceivedServicesJournalResponseDto::fromArray(
            $this->getRange('getDocReceivedServicesJournal', $from, $to)
        );
    }

    /** getDocReceivedServicesJournal (chunked + typed). */
    public function receivedServicesJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): ReceivedServicesJournalResponseDto
    {
        return ReceivedServicesJournalResponseDto::fromArray(
            $this->getRangeChunked('getDocReceivedServicesJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    // ---------------------------------------------------------------------
    // Typed: Realizes + Moves journals
    // ---------------------------------------------------------------------

    /** getRealizesJournal (typed). */
    public function realizesJournalTyped(DateTimeInterface $from, DateTimeInterface $to): RealizesJournalResponseDto
    {
        return RealizesJournalResponseDto::fromArray(
            $this->getRange('getRealizesJournal', $from, $to)
        );
    }

    /** getRealizesJournal (chunked + typed). */
    public function realizesJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): RealizesJournalResponseDto
    {
        return RealizesJournalResponseDto::fromArray(
            $this->getRangeChunked('getRealizesJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    /** getMovesJournal (typed). */
    public function movesJournalTyped(DateTimeInterface $from, DateTimeInterface $to): MovesJournalResponseDto
    {
        return MovesJournalResponseDto::fromArray(
            $this->getRange('getMovesJournal', $from, $to)
        );
    }

    /** getMovesJournal (chunked + typed). */
    public function movesJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): MovesJournalResponseDto
    {
        return MovesJournalResponseDto::fromArray(
            $this->getRangeChunked('getMovesJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    // ---------------------------------------------------------------------
    // v8.0: Auto Services Out Journal (typed + chunked)
    // ---------------------------------------------------------------------

    /** getAutoServicesOutJournal (typed, v8.0). */
    public function autoServicesOutJournalTyped(DateTimeInterface $from, DateTimeInterface $to): AutoServicesOutJournalResponseDto
    {
        return AutoServicesOutJournalResponseDto::fromArray(
            $this->getRange('getAutoServicesOutJournal', $from, $to)
        );
    }

    /** getAutoServicesOutJournal (chunked, v8.0). */
    public function autoServicesOutJournalChunked(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): array
    {
        return $this->getRangeChunked('getAutoServicesOutJournal', 'journals', $from, $to, $chunkDays);
    }

    /** getAutoServicesOutJournal (chunked + typed, v8.0). */
    public function autoServicesOutJournalChunkedTyped(DateTimeInterface $from, DateTimeInterface $to, int $chunkDays = 14): AutoServicesOutJournalResponseDto
    {
        return AutoServicesOutJournalResponseDto::fromArray(
            $this->getRangeChunked('getAutoServicesOutJournal', 'journals', $from, $to, $chunkDays)
        );
    }

    // ---------------------------------------------------------------------
    // v8.0: Cafe Order Detailed Report (typed)
    // ---------------------------------------------------------------------

    /** getCafeOrderDetailedReport (typed, v8.0). */
    public function cafeOrderDetailedReportTyped(DateTimeInterface $from, DateTimeInterface $to): CafeOrderDetailedReportResponseDto
    {
        return CafeOrderDetailedReportResponseDto::fromArray(
            $this->getRange('getCafeOrderDetailedReport', $from, $to)
        );
    }
}
