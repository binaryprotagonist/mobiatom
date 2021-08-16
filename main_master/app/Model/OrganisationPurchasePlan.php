<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\Software;
use App\Model\Plan;

class OrganisationPurchasePlan extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'software_id', 'plan_id', 'registed_user'
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

    public function software()
    {
        return $this->belongsTo(Software::class,  'software_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class,  'plan_id', 'id');
    }
}
