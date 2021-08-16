<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class OrgAutoAppWorksflowActionLog extends Model
{
    use LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'log_for', 'log_for_id', 'actioned_by', 'status'
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

    public function actionedBy()
    {
        return $this->belongsTo(User::class,  'actioned_by', 'id');
    }
}
