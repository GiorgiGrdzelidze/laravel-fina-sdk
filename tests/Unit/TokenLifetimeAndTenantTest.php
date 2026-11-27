<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use Fina\Sdk\Laravel\Auth\AuthTokenResponse;
use Fina\Sdk\Laravel\Auth\TokenStore;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Exceptions\FinaConfigException;
use Fina\Sdk\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * `valid_to` of authenticate (10.0) sets how long the token is cached; `tenant_key` (10.0,
 * MultiTenant FINA) goes on every request when configured and keeps tenants' caches apart.
 */
final class TokenLifetimeAndTenantTest extends TestCase
{
    private const TTL = 35 * 3600;

    private const TOKEN_KEY_PREFIX = 'fina:sdk:test:token:';

    private string $zone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zone = date_default_timezone_get();
        config(['fina.token_cache_ttl_seconds' => self::TTL]);
        Carbon::setTestNow(Carbon::parse('2026-10-27 16:03:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set($this->zone);
        parent::tearDown();
    }

    private function client(): FinaClient
    {
        return $this->app->make(FinaClient::class);
    }

    /** Runs the rest of the test as an application whose `app.timezone` is `$zone`. */
    private function inZone(string $zone): void
    {
        config(['app.timezone' => $zone]);
        date_default_timezone_set($zone);
    }

    /** @return iterable<string, array{string}> */
    public static function appZones(): iterable
    {
        yield 'UTC' => ['UTC'];
        yield 'ahead of UTC' => ['Asia/Tbilisi'];
        yield 'behind UTC' => ['America/Los_Angeles'];
    }

    /** @param array<string, mixed> $auth */
    private function fakeFina(array $auth): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response($auth),
            '*/api/operation/getStores' => Http::response(['stores' => [], 'ex' => null]),
        ]);
    }

    private function authCalls(): int
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), '/api/authentication/authenticate'))->count();
    }

    private function tokenKey(string $identity): string
    {
        return self::TOKEN_KEY_PREFIX.sha1($identity);
    }

    // -------------------------------------------------------
    // AuthTokenResponse::$validTo / expiresAt()
    // -------------------------------------------------------

    public function test_auth_response_reads_valid_to_as_the_document_prints_it(): void
    {
        $dto = AuthTokenResponse::fromArray(['token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9', 'valid_to' => '2026-10-29T04:03:00', 'ex' => null]);

        $this->assertSame('2026-10-29T04:03:00', $dto->validTo);
        $this->assertSame('2026-10-29 04:03:00', $dto->expiresAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $dto->expiresAt()?->getTimezone()->getName(), 'no zone in the value: UTC, as FINA sends it');
        $this->assertSame(Carbon::parse('2026-10-29 04:03:00', 'UTC')->getTimestamp(), $dto->expiresAt()?->getTimestamp());
        $this->assertSame(Carbon::parse('2026-10-29 00:03:00', 'UTC')->getTimestamp(), AuthTokenResponse::fromArray(['token' => 't', 'valid_to' => '2026-10-29T04:03:00+04:00'])->expiresAt()?->getTimestamp());
        $this->assertNotNull(AuthTokenResponse::fromArray(['token' => 't', 'valid_to' => '2026-10-29T04:03:00.1234567'])->expiresAt());
        $this->assertNull(AuthTokenResponse::fromArray(['token' => 't', 'ex' => null])->validTo, '8.0 answer');
    }

    #[DataProvider('appZones')]
    public function test_a_zone_less_valid_to_is_read_as_utc_whatever_the_app_zone(string $zone): void
    {
        $this->inZone($zone);
        // read from a real FINA: this valid_to came in an answer whose Date header was Thu, 26 Nov 2026 22:54:06 GMT
        Carbon::setTestNow(Carbon::parse('Thu, 26 Nov 2026 22:54:06 GMT'));
        $store = new TokenStore('fina:sdk:test:zone', 48 * 3600);

        $expiresAt = AuthTokenResponse::fromArray(['token' => 't', 'valid_to' => '2026-11-28T10:54:07', 'ex' => null])->expiresAt();

        $this->assertSame(Carbon::parse('2026-11-28 10:54:07', 'UTC')->getTimestamp(), $expiresAt?->getTimestamp());
        $this->assertSame(36 * 3600 + 1 - 3600, $store->ttlFor($expiresAt), 'a 36h token, kept until an hour before it, in every app zone');
    }

    /** @return iterable<string, array{string}> */
    public static function zonedValidTo(): iterable
    {
        // the same instant each time: 2026-10-28 02:03:00 UTC, ten hours after now
        yield 'Z' => ['2026-10-28T02:03:00Z'];
        yield 'offset ahead' => ['2026-10-28T06:03:00+04:00'];
        yield 'offset behind' => ['2026-10-27T21:03:00-05:00'];
        yield 'offset without a colon' => ['2026-10-28T06:03:00+0400'];
    }

    #[DataProvider('zonedValidTo')]
    public function test_a_valid_to_with_its_own_zone_keeps_it(string $validTo): void
    {
        foreach (['UTC', 'Asia/Tbilisi', 'America/Los_Angeles'] as $zone) {
            $this->inZone($zone);
            $expiresAt = AuthTokenResponse::fromArray(['token' => 't', 'valid_to' => $validTo])->expiresAt();

            $this->assertSame(Carbon::parse('2026-10-28 02:03:00', 'UTC')->getTimestamp(), $expiresAt?->getTimestamp(), $zone);
            $this->assertSame(9 * 3600, $this->client()->tokenStore()->ttlFor($expiresAt), $zone);
        }
    }

    /** @return iterable<string, array{mixed}> */
    public static function unusableValidTo(): iterable
    {
        yield 'missing' => [null];
        yield 'empty' => [''];
        yield 'garbage' => ['not a date'];
        yield 'relative' => ['tomorrow'];
        yield 'date with a relative tail' => ['2026-10-29T04:03:00 +1 week'];
        yield 'impossible month' => ['2026-13-45T04:03:00'];
        yield 'number' => [1793232180];
        yield 'array' => [['2026-10-29T04:03:00']];
    }

    #[DataProvider('unusableValidTo')]
    public function test_unusable_valid_to_gives_no_expiry(mixed $validTo): void
    {
        $this->assertNull(AuthTokenResponse::fromArray(['token' => 't', 'valid_to' => $validTo])->expiresAt());
    }

    // -------------------------------------------------------
    // Cache lifetime
    // -------------------------------------------------------

    #[DataProvider('appZones')]
    public function test_valid_to_shortens_the_cache_to_an_hour_before_it(string $zone): void
    {
        $this->inZone($zone);
        $this->fakeFina(['token' => 'tok', 'valid_to' => '2026-10-28T02:03:00', 'ex' => null]); // now + 10h, in UTC

        $client = $this->client();
        $client->reference()->stores();
        $this->assertSame(1, $this->authCalls());

        Carbon::setTestNow(Carbon::parse('2026-10-28 01:02:59', 'UTC')); // 1s before valid_to - 1h
        $client->reference()->stores();
        $this->assertSame(1, $this->authCalls(), 'still cached');

        Carbon::setTestNow(Carbon::parse('2026-10-28 01:03:01', 'UTC'));
        $client->reference()->stores();
        $this->assertSame(2, $this->authCalls(), 'dropped an hour before valid_to, not after the fixed 35h');
    }

    public function test_without_valid_to_the_fixed_lifetime_applies(): void
    {
        $this->fakeFina(['token' => 'tok', 'ex' => null]);

        $client = $this->client();
        $client->reference()->stores();

        Carbon::setTestNow(Carbon::parse('2026-10-27 16:03:00')->addSeconds(self::TTL - 1));
        $client->reference()->stores();
        $this->assertSame(1, $this->authCalls());

        Carbon::setTestNow(Carbon::parse('2026-10-27 16:03:00')->addSeconds(self::TTL + 1));
        $client->reference()->stores();
        $this->assertSame(2, $this->authCalls());
    }

    /** @return iterable<string, array{mixed}> */
    public static function fallbackValidTo(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'garbled' => ['29/10/2026 04:03'];
        yield 'relative' => ['tomorrow'];
        yield 'not a string' => [true];
    }

    #[DataProvider('fallbackValidTo')]
    public function test_missing_or_unreadable_valid_to_falls_back_to_the_fixed_lifetime(mixed $validTo): void
    {
        $this->fakeFina(['token' => 'tok', 'valid_to' => $validTo, 'ex' => null]);

        $client = $this->client();
        $client->reference()->stores();
        $this->assertSame('tok', $client->tokenStore()->get());

        Carbon::setTestNow(Carbon::parse('2026-10-27 16:03:00', 'UTC')->addSeconds(self::TTL - 1));
        $client->reference()->stores();
        $this->assertSame(1, $this->authCalls(), 'kept for the fixed lifetime');

        Carbon::setTestNow(Carbon::parse('2026-10-27 16:03:00', 'UTC')->addSeconds(self::TTL + 1));
        $client->reference()->stores();
        $this->assertSame(2, $this->authCalls());
    }

    /** @return iterable<string, array{string}> */
    public static function nearValidTo(): iterable
    {
        yield 'already past' => ['2026-10-27T10:00:00'];
        yield 'less than the margin away' => ['2026-10-27T16:33:00'];
        yield 'exactly the margin away' => ['2026-10-27T17:03:00'];
        yield '.NET DateTime.MinValue' => ['0001-01-01T00:00:00'];
    }

    #[DataProvider('nearValidTo')]
    public function test_past_or_near_valid_to_keeps_the_token_one_minute(string $validTo): void
    {
        $this->fakeFina(['token' => 'tok', 'valid_to' => $validTo, 'ex' => null]);

        $client = $this->client();
        $client->reference()->stores();
        $this->assertSame('tok', $client->tokenStore()->get());

        Carbon::setTestNow(Carbon::parse('2026-10-27 16:03:59', 'UTC'));
        $client->reference()->stores();
        $this->assertSame(1, $this->authCalls(), 'kept for the minute');

        Carbon::setTestNow(Carbon::parse('2026-10-27 16:04:01', 'UTC'));
        $client->reference()->stores();
        $this->assertSame(2, $this->authCalls(), 'the next call after the minute logs in again, not after the fixed 35h');
    }

    public function test_a_far_valid_to_never_extends_the_configured_lifetime(): void
    {
        $this->fakeFina(['token' => 'tok', 'valid_to' => '9999-12-31T23:59:59', 'ex' => null]);

        $client = $this->client();
        $client->reference()->stores();

        Carbon::setTestNow(Carbon::parse('2026-10-27 16:03:00', 'UTC')->addSeconds(self::TTL + 1));
        $client->reference()->stores();
        $this->assertSame(2, $this->authCalls());
    }

    public function test_ttl_arithmetic(): void
    {
        $store = $this->client()->tokenStore();

        $this->assertSame(self::TTL, $store->ttlFor(null));
        $this->assertSame(9 * 3600, $store->ttlFor(Carbon::now()->addHours(10)));
        $this->assertSame(self::TTL, $store->ttlFor(Carbon::now()->addHours(36)), '36h token: the same 35h as before');
        $this->assertSame(self::TTL, $store->ttlFor(Carbon::now()->addHours(100)));
        $this->assertSame(1, $store->ttlFor(Carbon::now()->addSeconds(3601)));
        $this->assertSame(60, $store->ttlFor(Carbon::now()->addSeconds(3600)), 'the margin reached: one minute');
        $this->assertSame(60, $store->ttlFor(Carbon::now()->subMinute()), 'already past: one minute');
        $this->assertSame(30, (new TokenStore('fina:sdk:test:short', 30))->ttlFor(Carbon::now()->subMinute()), 'never longer than the configured TTL');
    }

    // -------------------------------------------------------
    // tenant_key
    // -------------------------------------------------------

    public function test_no_tenant_key_header_unless_configured(): void
    {
        $this->fakeFina(['token' => 'tok', 'ex' => null]);

        $this->client()->reference()->stores();

        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $r) => $r->hasHeader('tenant_key'));
        $this->assertNull($this->client()->tenantKey());
        $this->assertSame('tok', Cache::get($this->tokenKey('http://fina-test.local:5007|test_user')), 'token cache key unchanged');
    }

    public function test_blank_tenant_key_is_not_sent(): void
    {
        config(['fina.tenant_key' => '   ']);
        $this->fakeFina(['token' => 'tok', 'ex' => null]);

        $this->client()->reference()->stores();

        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $r) => $r->hasHeader('tenant_key'));
        $this->assertSame('tok', Cache::get($this->tokenKey('http://fina-test.local:5007|test_user')));
    }

    public function test_tenant_key_goes_on_authenticate_and_every_request(): void
    {
        config(['fina.tenant_key' => ' acme-01 ']);
        $this->fakeFina(['token' => 'tok', 'ex' => null]);

        $client = $this->client();
        $client->reference()->stores();

        $this->assertSame('acme-01', $client->tenantKey());
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/api/authentication/authenticate') && $r->hasHeader('tenant_key', 'acme-01'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/api/operation/getStores') && $r->hasHeader('tenant_key', 'acme-01') && $r->hasHeader('Authorization', 'Bearer tok'));
        Http::assertSentCount(2);
    }

    public function test_tenants_do_not_share_tokens_or_doc_types(): void
    {
        Http::fake([
            '*/api/authentication/*' => fn (Request $r) => Http::response(['token' => 'tok-'.($r->header('tenant_key')[0] ?? 'none'), 'ex' => null]),
            '*/api/operation/getDocTypes' => Http::response(['doc_types' => [['type' => 21, 'name' => 'n', 'api_supported' => true]], 'ex' => null]),
        ]);

        config(['fina.tenant_key' => 'a']);
        $this->app->forgetInstance(FinaClient::class);
        $this->client()->reference()->docTypesCached();

        config(['fina.tenant_key' => 'b']);
        $this->app->forgetInstance(FinaClient::class);
        $this->client()->reference()->docTypesCached();

        $this->assertSame('tok-a', Cache::get($this->tokenKey('http://fina-test.local:5007|test_user|a')));
        $this->assertSame('tok-b', Cache::get($this->tokenKey('http://fina-test.local:5007|test_user|b')));
        $this->assertNull(Cache::get($this->tokenKey('http://fina-test.local:5007|test_user')));
        $this->assertTrue(Cache::has('fina-sdk:doc-types:'.sha1('http://fina-test.local:5007|test_user|a')));
        $this->assertTrue(Cache::has('fina-sdk:doc-types:'.sha1('http://fina-test.local:5007|test_user|b')));
        $this->assertSame(2, $this->authCalls(), 'each tenant authenticated for itself');
    }

    public function test_doc_types_cache_key_without_a_tenant_is_unchanged(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getDocTypes' => Http::response(['doc_types' => [], 'ex' => null]),
        ]);

        $this->client()->reference()->docTypesCached();

        $this->assertTrue(Cache::has('fina-sdk:doc-types:'.sha1('http://fina-test.local:5007|test_user')));
    }

    public function test_two_clients_with_their_own_tenants_do_not_share_doc_types(): void
    {
        Http::fake([
            '*/api/authentication/*' => Http::response(['token' => 'tok', 'ex' => null]),
            '*/api/operation/getDocTypes' => fn (Request $r) => Http::response(['doc_types' => [['type' => 21, 'name' => 'of '.($r->header('tenant_key')[0] ?? 'none'), 'api_supported' => true]], 'ex' => null]),
        ]);

        // the global config names no tenant; each client is built with its own
        $a = new FinaClient(array_merge(config('fina'), ['tenant_key' => 'a']));
        $b = new FinaClient(array_merge(config('fina'), ['tenant_key' => 'b']));

        $this->assertSame('of a', $a->reference()->docTypesCached()[0]->name);
        $this->assertSame('of b', $b->reference()->docTypesCached()[0]->name, 'not the list cached for tenant a');
        $this->assertSame('of a', $a->reference()->docTypesCached()[0]->name, 'each tenant reads its own cache');
        $this->assertSame(2, Http::recorded(fn (Request $r) => str_contains($r->url(), '/getDocTypes'))->count());
        $this->assertTrue(Cache::has('fina-sdk:doc-types:'.sha1('http://fina-test.local:5007|test_user|a')));
        $this->assertTrue(Cache::has('fina-sdk:doc-types:'.sha1('http://fina-test.local:5007|test_user|b')));
        $this->assertFalse(Cache::has('fina-sdk:doc-types:'.sha1('http://fina-test.local:5007|test_user')), 'nothing under the key of an install without a tenant');
    }

    /** @return iterable<string, array{string}> */
    public static function tenantKeysWithControlCharacters(): iterable
    {
        yield 'CR LF, a second header' => ["acme\r\nX-Injected: 1"];
        yield 'trailing LF' => ["acme\n"];
        yield 'leading CR' => ["\racme"];
        yield 'NUL' => ["acme\0"];
        yield 'tab inside' => ["ac\tme"];
        yield 'escape' => ["acme\x1B[0m"];
        yield 'unit separator' => ["acme\x1F"];
        yield 'DEL' => ["acme\x7F"];
    }

    #[DataProvider('tenantKeysWithControlCharacters')]
    public function test_a_tenant_key_with_a_control_character_is_refused_without_repeating_it(string $key): void
    {
        try {
            new FinaClient(array_merge(config('fina'), ['tenant_key' => $key]));
            $this->fail('accepted a tenant key with a control character');
        } catch (FinaConfigException $e) {
            $this->assertStringContainsString('tenant_key', $e->getMessage());
            $this->assertStringNotContainsString($key, $e->getMessage());
            $this->assertStringNotContainsString('acme', $e->getMessage(), 'not even its printable part');
        }
    }

    public function test_a_configured_tenant_key_with_a_control_character_fails_before_any_request(): void
    {
        config(['fina.tenant_key' => "acme\r\nX-Injected: 1"]);
        $this->app->forgetInstance(FinaClient::class);
        Http::fake();

        try {
            $this->client()->reference()->stores();
            $this->fail('accepted a tenant key with a control character');
        } catch (FinaConfigException) {
            $this->addToAssertionCount(1);
        }

        Http::assertNothingSent();
    }

    public function test_printable_tenant_keys_are_still_accepted(): void
    {
        foreach ([['acme-01', 'acme-01'], [' acme 01 ', 'acme 01'], ['ტენანტი-7', 'ტენანტი-7'], ['', null], ['   ', null], [null, null]] as [$configured, $sent]) {
            $client = new FinaClient(array_merge(config('fina'), ['tenant_key' => $configured]));

            $this->assertSame($sent, $client->tenantKey(), var_export($configured, true));
        }
    }
}
