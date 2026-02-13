<?php

namespace App\Models\QA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QaCampaignAssignment extends Model
{
    use HasFactory;

    protected $table = 'qa_campaign_assignments';
    protected $primaryKey = 'assignment_id';
    public $timestamps = false; // لا نحتاج created_at هنا
    protected $guarded = [];
}