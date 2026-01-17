<?php

namespace Itsmedudes\LaravelWhatsapp;

use Itsmedudes\LaravelWhatsapp\Contracts\TokenResolverInterface;
use Itsmedudes\LaravelWhatsapp\Exceptions\MetaAuthenticationException;
use Itsmedudes\LaravelWhatsapp\Exceptions\MetaRateLimitException;
use Itsmedudes\LaravelWhatsapp\Exceptions\MetaRequestException;
use Itsmedudes\LaravelWhatsapp\Exceptions\MetaValidationException;
use Itsmedudes\LaravelWhatsapp\Models\MetaCredential;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaClient
{
    protected string $accessToken;
    protected string $graphVersion;
    protected string $baseUrl;
    protected int $timeout;
    protected int $retry;
    protected int $retryDelay;
    protected ?int $ownerId;
    protected ?TokenResolverInterface $tokenResolver;
    protected bool $logRequests;
    protected ?string $logChannel;
    protected array $retryStatuses;
    protected string $retryBackoff;
    protected string $requestIdHeader;

    public function __construct(
        ?string $accessToken = null,
        ?string $graphVersion = null,
        ?string $baseUrl = null,
        ?int $timeout = null,
        ?int $retry = null,
        ?int $retryDelay = null,
        ?int $ownerId = null,
        ?TokenResolverInterface $tokenResolver = null
    ) {
        $this->ownerId = $ownerId;
        $this->tokenResolver = $tokenResolver;
        $this->accessToken = $accessToken ?? $this->resolveAccessToken();
        $this->graphVersion = $graphVersion ?? (string) config('meta.graph_version', 'v19.0');
        $this->baseUrl = $baseUrl ?? (string) config('meta.base_url', 'https://graph.facebook.com');
        $this->timeout = $timeout ?? (int) config('meta.timeout', 10);
        $this->retry = $retry ?? (int) config('meta.retry', 0);
        $this->retryDelay = $retryDelay ?? (int) config('meta.retry_delay', 100);
        $this->logRequests = (bool) config('meta.log_requests', false);
        $this->logChannel = config('meta.log_channel');
        $this->retryStatuses = (array) config('meta.retry_statuses', [429, 500, 502, 503, 504]);
        $this->retryBackoff = (string) config('meta.retry_backoff', 'linear');
        $this->requestIdHeader = (string) config('meta.request_id_header', 'X-Request-Id');
    }

    protected function request(
        string $method,
        string $path,
        array $query = [],
        array $payload = [],
        array $headers = []
    ): array {
        $this->ensureAccessToken();

        $requestId = (string) Str::uuid();
        $headers[$this->requestIdHeader] = $requestId;
        $client = $this->newClient($headers);
        $versionedPath = $this->buildVersionedPath($path);

        $response = $client->send($method, $versionedPath, [
            'query' => $query,
            'json' => $payload,
        ]);

        return $this->handleResponse($response, $requestId, $method, $versionedPath);
    }

    protected function newClient(array $headers = []): PendingRequest
    {
        $client = Http::acceptJson()
            ->withToken($this->accessToken)
            ->baseUrl($this->baseUrl)
            ->timeout($this->timeout);

        if ($this->retry > 0) {
            $client = $client->retry(
                $this->retry,
                function (int $attempt, ?\Throwable $exception, ?\Illuminate\Http\Client\Request $request, ?Response $response): int {
                    $delay = $this->retryDelay;
                    if ($this->retryBackoff === 'exponential') {
                        $delay = $this->retryDelay * (2 ** ($attempt - 1));
                    }

                    return $delay;
                },
                function (?Throwable $exception, ?\Illuminate\Http\Client\Request $request, ?Response $response): bool {
                    if ($response) {
                        return in_array($response->status(), $this->retryStatuses, true);
                    }

                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                }
            );
        }

        if (!empty($headers)) {
            $client = $client->withHeaders($headers);
        }

        return $client;
    }

    protected function buildVersionedPath(string $path): string
    {
        $trimmed = trim($path, '/');
        $version = trim($this->graphVersion, '/');

        return '/' . $version . '/' . $trimmed;
    }

    protected function ensureAccessToken(): void
    {
        if ($this->accessToken === '') {
            throw new MetaAuthenticationException('Meta access token is required.');
        }
    }

    protected function resolveAccessToken(): string
    {
        $token = (string) config('meta.access_token');
        if ($token !== '') {
            return $token;
        }

        try {
            $resolver = $this->tokenResolver;
            if ($resolver === null && function_exists('app')) {
                $resolver = app(TokenResolverInterface::class);
            }

            if ($resolver !== null) {
                $token = (string) $resolver->resolve($this->ownerId);
            } else {
                $query = MetaCredential::query()->where('is_active', true);

                if ($this->ownerId !== null) {
                    $query->where('user_id', $this->ownerId);
                }

                $token = (string) $query->latest('id')->value('access_token');
            }
        } catch (\Throwable $exception) {
            return '';
        }

        return $token;
    }

    public function forUser(int $userId): static
    {
        $clone = clone $this;
        $clone->ownerId = $userId;
        $clone->tokenResolver = $this->tokenResolver;
        $clone->accessToken = $clone->resolveAccessToken();

        return $clone;
    }

    protected function handleResponse(Response $response, string $requestId, string $method, string $path): array
    {
        if ($response->successful()) {
            $this->logRequest($requestId, $method, $path, $response->status(), []);

            return $response->json() ?? [];
        }

        $body = $response->json() ?? [];
        $error = is_array($body['error'] ?? null) ? $body['error'] : [];
        $message = (string) ($error['message'] ?? $response->body() ?? 'Meta request failed.');
        $exception = $this->makeException($response->status(), $message, $error, $body);

        $this->logRequest($requestId, $method, $path, $response->status(), $error);

        throw $exception;
    }

    protected function makeException(int $status, string $message, array $error, array $body): \Itsmedudes\LaravelWhatsapp\Exceptions\MetaException
    {
        $code = isset($error['code']) ? (int) $error['code'] : null;
        $subcode = isset($error['error_subcode']) ? (int) $error['error_subcode'] : null;
        $type = isset($error['type']) ? (string) $error['type'] : null;
        $trace = isset($error['fbtrace_id']) ? (string) $error['fbtrace_id'] : null;

        return match ($status) {
            400 => new MetaValidationException($message, $status, $code, $subcode, $type, $trace, $body),
            401, 403 => new MetaAuthenticationException($message, $status, $code, $subcode, $type, $trace, $body),
            429 => new MetaRateLimitException($message, $status, $code, $subcode, $type, $trace, $body),
            default => new MetaRequestException($message, $status, $code, $subcode, $type, $trace, $body),
        };
    }

    protected function logRequest(string $requestId, string $method, string $path, int $status, array $error): void
    {
        if (!$this->logRequests) {
            return;
        }

        $logger = $this->logChannel ? Log::channel($this->logChannel) : Log::getFacadeRoot();

        $context = [
            'request_id' => $requestId,
            'method' => strtoupper($method),
            'path' => $path,
            'status' => $status,
        ];

        if (!empty($error)) {
            $context['error'] = [
                'message' => $error['message'] ?? null,
                'type' => $error['type'] ?? null,
                'code' => $error['code'] ?? null,
                'subcode' => $error['error_subcode'] ?? null,
                'fbtrace_id' => $error['fbtrace_id'] ?? null,
            ];
        }

        if ($logger) {
            $logger->debug('Meta API request', $context);
        } else {
            Log::debug('Meta API request', $context);
        }
    }
}
