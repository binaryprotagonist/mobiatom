<?php

namespace App\Observers;

use App\Model\Delivery;
use App\Model\OrgAutoAppWorksflowActionLog;

class DeliveryObserver
{
    public function saved(Delivery $delivery)
    {
        $cs = Delivery::select('current_stage')->find($delivery->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'Delivery';
            $log->log_for_id    = $delivery->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
