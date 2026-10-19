<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PayoutAdjustment extends Model {
    protected $primaryKey = 'adjustment_id';
    protected $fillable = ['payout_id', 'type', 'amount', 'reason', 'is_automatic'];
}