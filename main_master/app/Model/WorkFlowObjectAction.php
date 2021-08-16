<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\WorkFlowObject;
use App\User;

class WorkFlowObjectAction extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'work_flow_object_id', 'user_id', 'approved_or_rejected'
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

    public function workFlowObject()
    {
        return $this->belongsTo(WorkFlowObject::class,  'work_flow_object_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }
}
