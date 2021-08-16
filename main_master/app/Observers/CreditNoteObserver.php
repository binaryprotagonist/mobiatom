<?php

namespace App\Observers;

use App\Model\CreditNote;
use App\Model\OrgAutoAppWorksflowActionLog;

class CreditNoteObserver
{
    public function saved(CreditNote $creditNote)
    {
        $cs = CreditNote::select('current_stage')->find($creditNote->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'CreditNote';
            $log->log_for_id    = $creditNote->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
