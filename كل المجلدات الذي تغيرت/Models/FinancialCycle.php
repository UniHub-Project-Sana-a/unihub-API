<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCycle extends Model {
    use SoftDeletes;
    protected $primaryKey = 'cycle_id';
    protected $fillable = ['college_id', 'month_year', 'start_date', 'end_date', 'status', 'created_by', 'total_payout', 'lecturers_count'];

        public function recalculateCycleTotal() {
        // نستخدم Stored Column إذا كانت قاعدة البيانات تدعمه (MySQL 5.7+), 
        // وإلا نجمع يدوياً في الكود
        
        // طريقة الجمع اليدوي الآمنة:
        $total = $this->payouts()
            ->get()
            ->sum(function($payout) {
                return $payout->base_amount + $payout->total_bonuses - $payout->total_deductions - $payout->tax_amount;
            });
            
        $count = $this->payouts()->count();
        
        $this->update([
            'total_payout' => $total,
            'lecturers_count' => $count
        ]);
    }

    public function payouts() {
        return $this->hasMany(LecturerPayout::class, 'cycle_id');
    }
}