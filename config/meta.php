<?php

return [
    'access_token' => env('META_ACCESS_TOKEN'),
    'token_resolver' => \LaravelWhatsapp\TokenResolvers\DatabaseTokenResolver::class,
    'app_secret' => env('META_APP_SECRET'),
    'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
    'graph_version' => env('META_GRAPH_VERSION', 'v19.0'),
    'base_url' => env('META_BASE_URL', 'https://graph.facebook.com'),
    'timeout' => env('META_TIMEOUT', 10),
    'retry' => env('META_RETRY', 0),
    'retry_delay' => env('META_RETRY_DELAY', 100),
    'retry_statuses' => array_filter(
        array_map('intval', explode(',', (string) env('META_RETRY_STATUSES', '429,500,502,503,504')))
    ),
    'retry_backoff' => env('META_RETRY_BACKOFF', 'linear'),
    'log_requests' => env('META_LOG_REQUESTS', false),
    'log_channel' => env('META_LOG_CHANNEL'),
    'request_id_header' => env('META_REQUEST_ID_HEADER', 'X-Request-Id'),
    'table' => env('META_TOKENS_TABLE', 'meta_credentials'),
];
