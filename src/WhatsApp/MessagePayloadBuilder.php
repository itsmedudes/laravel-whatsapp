<?php

namespace Itsmedudes\LaravelWhatsapp\WhatsApp;

class MessagePayloadBuilder
{
    public static function text(string $to, string $body, bool $previewUrl = false): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $body,
            ],
        ];
    }

    public static function template(string $to, string $name, string $languageCode, array $components = []): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $name,
                'language' => [
                    'code' => $languageCode,
                ],
                'components' => $components,
            ],
        ];
    }

    public static function media(string $to, string $type, array $mediaPayload): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => $type,
            $type => $mediaPayload,
        ];
    }
}
