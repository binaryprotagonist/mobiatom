<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\JourneyPlan;
use App\Model\JourneyPlanWeek;
use App\Model\JourneyPlanCustomer;

class JourneyPlanDay extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'journey_plan_id', 'journey_plan_week_id', 'day_number', 'day_name'
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

    public function journeyPlanWeek()
    {
        return $this->belongsTo(JourneyPlanWeek::class,  'journey_plan_week_id', 'id');
    }

    public function journeyPlanCustomers()
    {
        return $this->hasMany(JourneyPlanCustomer::class,  'journey_plan_day_id', 'id');
    }
}
