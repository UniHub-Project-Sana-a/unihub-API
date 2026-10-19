<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationClient extends Model
{
    protected $table = 'integration_clients';

    protected $fillable = [
        'name',
        'token_hash',
        'allowed_ips',
        'allowed_macs',
        'is_active',
        'rate_limit_per_minute',
        'last_used_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'allowed_macs' => 'array',
        'is_active' => 'boolean',
        'rate_limit_per_minute' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * يحوّل التوكن الخام إلى التمثيل المخزَّن (sha256 hex).
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function logs()
    {
        return $this->hasMany(IntegrationLog::class, 'client_id');
    }
}
