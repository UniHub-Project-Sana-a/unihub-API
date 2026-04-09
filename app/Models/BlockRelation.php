<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\Pivot;
class BlockRelation extends Model
{
    protected $table = 'block_relations';

    protected $fillable = [
        'block_id',
        'related_block_id',
        'relation_type'
    ];

    // علاقة للوصول للبلوك الأساسي
    public function block() {
        return $this->belongsTo(Block::class, 'block_id');
    }

    // علاقة للوصول للبلوك المرتبط
    public function relatedBlock() {
        return $this->belongsTo(Block::class, 'related_block_id');
    }
}