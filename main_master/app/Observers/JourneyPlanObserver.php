<?php

namespace App\Observers;

use App\Model\JourneyPlan;
use App\Model\OrgAutoAppWorksflowActionLog;

class JourneyPlanObserver
{
    public function saved(JourneyPlan $journeyPlan)
    {
        $cs = JourneyPlan::select('current_stage')->find($journeyPlan->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'JourneyPlan';
            $log->log_for_id    = $journeyPlan->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
