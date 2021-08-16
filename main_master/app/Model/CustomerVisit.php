<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class CustomerVisit extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'route_id', 'trip_id', 'customer_id', 'salesman_id', 'journey_plan_id', 'latitude', 'longitude', 'shop_status', 'start_time', 'end_time', 'is_sequnece', 'date', 'reason', 'comment','added_on'
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
	
	public function trip()
    {
        return $this->belongsTo(Trip::class,  'trip_id', 'id');
    }
	
	public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
	
	public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }
	
	public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

	public function journeyPlan()
    {
        return $this->belongsTo(JourneyPlan::class, 'journey_plan_id', 'id');
    }

	public function customerActivity()
    {
        return $this->hasMany(CustomerActivity::class, 'customer_visit_id', 'id');
    }

    public function getSaveData()
    {
        $this->trip;
        $this->customer;
        $this->salesman;
        $this->route;
        $this->journeyPlan;
        $this->customerActivity;
        return $this;
    }
}
