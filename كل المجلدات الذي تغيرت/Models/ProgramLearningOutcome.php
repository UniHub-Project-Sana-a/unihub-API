<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramLearningOutcome extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'program_learning_outcomes';
    protected $primaryKey = 'plo_id';
    protected $guarded = ['plo_id'];
    public $incrementing = true;

    protected $fillable = [
        'program_id',
        'code',
        'domain',
        'description',
        'weight',
        'is_active',
        'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'weight' => 'decimal:2',  // ✅ إضافة
        'program_id' => 'integer',
    ];

    protected $hidden = [
        'deleted_at'
    ];

    // ============================================================
    // العلاقات (Relationships)
    // ============================================================

    /**
     * البرنامج الذي ينتمي له المخرج
     */
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    /**
     * مخرجات تعلم المقررات المرتبطة به
     */
    public function courseLearningOutcomes()
    {
        return $this->hasMany(CourseLearningOutcome::class, 'plo_id', 'plo_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * فلترة المخرجات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فلترة حسب المجال
     */
    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * فلترة حسب البرنامج
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('domain')->orderBy('order');
    }
    // ============================================================
    // الدوال المساعدة
    // ============================================================

    /**
     * الحصول على الرمز الصحيح (A1, B2, إلخ)
     */
    public function getFormattedCode()
    {
        return strtoupper($this->code);
    }

    public function getDomainLabelAttribute(): string
    {
        $labels = [
            'Knowledge' => 'المعرفة',
            'Intellectual' => 'الفكري',
            'Professional' => 'المهني',
            'General' => 'العام',
        ];
        
        return $labels[$this->domain] ?? $this->domain;
    }

    public static function checkTotalWeight($programId): array
    {
        $total = self::where('program_id', $programId)->sum('weight');
        
        return [
            'total' => $total,
            'is_valid' => $total == 100,
            'difference' => 100 - $total,
        ];
    }
}