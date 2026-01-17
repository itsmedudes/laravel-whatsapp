<?php

namespace Itsmedudes\LaravelWhatsapp\TokenResolvers;

use Itsmedudes\LaravelWhatsapp\Contracts\TokenResolverInterface;
use Itsmedudes\LaravelWhatsapp\Models\MetaCredential;

class DatabaseTokenResolver implements TokenResolverInterface
{
    public function resolve(?int $ownerId = null): ?string
    {
        $query = MetaCredential::query()->where('is_active', true);

        if ($ownerId !== null) {
            $query->where('user_id', $ownerId);
        }

        $query->where(function ($builder) {
            $builder->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });

        return $query->latest('id')->value('access_token');
    }
}
