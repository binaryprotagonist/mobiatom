<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\WorkFlowRule;
use App\Model\WorkFlowRuleApprovalRole;
use App\User;

class WorkFlowRuleApprovalUser extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'work_flow_rule_id', 'wfr_approval_role_id', 'user_id'
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

    public function workFlowRuleApprovalRole()
    {
        return $this->belongsTo(WorkFlowRuleApprovalRole::class,  'wfr_approval_role_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }
}
