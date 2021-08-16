<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\WorkFlowRule;

class WorkFlowRuleModule extends Model
{    
	use LogsActivity;

    protected $fillable = [
        'name', 'type', 'status'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function workFlowRules()
    {
        return $this->hasMany(WorkFlowRule::class,  'work_flow_rule_module_id', 'id');
    }
}
