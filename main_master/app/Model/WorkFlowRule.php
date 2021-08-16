<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\WorkFlowRuleModule;
use App\Model\WorkFlowRuleApprovalRole;
use App\Model\WorkFlowRuleApprovalUser;

class WorkFlowRule extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'work_flow_rule_module_id', 'work_flow_rule_name', 'description', 'event_trigger', 'status'
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

    public function workFlowRuleModule()
    {
        return $this->belongsTo(WorkFlowRuleModule::class,  'work_flow_rule_module_id', 'id');
    }

    public function workFlowRuleApprovalRoles()
    {
        return $this->hasMany(WorkFlowRuleApprovalRole::class,  'work_flow_rule_id', 'id');
    }

    public function workFlowRuleApprovalUsers()
    {
        return $this->hasMany(WorkFlowRuleApprovalUser::class,  'work_flow_rule_id', 'id');
    }
}
