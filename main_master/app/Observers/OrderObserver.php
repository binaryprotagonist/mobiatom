<?php

namespace App\Observers;

use App\Model\Order;
use App\Model\OrgAutoAppWorksflowActionLog;

class OrderObserver
{
    public function saved(Order $order)
    {
        $cs = Order::select('current_stage')->find($order->id);
        if(in_array($cs->current_stage, ['Pending','Approved','Rejected']))
        {
            $log = new OrgAutoAppWorksflowActionLog;
            $log->log_for       = 'Order';
            $log->log_for_id    = $order->id;
            $log->actioned_by   = auth()->id();
            $log->status        = $cs->current_stage;
            $log->save();
        }
    }
}
