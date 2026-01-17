# Laravel WhatsApp (Meta Graph)

Production-ready WhatsApp Business + Meta Graph client for Laravel with multi-tenant tokens, retries, logging, and webhook verification.

## Install

```
composer require itsmedudes/laravel-whatsapp
```

Publish config and migrations:

```
php artisan vendor:publish --tag=meta-config
php artisan vendor:publish --tag=meta-migrations
php artisan migrate
```

## Usage

```php
use LaravelWhatsapp\WhatsAppBusinessClient;
use LaravelWhatsapp\WhatsApp\MessagePayloadBuilder;

$client = app(WhatsAppBusinessClient::class)->forUser($userId);

$payload = MessagePayloadBuilder::text($to, 'Hello!');
$client->sendMessage($phoneNumberId, $payload);
```

### Webhook verification

```php
use LaravelWhatsapp\WebhookVerifier;

$verifier = new WebhookVerifier();
$ok = $verifier->verifySignature($payload, $signatureHeader);
```

## Configuration

See `config/meta.php` after publishing.
