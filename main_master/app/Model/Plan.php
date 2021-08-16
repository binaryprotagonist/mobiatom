<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class Plan extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'software_id', 'name', 'monthly_price', 'yearly_price', 'is_active', 'maximum_user'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id', 'id');
    }

    public function planFeature()
    {
        return $this->hasMany(PlanFeature::class, 'plan_id', 'id');
    }

    public function getSaveData()
    {
        $this->software;
        $this->planFeature;
        return $this;
    }
}
