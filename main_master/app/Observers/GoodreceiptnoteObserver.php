<?php

namespace App\Observers;

use App\Model\Goodreceiptnote;
use App\Model\OrgAutoAppWorksflowActionLog;

class GoodreceiptnoteObserver
{
    public function saved(Goodreceiptnote $goodreceiptnote)
    {
        $cs = Goodreceiptnote::select('current_stage')->find($goodreceiptnote->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'Goodreceiptnote';
            $log->log_for_id    = $goodreceiptnote->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
