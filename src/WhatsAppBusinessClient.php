<?php

namespace LaravelWhatsapp;

class WhatsAppBusinessClient extends MetaClient
{
    public function sendMessage(string $phoneNumberId, array $payload): array
    {
        return $this->request('post', $phoneNumberId . '/messages', [], $payload);
    }

    public function uploadMedia(string $phoneNumberId, array $payload): array
    {
        return $this->request('post', $phoneNumberId . '/media', [], $payload);
    }

    public function markMessageRead(string $phoneNumberId, array $payload): array
    {
        return $this->request('post', $phoneNumberId . '/messages', [], $payload);
    }
}
