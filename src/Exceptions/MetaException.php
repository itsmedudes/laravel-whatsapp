<?php

namespace LaravelWhatsapp\Exceptions;

use RuntimeException;

class MetaException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?int $errorCode = null,
        public readonly ?int $errorSubcode = null,
        public readonly ?string $errorType = null,
        public readonly ?string $fbTraceId = null,
        public readonly array $responseBody = []
    ) {
        parent::__construct($message);
    }
}
