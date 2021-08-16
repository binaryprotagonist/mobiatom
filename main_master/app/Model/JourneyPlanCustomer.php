<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\JourneyPlan;
use App\Model\JourneyPlanDay;
use App\Model\CustomerInfo;

class JourneyPlanCustomer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'journey_plan_id', 'journey_plan_day_id', 'customer_id', 'day_customer_sequence', 'day_start_time', 'day_end_time'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function journeyPlan()
    {
        return $this->belongsTo(JourneyPlan::class,  'journey_plan_id', 'id');
    }

    public function journeyPlanDay()
    {
        return $this->belongsTo(JourneyPlanDay::class,  'journey_plan_day_id', 'id');
    }

    public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'id');
    }
}
