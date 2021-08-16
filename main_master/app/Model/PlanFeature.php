<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class PlanFeature extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'feature_name', 'plan_id'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }
}
