<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\JourneyPlan;
use App\Model\JourneyPlanDay;

class JourneyPlanWeek extends Model
{
	use LogsActivity;

    protected $fillable = [
        'uuid', 'journey_plan_id', 'week_number'
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

    public function journeyPlanDays()
    {
        return $this->hasMany(JourneyPlanDay::class,  'journey_plan_week_id', 'id');
    }
}
