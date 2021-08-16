<?php

namespace App\Observers;

use App\Model\CustomerInfo;
use App\Model\OrgAutoAppWorksflowActionLog;

class CustomerInfoObserver
{
    public function saved(CustomerInfo $customerInfo)
    {
        $cs = CustomerInfo::select('current_stage')->find($customerInfo->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'CustomerInfo';
            $log->log_for_id    = $customerInfo->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
