<?php

namespace App\Observers;

use App\Model\Warehouse;
use App\Model\OrgAutoAppWorksflowActionLog;

class WarehouseObserver
{
    public function saved(Warehouse $warehouse)
    {
        $cs = Warehouse::select('current_stage')->find($warehouse->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'Warehouse';
            $log->log_for_id    = $warehouse->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
