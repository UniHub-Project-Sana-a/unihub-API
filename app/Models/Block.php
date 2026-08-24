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

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id', 'level_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'block_id', 'id');
    }
}