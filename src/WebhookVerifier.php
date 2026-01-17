<?php

namespace Itsmedudes\LaravelWhatsapp;

class WebhookVerifier
{
    public function verifySignature(string $payload, ?string $signatureHeader, ?string $appSecret = null): bool
    {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $secret = $appSecret ?? (string) config('meta.app_secret');
        if ($secret === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        $signature = trim($signatureHeader);

        return hash_equals($expected, $signature);
    }

    public function verifyChallenge(string $mode, string $token, string $challenge, ?string $expectedToken = null): ?string
    {
        $verifyToken = $expectedToken ?? (string) config('meta.webhook_verify_token');
        if ($verifyToken === '') {
            return null;
        }

        if ($mode !== 'subscribe' || $token !== $verifyToken) {
            return null;
        }

        return $challenge;
    }
}
