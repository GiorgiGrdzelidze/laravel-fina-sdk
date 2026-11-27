<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Base URL
     |--------------------------------------------------------------------------
     | Example: https://your-fina-host:5007
     */
    'base_url' => env('FINA_BASE_URL'),

    /*
     |--------------------------------------------------------------------------
     | Credentials
     |--------------------------------------------------------------------------
     */
    'login' => env('FINA_LOGIN'),
    'password' => env('FINA_PASSWORD'),

    /*
     |--------------------------------------------------------------------------
     | Tenant (MultiTenant FINA)
     |--------------------------------------------------------------------------
     | Sent as the `tenant_key` header on every request, authenticate included.
     | Leave empty for a FINA that is not MultiTenant: the header is not sent.
     */
    'tenant_key' => env('FINA_TENANT_KEY'),

    /*
     |--------------------------------------------------------------------------
     | HTTP settings
     |--------------------------------------------------------------------------
     */
    'timeout' => (int) env('FINA_TIMEOUT', 120),
    'retry_times' => (int) env('FINA_RETRY_TIMES', 2),
    'retry_sleep_ms' => (int) env('FINA_RETRY_SLEEP_MS', 300),

    /*
     |--------------------------------------------------------------------------
     | Token cache
     |--------------------------------------------------------------------------
     | Token lifetime is ~36 hours in FINA; we keep a safe default slightly lower.
     | When authenticate returns `valid_to`, the token is kept until one hour
     | before it, but never longer than this. `valid_to` comes without a time
     | zone and is read as UTC (a value with its own offset keeps it); one that
     | is already past or less than an hour away keeps the token 60 seconds.
     */
    'token_cache_ttl_seconds' => (int) env('FINA_TOKEN_TTL', 35 * 3600),
    'token_cache_key_prefix' => env('FINA_TOKEN_CACHE_PREFIX', 'fina:sdk:token:'),

    /*
     |--------------------------------------------------------------------------
     | API prefixes (FINA Web API)
     |--------------------------------------------------------------------------
     */
    'prefixes' => [
        'authentication' => '/api/authentication',
        'operation' => '/api/operation',
        'reporting' => '/api/reporting',
    ],
    'cache' => [
        // Doc types rarely change; 1 hour default
        'doc_types_ttl' => (int) env('FINA_DOC_TYPES_TTL', 3600),

        // optional: cache key prefix to avoid collisions
        'prefix' => env('FINA_CACHE_PREFIX', 'fina-sdk'),
    ],
];
