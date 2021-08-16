<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class PlanInvoice extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'subscription_id', 'plan_history_id', 'customer_invoice_data', 'start_date', 'date_end', 'description', 'amount', 'paid_date', 'due_date'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function planHistory()
    {
        return $this->belongsTo(PlanHistory::class, 'plan_history_id', 'id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'plan_history_id', 'id');
    }
}
