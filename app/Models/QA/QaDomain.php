<?php

namespace App\Models\QA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QaDomain extends Model
{
    use HasFactory;

    protected $table = 'qa_domains';
    protected $primaryKey = 'domain_id';
    protected $guarded = ['domain_id'];

    // علاقة: المجال ينتمي لنموذج
    public function form()
    {
        return $this->belongsTo(QaForm::class, 'form_id');
    }

    // علاقة: المجال يحتوي على أسئلة (Questions)
    public function questions()
    {
        // نجلب الأسئلة مرتبة حسب الترتيب المحدد
        return $this->hasMany(QaQuestion::class, 'domain_id')->orderBy('sort_order');
    }
}