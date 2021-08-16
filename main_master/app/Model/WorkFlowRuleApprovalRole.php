<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\WorkFlowRule;
use App\Model\OrganisationRole;
use App\Model\WorkFlowRuleApprovalUser;

class WorkFlowRuleApprovalRole extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'work_flow_rule_id', 'organisation_role_id'
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

    public function workFlowRule()
    {
        return $this->belongsTo(WorkFlowRule::class,  'work_flow_rule_id', 'id');
    }

    public function organisationRole()
    {
        return $this->belongsTo(OrganisationRole::class,  'organisation_role_id', 'id');
    }

    public function workFlowRuleApprovalUsers()
    {
        return $this->hasMany(WorkFlowRuleApprovalUser::class,  'wfr_approval_role_id', 'id');
    }
}
