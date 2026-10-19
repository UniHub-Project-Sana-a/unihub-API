<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationEntityRef extends Model
{
    protected $table = 'integration_entity_refs';

    protected $fillable = [
        'client_id',
        'entity',
        'external_id',
        'internal_id',
        'internal_key',
    ];

    protected $casts = [
        'internal_id' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(IntegrationClient::class, 'client_id');
    }
}
