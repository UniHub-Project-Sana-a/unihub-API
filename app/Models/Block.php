<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $fillable = [
        'block_name', 'block_number', 'weight', 'credit_hours', 
        'weeks', 'type', 'program_id', 'level_id', 'description'
    ];

    // علاقة البلوك بالمتطلبات السابقة
    public function prerequisites()
    {
        return $this->belongsToMany(Block::class, 'block_relations', 'block_id', 'related_block_id')
                    ->wherePivot('relation_type', 'prerequisite')
                    ->withTimestamps();
    }

    // علاقة البلوك بالمجاورات
    public function concurrents()
    {
        return $this->belongsToMany(Block::class, 'block_relations', 'block_id', 'related_block_id')
                    ->wherePivot('relation_type', 'concurrent')
                    ->withTimestamps();
    }
}