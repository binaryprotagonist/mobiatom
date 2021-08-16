<?php

namespace App\Observers;

use App\Model\Item;
use App\Model\OrgAutoAppWorksflowActionLog;

class ItemObserver
{
    public function saved(Item $item)
    {
        $cs = Item::select('current_stage')->find($item->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'Item';
            $log->log_for_id    = $item->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
