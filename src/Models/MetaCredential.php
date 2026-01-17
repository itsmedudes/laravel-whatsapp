<?php

namespace LaravelWhatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class MetaCredential extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'access_token',
        'refresh_token',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
        'is_active' => 'bool',
    ];

    public function getTable(): string
    {
        $table = (string) config('meta.table', 'meta_credentials');

        return $table !== '' ? $table : 'meta_credentials';
    }
}
