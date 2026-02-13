<?php

namespace App\Models\QA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\College; // تأكد من مسار موديل الكلية لديك

class QaForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qa_forms';
    protected $primaryKey = 'form_id';
    
    // نسمح بتعبئة كل الحقول ما عدا ID
    protected $guarded = ['form_id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // علاقة: النموذج يحتوي على مجالات (Domains)
    public function domains()
    {
        return $this->hasMany(QaDomain::class, 'form_id')->orderBy('sort_order');
    }

    // علاقة: النموذج قد يكون مخصصاً لكلية معينة
    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    // علاقة: النموذج لديه حملات تقييم مرتبطة به
    public function campaigns()
    {
        return $this->hasMany(QaCampaign::class, 'form_id');
    }
}