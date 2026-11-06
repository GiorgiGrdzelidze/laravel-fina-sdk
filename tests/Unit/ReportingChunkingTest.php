<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Carbon\CarbonImmutable;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Reporting\Dto\EntriesJournalResponseDto;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class ReportingChunkingTest extends TestCase
{
    private function makeClient(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    public function test_chunked_merges_and_deduplicates_by_id_version(): void
    {
        // Two chunks with overlapping items (id:1/v:a appears in both)
        $chunk1 = [
            'journals' => [
                ['id' => 1, 'version' => 'a', 'amount' => 100],
                ['id' => 2, 'version' => 'b', 'amount' => 200],
            ],
            'ex' => null,
        ];

        $chunk2 = [
            'journals' => [
                ['id' => 1, 'version' => 'a', 'amount' => 100], // duplicate
                ['id' => 3, 'version' => 'c', 'amount' => 300],
            ],
            'ex' => null,
        ];

        $callIndex = 0;

        Http::fake(function ($request) use (&$callIndex, $chunk1, $chunk2) {
            if (str_contains($request->url(), '/api/authentication/authenticate')) {
                return Http::response(['token' => 'tok', 'ex' => null], 200);
            }

            if (str_contains($request->url(), 'getEntriesJournal')) {
                $callIndex++;

                return Http::response($callIndex === 1 ? $chunk1 : $chunk2, 200);
            }

            return Http::response([], 404);
        });

        $client = $this->makeClient();

        // 14-day range with 7-day chunks = 2 chunks
        $from = CarbonImmutable::create(2025, 1, 1);
        $to = CarbonImmutable::create(2025, 1, 14);

        $result = $client->reporting()->entriesJournalChunked($from, $to, 7);

        // Should have 3 unique items (deduped id:1/v:a)
        $this->assertCount(3, $result['journals']);
        $this->assertNull($result['ex']);

        // Verify the items
        $ids = array_column($result['journals'], 'id');
        $this->assertSame([1, 2, 3], $ids);
    }

    public function test_chunked_preserves_collection_key_and_ex(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/authentication/authenticate')) {
                return Http::response(['token' => 'tok', 'ex' => null], 200);
            }

            if (str_contains($request->url(), 'getEntriesJournal')) {
                return Http::response([
                    'journals' => [
                        ['id' => 1, 'version' => 'x', 'amount' => 50],
                    ],
                    'ex' => null,
                ], 200);
            }

            return Http::response([], 404);
        });

        $client = $this->makeClient();
        $from = CarbonImmutable::create(2025, 1, 1);
        $to = CarbonImmutable::create(2025, 1, 5);

        $result = $client->reporting()->entriesJournalChunked($from, $to, 7);

        $this->assertArrayHasKey('journals', $result);
        $this->assertArrayHasKey('ex', $result);
        $this->assertCount(1, $result['journals']);
    }

    public function test_chunked_typed_returns_dto(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/authentication/authenticate')) {
                return Http::response(['token' => 'tok', 'ex' => null], 200);
            }

            if (str_contains($request->url(), 'getEntriesJournal')) {
                return Http::response([
                    'journals' => [
                        ['id' => 10, 'version' => 'v1', 'date' => '2025-01-01', 'doc_num' => 'D1', 'doc_type' => 5, 'purpose' => 'test', 'amount' => 99.9],
                    ],
                    'ex' => null,
                ], 200);
            }

            return Http::response([], 404);
        });

        $client = $this->makeClient();
        $from = CarbonImmutable::create(2025, 1, 1);
        $to = CarbonImmutable::create(2025, 1, 5);

        $dto = $client->reporting()->entriesJournalChunkedTyped($from, $to, 7);

        $this->assertInstanceOf(EntriesJournalResponseDto::class, $dto);
        $this->assertCount(1, $dto->journals);
        $this->assertSame(10, $dto->journals[0]->id);
        $this->assertSame('v1', $dto->journals[0]->version);
        $this->assertNull($dto->ex);
    }

    public function test_dedupe_falls_back_to_id_only(): void
    {
        $chunk1 = [
            'reports' => [
                ['id' => 1, 'value' => 'first'],
                ['id' => 2, 'value' => 'second'],
            ],
            'ex' => null,
        ];

        $chunk2 = [
            'reports' => [
                ['id' => 1, 'value' => 'first-dup'], // same id, no version
                ['id' => 3, 'value' => 'third'],
            ],
            'ex' => null,
        ];

        $callIndex = 0;

        Http::fake(function ($request) use (&$callIndex, $chunk1, $chunk2) {
            if (str_contains($request->url(), '/api/authentication/authenticate')) {
                return Http::response(['token' => 'tok', 'ex' => null], 200);
            }

            if (str_contains($request->url(), 'getCustomersCycleReport')) {
                $callIndex++;

                return Http::response($callIndex === 1 ? $chunk1 : $chunk2, 200);
            }

            return Http::response([], 404);
        });

        $client = $this->makeClient();
        $from = CarbonImmutable::create(2025, 1, 1);
        $to = CarbonImmutable::create(2025, 1, 14);

        $result = $client->reporting()->getRangeChunked(
            'getCustomersCycleReport',
            'reports',
            $from,
            $to,
            7
        );

        // id:1 should be deduped (first occurrence kept)
        $this->assertCount(3, $result['reports']);
        $this->assertSame('first', $result['reports'][0]['value']);
    }

    public function test_chunked_keeps_every_entry_line_of_an_operation(): void
    {
        // getEntriesJournal: one row per entry LINE; every line of operation 3121
        // carries the same id + version (as in the FINA 8.0 PDF example).
        $line = static fn (string $debit, string $credit, float $amount): array => [
            'id' => 3121,
            'version' => 'AAAAAAABf5A=',
            'date' => '2018-10-19 16:03:21',
            'doc_num' => '5',
            'doc_type' => 49,
            'amount' => $amount,
            'debit_acc' => $debit,
            'credit_acc' => $credit,
        ];

        $chunk1 = [
            'journals' => [
                $line('1110.1', '3120.8', 12.0),
                $line('3339', '3330', 1.83),
                // two genuinely identical lines must both survive
                $line('1410', '6110', 5.0),
                $line('1410', '6110', 5.0),
                ['id' => 3122, 'version' => 'B', 'date' => '2018-10-20 10:00:00', 'amount' => 1.0, 'debit_acc' => '1410', 'credit_acc' => '6110'],
            ],
            'ex' => null,
        ];

        // Chunk 2 overlaps the boundary and re-sends operation 3122 in full, plus a new one.
        $chunk2 = [
            'journals' => [
                ['id' => 3122, 'version' => 'B', 'date' => '2018-10-20 10:00:00', 'amount' => 1.0, 'debit_acc' => '1410', 'credit_acc' => '6110'],
                ['id' => 3123, 'version' => 'C', 'date' => '2018-10-25 10:00:00', 'amount' => 2.0, 'debit_acc' => '1410', 'credit_acc' => '6110'],
                ['id' => 3123, 'version' => 'C', 'date' => '2018-10-25 10:00:00', 'amount' => 0.3, 'debit_acc' => '3339', 'credit_acc' => '3330'],
            ],
            'ex' => null,
        ];

        $callIndex = 0;

        Http::fake(function ($request) use (&$callIndex, $chunk1, $chunk2) {
            if (str_contains($request->url(), '/api/authentication/authenticate')) {
                return Http::response(['token' => 'tok', 'ex' => null], 200);
            }

            if (str_contains($request->url(), 'getEntriesJournal')) {
                $callIndex++;

                return Http::response($callIndex === 1 ? $chunk1 : $chunk2, 200);
            }

            return Http::response([], 404);
        });

        $result = $this->makeClient()->reporting()->entriesJournalChunked(
            CarbonImmutable::create(2018, 10, 19),
            CarbonImmutable::create(2018, 10, 31),
            7
        );

        $this->assertSame(2, $callIndex);
        // 4 lines of 3121 + 1 line of 3122 (once) + 2 lines of 3123 = 7
        $this->assertCount(7, $result['journals']);
        $this->assertSame([3121, 3121, 3121, 3121, 3122, 3123, 3123], array_column($result['journals'], 'id'));
        $this->assertSame(
            [12.0, 1.83, 5.0, 5.0, 1.0, 2.0, 0.3],
            array_map(static fn (array $r) => (float) $r['amount'], $result['journals'])
        );

        $callIndex = 0; // the typed call fetches both chunks again
        $typed = $this->makeClient()->reporting()->entriesJournalChunkedTyped(
            CarbonImmutable::create(2018, 10, 19),
            CarbonImmutable::create(2018, 10, 31),
            7
        );
        $this->assertCount(7, $typed->journals);
        $this->assertSame('3339', $typed->journals[1]->debitAcc);
    }

    public function test_chunked_rejects_chunk_days_below_one(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->makeClient()->reporting()->entriesJournalChunked(
            CarbonImmutable::create(2025, 1, 1),
            CarbonImmutable::create(2025, 1, 14),
            0
        );
    }

    public function test_chunk_windows_cover_the_whole_range_and_touch_at_boundaries(): void
    {
        $urls = [];

        Http::fake(function ($request) use (&$urls) {
            if (str_contains($request->url(), '/api/authentication/authenticate')) {
                return Http::response(['token' => 'tok', 'ex' => null], 200);
            }

            $urls[] = rawurldecode($request->url());

            return Http::response(['journals' => [], 'ex' => null], 200);
        });

        $this->makeClient()->reporting()->realizesJournalChunked(
            CarbonImmutable::create(2025, 1, 1, 8, 15, 0),
            CarbonImmutable::create(2025, 1, 20, 12, 0, 0),
            7
        );

        $this->assertSame([
            'http://fina-test.local:5007/api/reporting/getRealizesJournal/2025-01-01T00:00:00/2025-01-08T00:00:00',
            'http://fina-test.local:5007/api/reporting/getRealizesJournal/2025-01-08T00:00:00/2025-01-15T00:00:00',
            'http://fina-test.local:5007/api/reporting/getRealizesJournal/2025-01-15T00:00:00/2025-01-20T12:00:00',
        ], $urls);
    }
}
