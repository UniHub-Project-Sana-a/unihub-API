<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LecturerPayout extends Model {
    protected $primaryKey = 'payout_id';
    protected $fillable = [
        'cycle_id', 'lecturer_id', 'total_hours', 'hourly_rate', 'base_amount', 
        'total_bonuses', 'total_deductions', 'tax_amount', 'status', 'notes'
    ];

    public function lecturer() {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }
    
    public function adjustments() {
        return $this->hasMany(PayoutAdjustment::class, 'payout_id');
    }
    
    // دالة مساعدة لتحديث المجاميع
    public function recalculateTotals() {
        $adjustments = $this->adjustments;
        $this->total_bonuses = $adjustments->where('type', 'bonus')->sum('amount');
        $this->total_deductions = $adjustments->where('type', 'deduction')->sum('amount');
        $this->tax_amount = $adjustments->where('type', 'tax')->sum('amount');
        $this->save(); // net_amount يتم حسابه تلقائياً في القاعدة (Stored Column) أو يمكن حسابه هنا إذا لم تدعمه قاعدتك
        
        // تحديث الكشف الرئيسي أيضاً
        $this->cycle->recalculateCycleTotal();
    }
    
    public function cycle() {
        return $this->belongsTo(FinancialCycle::class, 'cycle_id');
    }
}