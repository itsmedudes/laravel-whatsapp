<?php

namespace LaravelWhatsapp\Contracts;

interface TokenResolverInterface
{
    public function resolve(?int $ownerId = null): ?string;
}
