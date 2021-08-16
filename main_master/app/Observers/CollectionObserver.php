<?php

namespace App\Observers;

use App\Model\Collection;
use App\Model\OrgAutoAppWorksflowActionLog;

class CollectionObserver
{
    public function saved(Collection $collection)
    {
        $cs = Collection::select('current_stage')->find($collection->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'Collection';
            $log->log_for_id    = $collection->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
