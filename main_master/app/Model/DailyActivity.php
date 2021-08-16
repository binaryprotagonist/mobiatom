<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\Lob;
use App\User;

class DailyActivity extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'supervisor_id', 'lob_id', 'date', 'status'
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
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class,  'supervisor_id', 'id');
    }

    public function dailyActivityDetails()
    {
        return $this->hasMany(DailyActivityDetail::class,  'daily_activity_id', 'id');
    }

    public function dailyActivityCustomer()
    {
        return $this->hasMany(DailyActivityCustomer::class,  'daily_activity_id', 'id');
    }

    public function lob()
    {
        return $this->belongsTo(Lob::class,  'lob_id', 'id');
    }
}
