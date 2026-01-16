<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpRestriction extends Model
{
    use HasFactory;

    protected $table = 'ip_restrictions';

    protected $fillable = [
        'type',
        'ip_address',
        'description',
        'is_active'
    ];
}