<?php

namespace LaravelWhatsapp\Facades;

use Illuminate\Support\Facades\Facade;

class Meta extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LaravelWhatsapp\WhatsAppBusinessClient::class;
    }
}
