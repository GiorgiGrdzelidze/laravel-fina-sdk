<?php

declare(strict_types=1);

/**
 * HTTP client wrapper for the FINA Web API with auth retry and consistent error handling.
 */

namespace Fina\Sdk\Laravel\Client;

use Fina\Sdk\Laravel\Auth\AuthService;
use Fina\Sdk\Laravel\Auth\TokenStore;
use Fina\Sdk\Laravel\Exceptions\FinaConfigException;
use Fina\Sdk\Laravel\Exceptions\FinaHttpException;
use Fina\Sdk\Laravel\FinaSdkServiceProvider;
use Fina\Sdk\Laravel\Operation\CustomersApi;
use Fina\Sdk\Laravel\Operation\DocumentsApi;
use Fina\Sdk\Laravel\Operation\InventoriesApi;
use Fina\Sdk\Laravel\Operation\LoyaltyApi;
use Fina\Sdk\Laravel\Operation\ProductsApi;
use Fina\Sdk\Laravel\Operation\ReferenceApi;
use Fina\Sdk\Laravel\Operation\ServicesApi;
use Fina\Sdk\Laravel\Operation\VendorsApi;
use Fina\Sdk\Laravel\Reporting\JournalsApi;
use Fina\Sdk\Laravel\Reporting\ReportingApi;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Central client for the FINA Web API.
 *
 * Manages configuration, authentication, HTTP transport, and provides
 * lazy-loaded accessors for all API modules (Operation + Reporting).
 *
 * Resolve via `app(FinaClient::class)` or `app('fina')`.
 *
 * @see FinaSdkServiceProvider
 */
final class FinaClient
{
    private array $config;

    private TokenStore $tokenStore;

    private AuthService $auth;

    private ?CustomersApi $customersApi = null;

    private ?VendorsApi $vendorsApi = null;

    private ?ProductsApi $productsApi = null;

    private ?DocumentsApi $documentsApi = null;

    private ?LoyaltyApi $loyaltyApi = null;

    private ?ReferenceApi $referenceApi = null;

    private ?ServicesApi $servicesApi = null;

    private ?InventoriesApi $inventoriesApi = null;

    private ?JournalsApi $journalsApi = null;

    private ?ReportingApi $reportingApi = null;

    /**
     * @param  array<string, mixed>  $config  Merged FINA SDK configuration (from config/fina.php).
     *
     * @throws FinaConfigException If required config values are missing.
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        if ($baseUrl === '') {
            throw new FinaConfigException('FINA base_url is missing. Set FINA_BASE_URL in .env or config/fina.php');
        }

        $login = (string) ($this->config['login'] ?? '');
        $password = (string) ($this->config['password'] ?? '');
        if ($login === '' || $password === '') {
            throw new FinaConfigException('FINA credentials are missing. Set FINA_LOGIN and FINA_PASSWORD.');
        }

        $cacheKey = ($this->config['token_cache_key_prefix'] ?? 'fina:sdk:token:')
            .sha1($baseUrl.'|'.$login);

        $ttl = (int) ($this->config['token_cache_ttl_seconds'] ?? (35 * 3600));

        $this->tokenStore = new TokenStore($cacheKey, $ttl);
        $this->auth = new AuthService($this);
    }

    /**
     * Get the configured FINA base URL (without trailing slash).
     */
    public function baseUrl(): string
    {
        return rtrim((string) $this->config['base_url'], '/');
    }

    /**
     * Get the configured FINA login.
     */
    public function login(): string
    {
        return (string) $this->config['login'];
    }

    /**
     * Get the configured FINA password.
     */
    public function password(): string
    {
        return (string) $this->config['password'];
    }

    /**
     * Get all configured API prefixes.
     *
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return (array) ($this->config['prefixes'] ?? []);
    }

    /**
     * Returns prefix path without trailing slash, e.g. '/api/operation'
     */
    public function prefix(string $key): string
    {
        $prefix = (string) ($this->prefixes()[$key] ?? '');

        return $prefix !== '' ? rtrim($prefix, '/') : '';
    }

