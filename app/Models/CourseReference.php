<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseReference extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'course_references';
    protected $primaryKey = 'reference_id';
    protected $guarded = ['reference_id'];

    protected $fillable = [
        'course_id',
        'type',
        'category',
        'author',
        'year',
        'title',
        'edition',
        'publisher',
        'country',
        'url',
        'order',
    ];

    protected $casts = [
        'year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // العلاقات
    // ============================================================

    /**
     * المقرر الذي ينتمي له المرجع
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * فلترة حسب نوع المرجع
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * فلترة المراجع الرئيسية
     */
    public function scopeMain($query)
    {
        return $query->where('type', 'main');
    }

    /**
     * فلترة المراجع المساعدة
     */
    public function scopeSupport($query)
    {
        return $query->where('type', 'support');
    }

    /**
     * فلترة المصادر الإلكترونية
     */
    public function scopeElectronic($query)
    {
        return $query->where('type', 'electronic');
    }

    /**
     * فلترة حسب المقرر
     */
    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // ============================================================
    // الدوال المساعدة
    // ============================================================

    /**
     * الحصول على الاقتباس المرجعي (Harvard Style)
     */
    public function getHarvardCitation()
    {
        if ($this->type === 'electronic') {
            return "{$this->title} - " . ($this->url ? "Available at: {$this->url}" : "");
        }

        $citation = '';
        if ($this->author) {
            $citation .= $this->author . ' ';
        }
        if ($this->year) {
            $citation .= "({$this->year}) ";
        }
        if ($this->title) {
            $citation .= "{$this->title}. ";
        }
        if ($this->edition) {
            $citation .= "{$this->edition} edn. ";
        }
        if ($this->publisher) {
            $citation .= "{$this->publisher}";
        }
        if ($this->country) {
            $citation .= ", {$this->country}";
        }

        return trim($citation);
    }
}