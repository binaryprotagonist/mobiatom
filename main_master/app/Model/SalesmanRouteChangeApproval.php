<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class SalesmanRouteChangeApproval extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'salesman_id', 'customer_id', 'journey_plan_id', 'supervisor_id', 'approval_date', 'route_approval', 'reason'
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

    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class, 'salesman_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class,  'supervisor_id', 'id');
    }

    public function journeyPlan()
    {
        return $this->belongsTo(JourneyPlan::class,  'journey_plan_id', 'id');
    }
}