    /**
     * Create a pre-configured HTTP client instance.
     *
     * @param  string|null  $token  Optional bearer token to attach.
     */
    public function http(?string $token = null, bool $retry = true): PendingRequest
    {
        $timeout = (int) ($this->config['timeout'] ?? 120);
        $retryTimes = (int) ($this->config['retry_times'] ?? 2);
        $retrySleepMs = (int) ($this->config['retry_sleep_ms'] ?? 300);

        $req = Http::acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->baseUrl($this->baseUrl());

        if ($retry && $retryTimes > 0) {
            $req = $req->retry($retryTimes, $retrySleepMs);
        }

        if ($token) {
            $req = $req->withToken($token);
        }

        return $req;
    }

    /**
     * Get the token store instance.
     */
    public function tokenStore(): TokenStore
    {
        return $this->tokenStore;
    }

    /**
     * Get the authentication service instance.
     */
    public function auth(): AuthService
    {
        return $this->auth;
    }

    /**
     * Send an authorized request with auto token refresh on 401 (retry once).
     *
     * @param  'get'|'post'|'put'|'patch'|'delete'  $method
     */
    public function request(string $method, string $url, array $data = []): array
    {
        $token = $this->auth()->token();

        try {
            $resp = $this->send($method, $url, $token, $data)->throw();

            return is_array($resp->json()) ? $resp->json() : [];
        } catch (RequestException $e) {
            $status = $e->response?->status() ?? 0;

            if ($status === 401) {
                // Refresh token and retry once
                $this->auth()->forgetToken();
                $newToken = $this->auth()->token();

                try {
                    $resp = $this->send($method, $url, $newToken, $data)->throw();

                    return is_array($resp->json()) ? $resp->json() : [];
                } catch (RequestException $e2) {
                    $status2 = $e2->response?->status() ?? 0;
                    $body2 = $e2->response?->body();

                    throw new FinaHttpException($status2, $body2, $e2);
                }
            }

            $body = $e->response?->body();

            throw new FinaHttpException($status, $body, $e);
        }
    }

    /**
     * Whether a request writes to FINA (save* / update*). A write is never repeated by the
     * transport: a timeout or a 5xx after FINA saved the document would create it twice.
     */
    public static function isWrite(string $method, string $url): bool
    {
        $last = strtolower((string) basename((string) parse_url($url, PHP_URL_PATH)));

        return strtolower($method) !== 'get' && (str_starts_with($last, 'save') || str_starts_with($last, 'update'));
    }

    private function send(string $method, string $url, string $token, array $data = []): Response
    {
        $req = $this->http($token, retry: ! self::isWrite($method, $url));

        return match (strtolower($method)) {
            'get' => $req->get($url, $data),
            'post' => $req->post($url, $data),
            'put' => $req->put($url, $data),
            'patch' => $req->patch($url, $data),
            'delete' => $req->delete($url, $data),
            default => $req->send($method, $url, ['json' => $data]),
        };
    }

    /** Get the Customers API client. */
    public function customers(): CustomersApi
    {
        return $this->customersApi ??= new CustomersApi($this);
    }

    /** Get the Vendors API client. */
    public function vendors(): VendorsApi
    {
        return $this->vendorsApi ??= new VendorsApi($this);
    }

    /** Get the Products API client. */
    public function products(): ProductsApi
    {
        return $this->productsApi ??= new ProductsApi($this);
    }

    /** Get the Documents API client. */
    public function documents(): DocumentsApi
    {
        return $this->documentsApi ??= new DocumentsApi($this);
    }

    /** Get the Loyalty API client. */
    public function loyalty(): LoyaltyApi
    {
        return $this->loyaltyApi ??= new LoyaltyApi($this);
    }

    /** Get the Reference data API client. */
    public function reference(): ReferenceApi
    {
        return $this->referenceApi ??= new ReferenceApi($this);
    }

    /** Get the Services catalog API client. */
    public function services(): ServicesApi
    {
        return $this->servicesApi ??= new ServicesApi($this);
    }

    /** Get the Inventories (fixed assets) API client. */
    public function inventories(): InventoriesApi
    {
        return $this->inventoriesApi ??= new InventoriesApi($this);
    }

    /** Get the Journals API client (low-level reporting). */
    public function journals(): JournalsApi
    {
        return $this->journalsApi ??= new JournalsApi($this);
    }

    /** Get the Reporting API client (preferred — includes typed + chunked methods). */
    public function reporting(): ReportingApi
    {
        return $this->reportingApi ??= new ReportingApi($this);
    }
}
