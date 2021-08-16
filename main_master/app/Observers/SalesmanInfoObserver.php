<?php

namespace App\Observers;

use App\Model\SalesmanInfo;
use App\Model\OrgAutoAppWorksflowActionLog;

class SalesmanInfoObserver
{
    public function saved(SalesmanInfo $salesmanInfo)
    {
        $cs = SalesmanInfo::select('current_stage')->find($salesmanInfo->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'SalesmanInfo';
            $log->log_for_id    = $salesmanInfo->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
