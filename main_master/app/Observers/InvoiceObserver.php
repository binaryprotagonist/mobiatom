<?php

namespace App\Observers;

use App\Model\Invoice;
use App\Model\OrgAutoAppWorksflowActionLog;

class InvoiceObserver
{
    public function saved(Invoice $invoice)
    {
        $cs = Invoice::select('current_stage')->find($invoice->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'Invoice';
            $log->log_for_id    = $invoice->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
